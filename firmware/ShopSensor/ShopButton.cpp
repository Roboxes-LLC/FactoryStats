#include "Adapter/HttpClientAdapter.hpp"
#include "Board/WifiBoard.hpp"
#include "Connection/ConnectionManager.hpp"
#include "Component/Button.hpp"
#include "Logger/Logger.hpp"
#include "Messaging/Address.hpp"
#include "Messaging/Messaging.hpp"
#include "ConfigPage.hpp"
#include "Diagnostics.hpp"
#include "Display.hpp"
#include "Power.hpp"
#include "Robox.hpp"
#include "ShopButton.hpp"
#include "Version.hpp"

using namespace Roboxes;

static const int DISPLAY_TIME = 5000;  // 5 seconds

// ************************************************
//                     Public

ShopButton::ShopButton(
   const String& id,
   const int& updatePeriod,
   const int& pingPeriod,
   const String& connectionId,
   const String& displayId,
   const String& powerId,
   const String& adapterId) :
      ShopSensor(id, updatePeriod, pingPeriod, connectionId, displayId, powerId, adapterId),
      pingTimer(nullptr),
      pingRequired(true)
{
}

ShopButton::ShopButton(
   MessagePtr message) :
      ShopSensor(message)
{
}

ShopButton::~ShopButton()
{
}

void ShopButton::setup()
{
   Component::setup();
   
   uid = getUid();
   
   String server = Robox::getProperties().getString("server");
   
   Display* display = getDisplay();
   if (display)
   {
      display->updateId(uid);
      
      display->updateServer(server, false);

      // Show the splash screen (temporarily).
      setDisplayMode(Display::SPLASH, DISPLAY_TIME);
   }
      
   Messaging::subscribe(this, ConnectionManager::CONNECTION);
   Messaging::subscribe(this, Power::POWER_INFO);
   Messaging::subscribe(this, Button::BUTTON_UP);
   Messaging::subscribe(this, Button::BUTTON_CLICK);
   Messaging::subscribe(this, Button::BUTTON_DOUBLE_CLICK);
   Messaging::subscribe(this, Button::BUTTON_LONG_PRESS);
   
   pingTimer = Timer::newTimer(
      getId() + ".ping",
      (pingPeriod * 1000),
      Timer::PERIODIC,
      this);
      
   if (pingTimer)
   {
      pingTimer->start();
   }
   
   webServer = new WebpageServer("webserver", 80);
   webServer->addPage(new ConfigPage(uid));
   webServer->addPage(new Webpage("/", "/index.html"));
   Robox::addComponent(webServer);
   
   if (!getDisplay())
   {
      Logger::logWarning(F("ShopButton::setup: No available display."));
   }
   
   if (!getConnection())
   {
      Logger::logWarning(F("ShopButton::setup: No available connection manager."));
   }
   
   if (!getAdapter())
   {
      Logger::logWarning(F("ShopButton::setup: No available adapter."));
   }
}

void ShopButton::handleMessage(
   MessagePtr message)
{
   // WIFI_CONNECTED, WIFI_DISCONNECTED
   if (message->getTopic() == ConnectionManager::CONNECTION)
   {
      onConnectionUpdate(message);
   }
   //  BUTTON_CLICK
   else if (message->getTopic() == Button::BUTTON_UP)
   {
      onButtonUp(message->getSource());
   }
   //  BUTTON_CLICK
   else if (message->getTopic() == Button::BUTTON_CLICK)
   {
      onButtonClick(message->getSource());
   }
   //  BUTTON_DOUBLE_CLICK
   else if (message->getTopic() == Button::BUTTON_DOUBLE_CLICK)
   {
      onButtonDoubleClick(message->getSource());
   }
   //  BUTTON_LONG_PRESS
   else if (message->getTopic() == Button::BUTTON_LONG_PRESS)
   {
      onButtonLongPress(message->getSource());
   }
   //  Power source
   else if (message->getTopic() == Power::POWER_INFO)
   {
      onPowerInfo(message);
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

void ShopButton::timeout(
   Timer* timer)
{
   if (timer == pingTimer)
   {
      if (pingRequired && isConnected())
      {
         ping();
      }
      
      pingRequired = true;
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
      Logger::logWarning(F("ShopButton::timeout: Received unexpected timeout: %s"), timer->getId().c_str());
   }
}

// ************************************************
//                    Protected

void ShopButton::onButtonUp(
   const String& buttonId)
{
   Logger::logDebug("ShopButton::onButtonUp: Button [%s] up.", buttonId.c_str());

   if (buttonId == BUTTON_B)
   {
      toggledDisplayMode();
   }
}

void ShopButton::onButtonClick(
   const String& buttonId)
{
   Logger::logDebug("ShopButton::onButtonClick: Button [%s] clicked.", buttonId.c_str());
   
   if ((buttonId == LIMIT_SWITCH) || (buttonId == BUTTON_A))
   {
      sendButtonPress(bpSINGLE_CLICK);
      pingRequired = false;

      setDisplayMode(Display::COUNT, DISPLAY_TIME);
   }
}

void ShopButton::onButtonDoubleClick(
   const String& buttonId)
{
   Logger::logDebug("ShopButton::onButtonDoubleClick: Button [%s] double-clicked.", buttonId.c_str());
   
   if ((buttonId == LIMIT_SWITCH) || (buttonId == BUTTON_A))
   {
      sendButtonPress(bpDOUBLE_CLICK);
      pingRequired = false;

      setDisplayMode(Display::COUNT, DISPLAY_TIME);
   }
}

void ShopButton::onButtonLongPress(
   const String& buttonId)
{
   Logger::logDebug("ShopButton::onButtonLongPress: Button [%s] long-pressed.", buttonId.c_str());

   if ((buttonId == LIMIT_SWITCH) || (buttonId == BUTTON_A))
   {
      sendButtonPress(bpLONG_PRESS);
      pingRequired = false;

      setDisplayMode(Display::COUNT, DISPLAY_TIME);
   }
}

bool ShopButton::ping()
{
   return (sendButtonPress(bpUNKNOWN));
}

bool ShopButton::sendButtonPress(
   const ButtonPress& buttonPress)
{
   bool success = false;

   if (isConnected())
   {
      MessagePtr message = Messaging::newMessage();
      if (message)
      {
         message->setMessageId("button");
         message->setSource(getId());
         message->setDestination(adapterId);
         message->setTransaction(uid);

         // Specify HTTP parameters.
         message->set(HttpClientAdapter::REQUEST_TYPE, HttpClientAdapter::GET);
         message->set(HttpClientAdapter::ENCODING, HttpClientAdapter::URL_ENCODING);
         message->set("subdomain", "flexscreentest");  // TODO: For local testing.  Remove.

         String url = getRequestUrl("button");
         if (url != "")
         {
            message->set("url", url);
         }

         message->set("uid", uid);
         message->set("version", VERSION);
         message->set("ipAddress", getIpAddress());

         if (buttonPress != bpUNKNOWN)
         {
            message->set("press", buttonPress);
         }

         success = Messaging::send(message);
      }
   }

   return (success);
}

void ShopButton::onServerResponse(MessagePtr message)
{
   int responseCode =  message->getInt(HttpClientAdapter::RESPONSE_CODE);
   
   Logger::logDebug(F("ShopButton::onServerResponse: Got server response for client [%s]: %d."), uid, responseCode);
   
   Display* display = getDisplay();
   if (display)
   {
      if (responseCode == 200)
      {
         totalCount = message->getInt("count");

         display->updateServer(true);
         
         display->updateCount(totalCount, count);
      }
      else
      {
         display->updateServer(false);
      }
   }
}

String ShopButton::getRequestUrl(
   const String& apiMessageId)
{
   String url = "";
   
   String server = Robox::getProperties().getString("server");
   
   if (server != "")
   {
      url = server + "/api/" + apiMessageId + "/";
   }

   return (url);
}
