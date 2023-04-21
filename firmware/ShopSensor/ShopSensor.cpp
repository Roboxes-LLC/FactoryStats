#include "Adapter/HttpClientAdapter.hpp"
#include "Board/WifiBoard.hpp"
#include "Component/Button.hpp"
#include "Connection/ConnectionManager.hpp"
#include "Logger/Logger.hpp"
#include "Messaging/Address.hpp"
#include "Messaging/Messaging.hpp"

#include "BreakDefs.hpp"
#include "ComponentDefs.hpp"
#include "ConfigPage.hpp"
#include "Diagnostics.hpp"
#include "Display.hpp"
#include "DisplayM5Tough.hpp"
#include "FactoryStatsDefs.hpp"
#include "Power.hpp"
#include "Robox.hpp"
#include "ShopSensor.hpp"
#include "Version.hpp"

static const int DISPLAY_TIME = 5000;  // 5 seconds

static const bool NO_REDRAW = false;

// ************************************************
//                     Public

ShopSensor::ShopSensor(
   const String& id,
   const int& updatePeriod,
   const int& pingPeriod,
   const String& connectionId,
   const String& displayId,
   const String& powerId,
   const String& adapterId,
   const String& breakManagerId) :
      Component(id),
      updateTimer(0),
      displayTimer(0),
      webServer(0),
      updatePeriod(updatePeriod),
      pingPeriod(pingPeriod),
      displayId(displayId),
      powerId(powerId),
      adapterId(adapterId),
      breakManagerId(breakManagerId),
      count(0),
      totalCount(0),
      updateCount(0),
      stationId(UNKNOWN_STATION_ID),
      stationLabel(UNKNOWN_STATION_LABEL)
{
  this->pingPeriod = (this->pingPeriod > 0) ? this->pingPeriod : 1;
}

ShopSensor::ShopSensor(
   MessagePtr message) :
      Component(message),
      updateTimer(0),
      displayTimer(0),
      webServer(0),
      updatePeriod(message->getInt("updatePeriod")),
      pingPeriod(message->getInt("pingPeriod")),
      connectionId(message->getString("connection")),
      displayId(message->getString("display")),
      powerId(message->getString("power")),
      adapterId(message->getString("adapter")),
      breakManagerId(message->getString("breakManager")),
      count(0),
      totalCount(0),
      updateCount(0),
      stationId(UNKNOWN_STATION_ID),
      stationLabel(UNKNOWN_STATION_LABEL)
{
   pingPeriod = (pingPeriod > 0) ? pingPeriod : 1;
}

ShopSensor::~ShopSensor()
{
   Timer::freeTimer(updateTimer);
}

void ShopSensor::setup()
{
   Component::setup();
   
   uid = getUid();
   
   String server = Robox::getProperties().getString("server");

   Display* display = getDisplay();
   if (display)
   {
      display->updateId(uid, NO_REDRAW);
      
      display->updateServer(server, false, NO_REDRAW);

      display->setRotation(static_cast<Rotation>(Robox::getProperties().getInt("rotation")));

      // Show the splash screen (temporarily).
      setDisplayMode(Display::SPLASH, DISPLAY_TIME);
   }
      
   Messaging::subscribe(this, ConnectionManager::CONNECTION);
   Messaging::subscribe(this, Power::POWER_INFO);
   Messaging::subscribe(this, Roboxes::Button::BUTTON_UP);
   Messaging::subscribe(this, Roboxes::Button::BUTTON_LONG_PRESS);
   
   updateTimer = Timer::newTimer(
      getId() + ".update",
      updatePeriod,
      Timer::PERIODIC,
      this);
      
   if (updateTimer)
   {
      updateTimer->start();
   }
   
   webServer = new WebpageServer("webserver", 80);
   webServer->addPage(new ConfigPage(uid));
   webServer->addPage(new Webpage("/", "/index.html"));
   Robox::addComponent(webServer);
   
   if (!getDisplay())
   {
      Logger::logWarning(F("ShopSensor::setup: No available display."));   
   }
   
   if (!getConnection())
   {
      Logger::logWarning(F("ShopSensor::setup: No available connection manager."));   
   }
   
   if (!getAdapter())
   {
      Logger::logWarning(F("ShopSensor::setup: No available adapter."));   
   }
}

void ShopSensor::handleMessage(
   MessagePtr message)
{
   // WIFI_CONNECTED, WIFI_DISCONNECTED
   if (message->getTopic() == ConnectionManager::CONNECTION)
   {
      onConnectionUpdate(message);
   }
   //  BUTTON_UP
   else if (message->getTopic() == Roboxes::Button::BUTTON_UP)
   {
      onButtonUp(message->getSource());
   }
   //  BUTTON_LONG_PRESS
   else if (message->getTopic() == Roboxes::Button::BUTTON_LONG_PRESS)
   {
      onButtonLongPress(message->getSource());
   }
   //  POWER_INFO
   else if (message->getTopic() == Power::POWER_INFO)
   {
      onPowerInfo(message);
   }   
   //  HTTP_RESPONSE
   else if (message->getMessageId() == HttpClientAdapter::HTTP_RESPONSE)
   {
      onServerResponse(message);
   }
   else
   {
      Component::handleMessage(message);
   }

   Messaging::freeMessage(message);
}

void ShopSensor::timeout(
   Timer* timer)
{
   if (timer == updateTimer)
   {
      bool updateRequired =
         ((count != 0) ||                      // Update if there is a count
          hasPendingBreak()||                  // Update if there is a break
          (updateCount == 0) ||                // Initial update
          ((updateCount % pingPeriod) == 0));  // Always update on ping periods
      
      if (updateRequired && isConnected() && sendUpdate())
      {
         count = 0;
         clearPendingBreak();
      }
      
      updateCount++;
   }
   else if (timer == displayTimer)
   {
      displayTimer = 0;
      
      Display* display = getDisplay();
      if (display)
      {
         ConnectionManager* connection = getConnection();
         if (connection && connection->isWifiConnected())
         {
            display->setMode(Display::COUNT);         
         }
         else
         {
            display->setMode(Display::CONNECTION);         
         }
      }
   }
   else
   {
      Logger::logWarning(F("ShopSensor::timeout: Received unexpected timeout: %s"), timer->getId().c_str());
   }
}

// ************************************************
//                    Protected

ConnectionManager* ShopSensor::getConnection()
{
   static ConnectionManager* connection = 0;
   
   if (!connection)
   {
      connection = (ConnectionManager*)Robox::getComponent(connectionId);
   }
   
   return (connection);
}

Display* ShopSensor::getDisplay() const
{
   static Display* display = 0;

   if (!display)
   {
      display = (Display*)Robox::getComponent(displayId);
   }
   
   return (display);
}

Power* ShopSensor::getPower() const
{
   static Power* power = 0;
   
   if (!power)
   {
      power = (Power*)Robox::getComponent(powerId);
   }
   
   return (power);
}

Adapter* ShopSensor::getAdapter() const
{
   static Adapter* adapter = 0;
   
   if (!adapter)
   {
      adapter = (Adapter*)Robox::getComponent(adapterId);
   }
   
   return (adapter);   
}

BreakManager* ShopSensor::getBreakManager() const
{
   static BreakManager* breakManager = 0;

   if (!breakManager)
   {
      breakManager = (BreakManager*)Robox::getComponent(breakManagerId);
   }

   return (breakManager);
}

void ShopSensor::setDisplayMode(
   const Display::DisplayMode& displayMode,
   const int& duration)
{
   Display* display = getDisplay();
   if (display)
   {
      // Don't update the display while the splash screen is being displayed.
      bool isSplash = (displayTimer && (display->getMode() == Display::SPLASH));
      
      if (!isSplash)
      {
         display->setMode(displayMode);
         
         // Cancel any running display timer.
         if (displayTimer)
         {
            Timer::freeTimer(displayTimer);
            displayTimer = 0;
         }      
         
         if (duration > 0)
         {
            displayTimer = Timer::newTimer(
               getId() + ".display",
               duration,
               Timer::ONE_SHOT,
               this);
               
            if (displayTimer)
            {
               displayTimer->start();
            }
         }
      }
   }
}

void ShopSensor::toggledDisplayMode()
{
   // Cancel any running display timer.
   if (displayTimer)
   {
      Timer::freeTimer(displayTimer);
      displayTimer = 0;
   }
   
   // Toggle display mode.
   Display* display = getDisplay();
   if (display)
   {
      display->toggleMode();
   }
}

void ShopSensor::rotateDisplay()
{
   Display* display = getDisplay();
   if (display)
   {
      // Retrieve the current rotation.
      Rotation rotation = display->getRotation();

      // Rotate.
      // Note: Only two orientations are supported.
      if (rotation == Rotation::CW_90)
      {
         rotation = CW_270;
      }
      else
      {
         rotation = CW_90;
      }

      // Store the new rotation.
      Properties& properties = Robox::getProperties();
      properties.set("rotation", rotation);
      properties.save();

      display->setRotation(rotation);

      display->redraw();
   }
}

void ShopSensor::onConnectionUpdate(
   MessagePtr message)
{
   ConnectionManager* connection = getConnection();
   
   Display* display = getDisplay();
   
   if (connection && display)
   {
      display->updateConnection(
         connection->getWifiConfig().ssid,
         connection->getAPConfig().ssid,
         connection->isWifiConnected(),
         connection->isAPRunning(),
         connection->getIpAddress(),
         connection->getAPIpAddress());
         
      setDisplayMode(Display::CONNECTION, DISPLAY_TIME);

      static bool isWebServerRunning = false;
      if ((connection->isWifiConnected() || connection->isAPRunning()) && webServer && !isWebServerRunning)
      {
         isWebServerRunning = true;
         webServer->start();
      }
   }
}

void ShopSensor::onButtonUp(
   const String& buttonId)
{
   Logger::logDebug("ShopSensor::onButtonUp: Button [%s] pressed.", buttonId.c_str());
   
   if ((buttonId == LIMIT_SWITCH) ||
       (buttonId == BUTTON_A) ||
       (buttonId == INCREMENT_BUTTON))
   {
      count++;

      Display* display = getDisplay();
      if (display)
      {
         display->updateCount(totalCount, count);

#if defined(M5STICKC) || defined (M5STICKC_PLUS)
         setDisplayMode(Display::COUNT, DISPLAY_TIME);
#endif
      }
   }
   else if (buttonId == DECREMENT_BUTTON)
   {
      count--;

      Display* display = getDisplay();
      if (display)
      {
         display->updateCount(totalCount, count);

#if defined(M5STICKC) || defined (M5STICKC_PLUS)
         setDisplayMode(Display::COUNT, DISPLAY_TIME);
#endif
      }
   }
   else if (buttonId == BUTTON_B)
   {
      toggledDisplayMode();
   }
   else if (buttonId == ROTATE_BUTTON)
   {
      rotateDisplay();
   }
}

void ShopSensor::onButtonLongPress(
   const String& buttonId)
{
   Logger::logDebug("ShopSensor::onButtonLongPress: Button [%s] long-pressed.", buttonId.c_str());
}

void ShopSensor::onPowerInfo(
   MessagePtr message)
{
   if (message->getMessageId() == Power::POWER_SOURCE)
   {
      bool isUsbPower = message->getBool("isUsbPower");
      
      setDisplayMode(Display::POWER, DISPLAY_TIME);
      
      if (!isUsbPower && Robox::getProperties().getBool("requireUsbPower"))
      {
         Logger::logDebug("ShopSensor::onPowerInfo: Powering-off after loss of USB power");
         
         Power* power = getPower();
         if (power)
         {
            power->powerOff();
         }
      }
   }
} 

bool ShopSensor::sendUpdate()
{
   bool success = false;
   
   MessagePtr message = Messaging::newMessage();
   if (message)
   {
      message->setMessageId(SENSOR_UPDATE_MESSAGE_ID);
      message->setSource(getId());
      message->setDestination(adapterId);
      message->setTransaction(SENSOR_UPDATE_MESSAGE_ID);
      
      // Specify HTTP parameters.
      message->set(HttpClientAdapter::REQUEST_TYPE, HttpClientAdapter::GET);
      message->set(HttpClientAdapter::ENCODING, HttpClientAdapter::URL_ENCODING);
      //message->set("subdomain", "flexscreentest");  // TODO: For local testing.  Remove.

      String url = getRequestUrl(Robox::getProperties().getString("server"),
                                 "sensor");
      if (url != "")
      {
         message->set("url", url);
      } 

      message->set("uid", uid);
      message->set("version", VERSION);
      message->set("ipAddress", getIpAddress());

      message->set("count", count);

      if (hasPendingBreak())
      {
         message->set("breakCode", getPendingBreakCode());
      }

      success = Messaging::send(message);

      if (success)
      {
         Logger::logDebug(F("ShopServer::sendUpdate: Sent count [%d] to server for sensor [%s]."), count, uid);
      }
      else
      {
         Logger::logWarning(F("ShopSensor::sendUpdate: Failed to send count [%d] to server for sensor [%s]."), count, uid);
      }
   }
   
   return (success);   
}

void ShopSensor::onServerResponse(MessagePtr message)
{
   int responseCode =  message->getInt(HttpClientAdapter::RESPONSE_CODE);
   
   Logger::logDebug(F("ShopSensor::onServerResponse: Got server response for client [%s]: %d."), uid, responseCode);

   Display* display = getDisplay();
   if (display)
   {
      if (responseCode == 200)
      {
         if (message->getTransaction() == SENSOR_UPDATE_MESSAGE_ID)
         {
            totalCount = message->getInt("totalCount");

            if (message->isSet("stationId"))
            {
               stationId = message->getInt("stationId");
            }
            else
            {
               stationId = UNKNOWN_STATION_ID;
            }

            if (message->isSet("stationLabel"))
            {
               stationLabel = message->getString("stationLabel");
            }
            else
            {
               stationLabel = UNKNOWN_STATION_LABEL;
            }

            if (message->isSet("breakId"))
            {
               confirmBreak(message->getInt("breakId"));
            }

            display->updateServer(true);

            display->updateStation(stationId, stationLabel, NO_REDRAW);

            display->updateBreak(isOnBreak(), NO_REDRAW);

            display->updateCount(totalCount, count);
         }
      }
      else
      {
         display->updateServer(false);
      }
   }
}

bool ShopSensor::isConnected()
{
   WifiBoard* board = 0;
   
   return ((board = WifiBoard::getBoard()) && board->isConnected());
}

String ShopSensor::getIpAddress()
{
   WifiBoard* board = 0;
   
   return ((board = WifiBoard::getBoard()) ? board->getIpAddress() : "");
}

String ShopSensor::getUid()
{
   String uid = "";

   WifiBoard* board = WifiBoard::getBoard();
   
   if (board)
   {
      // Get the MAC address.
      unsigned char mac[6] = {0, 0, 0, 0, 0, 0};
      WifiBoard::getBoard()->getMacAddress(mac);
      
      // Last six hex digits of MAC address.
      char uidStr[7];
      sprintf(uidStr, "%02X%02X%02X", mac[3], mac[4], mac[5]);
      uidStr[6] = 0;  // Null terminate.
      
      uid = String(uidStr);
   }
   
   return (uid);   
}

bool ShopSensor::isOnBreak() const
{
   BreakManager* breakManager = getBreakManager();

   return (breakManager && breakManager->isOnBreak());
}

void ShopSensor::confirmBreak(
   const int& confirmedBreakId) const
{
   BreakManager* breakManager = getBreakManager();
   if (breakManager)
   {
      breakManager->confirmBreak(confirmedBreakId);
   }
}

bool ShopSensor::hasPendingBreak() const
{
   BreakManager* breakManager = getBreakManager();

   return (breakManager && breakManager->hasPendingBreak());
}

void ShopSensor::clearPendingBreak() const
{
   BreakManager* breakManager = getBreakManager();
   if (breakManager)
   {
      breakManager->clearPendingBreak();
   }
}

String ShopSensor::getPendingBreakCode() const
{
   BreakManager* breakManager = getBreakManager();

   return (breakManager ? breakManager->getPendingBreakCode() : NO_BREAK_CODE);
}

void ShopSensor::toggleBreak() const
{
   BreakManager* breakManager = getBreakManager();
   if (breakManager)
   {
      breakManager->toggleBreak();
   }
}
