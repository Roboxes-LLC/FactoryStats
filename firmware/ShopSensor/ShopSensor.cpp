#include "Adapter/HttpClientAdapter.hpp"
#include "Board/WifiBoard.hpp"
#include "Connection/Connection.hpp"
#include "Logger/Logger.hpp"
#include "Messaging/Messaging.hpp"
#include "ConfigPage.hpp"
#include "ConnectionManager.hpp"
#include "Diagnostics.hpp"
#include "Display.hpp"
#include "Robox.hpp"
#include "ShopSensor.hpp"

static const int SPLASH_TIME = 5000;  // 5 seconds

// ************************************************
//                     Public

ShopSensor::ShopSensor(
   const String& id,
   const int& updatePeriod,
   const String& connectionId,
   const String& displayId,
   const String& adapterId) :
      Component(id),
      updateTimer(0),
      webServer(0),
      updatePeriod(updatePeriod),
      displayId(displayId),
      adapterId(adapterId),
      count(0),
      totalCount(0)
{
}

ShopSensor::ShopSensor(
   MessagePtr message) :
      Component(message),
      updateTimer(0),
      webServer(0),
      updatePeriod(message->getInt("period")),
      connectionId(message->getString("connection")),
      displayId(message->getString("display")),
      adapterId(message->getString("adapter")),
      count(0),
      totalCount(0)
{
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
      display->updateId(uid);
      
      display->updateServer(server, false);

      // Show the splash screen.      
      display->setMode(Display::SPLASH);
      
      Timer* timer = Timer::newTimer(
         "splash",
         SPLASH_TIME,
         Timer::ONE_SHOT,
         this);
         
      timer->start();
   }
      
   Messaging::subscribe(this, ConnectionManager::CONNECTION);
   Messaging::subscribe(this, "buttonUp");
   Messaging::subscribe(this, "buttonLongPress");
   
   updateTimer = Timer::newTimer(
      "update",
      updatePeriod,
      Timer::PERIODIC,
      this);
      
   updateTimer->start();
   
   webServer = new WebpageServer("webserver", 80);
   webServer->addPage(new ConfigPage(uid));
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
   //  buttonUp
   else if (message->getTopic() == "buttonUp")
   {
      onButtonUp(message->getSource());
   }
   //  buttonLongPress
   else if (message->getTopic() == "buttonLongPress")
   {
      onButtonLongPress(message->getSource());
   }
   //  HTTP response
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
      if (isConnected() && sendUpdate())
      {
         count = 0;
      }
   }
   else if (timer->getId() == "splash")
   {
      Display* display = getDisplay();
      if (display && (display->getMode() == Display::SPLASH))
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

Display* ShopSensor::getDisplay()
{
   static Display* display = 0;
   
   if (!display)
   {
      display = (Display*)Robox::getComponent(displayId);
   }
   
   return (display);
}

Adapter* ShopSensor::getAdapter()
{
   static Adapter* adapter = 0;
   
   if (!adapter)
   {
      adapter = (Adapter*)Robox::getComponent(adapterId);
   }
   
   return (adapter);   
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
   
   if ((buttonId == LIMIT_SWITCH) || (buttonId == BUTTON_A))
   {
      count++;
      
      Display* display = getDisplay();
      if (display)
      {
         display->setMode(Display::COUNT);
         display->updateCount(totalCount, count);
      }
   }
   else if (buttonId == BUTTON_B)
   {
     // Toggle display mode.
     Display* display = getDisplay();
     if (display)
     {
        display->toggleMode();
     }
   }
}

void ShopSensor::onButtonLongPress(
   const String& buttonId)
{
   Logger::logDebug("ShopSensor::onButtonLongPress: Button [%s] long-pressed.", buttonId.c_str());
}

bool ShopSensor::sendUpdate()
{
   bool success = false;
   
   MessagePtr message = Messaging::newMessage();
   if (message)
   {
      message->setMessageId("sensor");
      message->setSource(getId());
      message->setDestination(adapterId);
      
      String url = getRequestUrl();
      if (url != "")
      {
         message->set("url", url);
      } 

      message->set("uid", uid);
      //message->set("ipAddress", getIpAddress());  // TODO: url encode
      message->set("count", count);

      success = Messaging::send(message);

      if (success)
      {
         Logger::logDebug(F("ShopSensor::sendUpdate: Sent count [%d] to server."), count);
      }
      else
      {
         Logger::logWarning(F("ShopSensor::sendUpdate: Failed to send count [%d] to server."), count);
      }
   }
   
   return (success);   
}

void ShopSensor::onServerResponse(MessagePtr message)
{
   int responseCode =  message->getInt(HttpClientAdapter::RESPONSE_CODE);
   
   Logger::logDebug(F("ShopSensor::onServerReponse: Server response: %d"), responseCode);
   
   Display* display = getDisplay();
   if (display)
   {
      if (responseCode == 200)
      {
         totalCount = message->getInt("totalCount");

         display->updateServer(true);
         
         display->updateCount(totalCount, count);
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

String ShopSensor::getRequestUrl()
{
   String url = "";
   
   String server = Robox::getProperties().getString("server");
   
   if (server != "")
   {
      url = server + "/api/";
   }

   return (url);
}
