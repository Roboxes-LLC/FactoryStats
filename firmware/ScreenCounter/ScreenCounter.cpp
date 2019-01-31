#include "Messaging.hpp"
#include "Properties.hpp"
#include "ScreenCounter.hpp"
#include "StatusLed.hpp"
#include "ToastBot.hpp"
#include "WifiBoard.hpp"

ScreenCounter::ScreenCounter(
   const String& id) :
   Component(id)
{
  
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
      onButtonUp();
      
      Messaging::freeMessage(message);
   }
   // buttonLongPress
   else if (message->getTopic() == "buttonLongPress")
   {
      onLongPress();
      
      Messaging::freeMessage(message);
   }
   else
   {
      Component::handleMessage(message);
   }
}

void ScreenCounter::timeout(
   Timer* timer)
{
  ToastBot::factoryReset();
}

void ScreenCounter::onButtonUp()
{
   Logger::logDebug("ScreenCounter::onButtonUp: Button pressed");

   MessagePtr message = Messaging::newMessage();
   if (message)
   {
      message->setDestination("http");
      message->setMessageId("update");
      message->set("count", 1);
      message->set("stationId", ToastBot::getId());
      Messaging::send(message);

       // TODO: Send in reponse to HTTP 200 response.
       if (WifiBoard::getBoard()->isConnected() == true)
       {
          StatusLed* led = (StatusLed*)ToastBot::getComponent("led");
          if (led)
          {
             led->onCounterUpdated();
          }
       }
   }
}

void ScreenCounter::onLongPress()
{
   StatusLed* led = (StatusLed*)ToastBot::getComponent("led");
   if (led)
   {
      led->onCounterUpdated();
   }

   // Factory reset after delay.
   Timer* timer = Timer::newTimer(getId() + ".factoryReset", 5000, Timer::ONE_SHOT, this);
   timer->start();
}
