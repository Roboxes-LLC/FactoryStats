#include "Messaging.hpp"
#include "Properties.hpp"
#include "ScreenCounter.hpp"
#include "StatusLed.hpp"
#include "ToastBot.hpp"
#include "WifiBoard.hpp"

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
  Messaging::subscribe(this, "buttonUp");
  Messaging::subscribe(this, "buttonLongPress");

  // TODO: Have StatusLed react to broadcast "wifiConnected" message.
  if (WifiBoard::getBoard()->isConnected() == true)
  {
     StatusLed* led = (StatusLed*)ToastBot::getComponent("led");
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
                 
         onDoubleClick();  
      }
   }
   // buttonLongPress
   else if (message->getTopic() == "buttonLongPress")
   {
      onLongPress();
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
      ToastBot::factoryReset();
   }
   else if (timer->getId().indexOf("doubleClick") != -1)
   {
      doubleClickTimer = 0;
      onButtonUp();
   }
   else
   {
      Logger::logWarning(F("ScreenCounter::timeout: Received unexpected timeout: %s"), timer->getId().c_str());
   }
}

void ScreenCounter::onButtonUp()
{
   Logger::logDebug("ScreenCounter::onButtonUp: Button pressed");

   MessagePtr message = Messaging::newMessage();
   if (message)
   {
      message->setDestination("http");
      message->setMessageId("update");
      message->set("macAddress", macAddress);
      message->set("count", 1);

      Messaging::send(message);

      // TODO: Send in reponse to HTTP 200 response.
      if (WifiBoard::getBoard()->isConnected() == true)
      {
         StatusLed* led = (StatusLed*)ToastBot::getComponent("led");
         if (led)
         {
            led->onCounterDecremented();
         }
      }
   }
}

void ScreenCounter::onDoubleClick()
{
   Logger::logDebug("ScreenCounter::onDoubleClick: Button double-clicked");

   MessagePtr message = Messaging::newMessage();
   if (message)
   {
      message->setDestination("http");
      message->setMessageId("update");
      message->set("macAddress", macAddress);
      message->set("count", -1);
      
      Messaging::send(message);

       // TODO: Send in reponse to HTTP 200 response.
       if (WifiBoard::getBoard()->isConnected() == true)
       {
          StatusLed* led = (StatusLed*)ToastBot::getComponent("led");
          if (led)
          {
             led->onCounterIncremented();
          }
       }
   }
}

void ScreenCounter::onLongPress()
{
   StatusLed* led = (StatusLed*)ToastBot::getComponent("led");
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

void ScreenCounter::getMacAddress(
   char macAddress[18])
{
   // Get the MAC address.
   unsigned char mac[6] = {0, 0, 0, 0, 0, 0};
   WifiBoard::getBoard()->getMacAddress(mac);

   sprintf(macAddress, "%02X:%02X:%02X:%02X:%02X:%02X", mac[0], mac[1], mac[2], mac[3], mac[4], mac[5]);
}
