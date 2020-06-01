#include "ScreenCounter.hpp"

#include "Board/WifiBoard.hpp"
#include "Logger/Logger.hpp"
#include "Messaging/Messaging.hpp"
#include "Properties/Properties.hpp"
#include "Robox.hpp"
#include "StatusLed.hpp"

static const String COUNT_BUTTON = "countButton";
static const String UNDO_BUTTON = "undoButton";

ScreenCounter::ScreenCounter(
   const String& id) :
   Component(id),
   doubleClickTimer(0)
{
   getMacAddress(macAddress);
}

ScreenCounter::~ScreenCounter()
{
  
}

void ScreenCounter::setup()
{
   Component::setup();
      
   Messaging::subscribe(this, "buttonUp");
   Messaging::subscribe(this, "buttonLongPress");

   Properties& properties = Robox::getProperties();
   serverUrl = properties.getString("server");

   // TODO: Have StatusLed react to broadcast "wifiConnected" message.
   if (WifiBoard::getBoard()->isConnected() == true)
   {
      StatusLed* led = (StatusLed*)Robox::getComponent("led");
      if (led)
      {
         led->onWifiConnected();
      }
   }  
}

void ScreenCounter::handleMessage(
   MessagePtr message)
{
   //  buttonUp
   if (message->getTopic() == "buttonUp")
   {
      lastPressedButtonId = message->getSource();

      if (doubleClickTimer == 0)
      {
         // Start a "double click" timer.
         // If the user presses the button again before it expires, consider it a double click.
         doubleClickTimer = Timer::newTimer(getId() + ".doubleClick", 500, Timer::ONE_SHOT, this);
         if (doubleClickTimer)
         {
            doubleClickTimer->start();
         }
      }
      else
      {
         Timer::freeTimer(doubleClickTimer);
         doubleClickTimer = 0;
                 
         onDoubleClick(lastPressedButtonId);
      }
   }
   // buttonLongPress
   else if (message->getTopic() == "buttonLongPress")
   {
      onLongPress(message->getSource());
    }
   else
   {
      Component::handleMessage(message);
   }

    Messaging::freeMessage(message);
}

void ScreenCounter::timeout(
   Timer* timer)
{
   if (timer->getId().indexOf("factoryReset") != -1)
   {
      Robox::factoryReset();
   }
   else if (timer->getId().indexOf("doubleClick") != -1)
   {
      doubleClickTimer = 0;
      onButtonUp(lastPressedButtonId);
   }
   else
   {
      Logger::logWarning(F("ScreenCounter::timeout: Received unexpected timeout: %s"), timer->getId().c_str());
   }
}

void ScreenCounter::onButtonUp(
   const String& buttonId)
{
   Logger::logDebug("ScreenCounter::onButtonUp: Button [%s] pressed.", buttonId.c_str());

   int count = (buttonId == COUNT_BUTTON) ? 1 : (buttonId == UNDO_BUTTON) ? -1 : 0;

   MessagePtr message = Messaging::newMessage();
   if (message)
   {
      message->setMessageId("update");
      message->setDestination("http");

      if (serverUrl != "")
      {
         message->set("url", serverUrl);
      }
               
      message->set("macAddress", macAddress);
      message->set("count", count);

      Messaging::send(message);

      // TODO: Send in reponse to HTTP 200 response.
      if (WifiBoard::getBoard()->isConnected() == true)
      {
         StatusLed* led = (StatusLed*)Robox::getComponent("led");
         if (led)
         {
            if (count > 0)
            {
               led->onCounterIncremented();
            }
            else if (count < 0)
            {
               led->onCounterDecremented();
            }
         }
      }
   }
}

void ScreenCounter::onDoubleClick(
   const String& buttonId)
{
   Logger::logDebug("ScreenCounter::onDoubleClick: Button [%s] double-clicked.", buttonId.c_str());
}

void ScreenCounter::onLongPress(
   const String& buttonId)
{
   Logger::logDebug("ScreenCounter::onLongPress: Button [%s] long-press.", buttonId.c_str());

   if (buttonId == UNDO_BUTTON)
   {
      StatusLed* led = (StatusLed*)Robox::getComponent("led");
      if (led)
      {
         led->onFactoryReset();
      }

      // Factory reset after delay.
      Timer* timer = Timer::newTimer(getId() + ".factoryReset", 5000, Timer::ONE_SHOT, this);
      if (timer)
      {
         timer->start();
      }
   }
}

void ScreenCounter::getMacAddress(
   char macAddress[18])
{
   // Get the MAC address.
   unsigned char mac[6] = {0, 0, 0, 0, 0, 0};
   WifiBoard::getBoard()->getMacAddress(mac);

   sprintf(macAddress, "%02X:%02X:%02X:%02X:%02X:%02X", mac[0], mac[1], mac[2], mac[3], mac[4], mac[5]);
}
