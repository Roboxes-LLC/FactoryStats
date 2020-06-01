#include "PartCounter.hpp"

#include "Board/WifiBoard.hpp"
#include "Logger/Logger.hpp"
#include "Messaging/Messaging.hpp"
#include "Properties/Properties.hpp"
#include "Robox.hpp"
#include "StatusLed.hpp"

static const String COUNT_BUTTON = "countButton";

PartCounter::PartCounter(
   const String& id,
   const int& batchTime) :
   Component(id),
   batchTimer(0)
{
   getMacAddress(macAddress);

   this->batchTime = batchTime;
}

PartCounter::~PartCounter()
{
  
}

void PartCounter::setup()
{
   Component::setup();
      
   Messaging::subscribe(this, "buttonUp");

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

void PartCounter::handleMessage(
   MessagePtr message)
{
   //  buttonUp
   if (message->getTopic() == "buttonUp")
   {
      onButtonUp(message->getSource());
   }
   else
   {
      Component::handleMessage(message);
   }

    Messaging::freeMessage(message);
}

void PartCounter::timeout(
   Timer* timer)
{
   if (timer->getId().indexOf("batch") != -1)
   {
      if (sendCount())
      {
         batchCount = 0;
      }

      batchTimer = 0;
   }
   else
   {
      Logger::logWarning(F("PartCounter::timeout: Received unexpected timeout: %s"), timer->getId().c_str());
   }
}

void PartCounter::onButtonUp(
   const String& buttonId)
{
   Logger::logDebug("PartCounter::onButtonUp: Button [%s] pressed.", buttonId.c_str());
   
   batchCount++;

   StatusLed* led = (StatusLed*)Robox::getComponent("led");
   if (led)
   {
      led->onCounterIncremented();
   }

   if (batchTimer == 0)
   {
      // Start a "batch" timer.
      // Following the expiration of this timer, we'll send a batch count to the server.
      batchTimer = Timer::newTimer(getId() + ".batch", batchTime, Timer::ONE_SHOT, this);
      if (batchTimer)
      {
         batchTimer->start();
      }
   }
}

bool PartCounter::sendCount()
{
   bool success = false;
   
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
      message->set("count", batchCount);

      success = Messaging::send(message);

      if (success)
      {
         Logger::logDebug(F("PartCounter::sendCount: Sent count [%d] to server."), batchCount);
      }
      else
      {
         Logger::logWarning(F("PartCounter::sendCount: Failed to send count [%d] to server."), batchCount);
      }
   }   

   return (success);
}

void PartCounter::getMacAddress(
   char macAddress[18])
{
   // Get the MAC address.
   unsigned char mac[6] = {0, 0, 0, 0, 0, 0};
   WifiBoard::getBoard()->getMacAddress(mac);

   sprintf(macAddress, "%02X:%02X:%02X:%02X:%02X:%02X", mac[0], mac[1], mac[2], mac[3], mac[4], mac[5]);
}
