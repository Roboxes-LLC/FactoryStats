#include "Messaging.hpp"
#include "Properties.hpp"
#include "ScreenCounter.hpp"
#include "ToastBot.hpp"

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
  Messaging::subscribe(this, "buttonDown");
  Messaging::subscribe(this, "buttonLongPress");
}

void ScreenCounter::handleMessage(
   MessagePtr message)
{
   //  buttonDown
   if (message->getTopic() == "buttonDown")
   {
      onButtonDown();
      
      Messaging::freeMessage(message);
   }
   // buttonLongPress
   else if (message->getTopic() == "buttonLongPress")
   {
      Messaging::freeMessage(message);
   }
   else
   {
      Component::handleMessage(message);
   }
}

void ScreenCounter::onButtonDown()
{
   Logger::logDebug("ScreenCounter::onButtonDown: Button pressed");
   
   Properties& properties = ToastBot::getProperties();

   if (properties.isSet("server.url"))
   {
      String url = properties.getString("server.url");
      
      MessagePtr message = Messaging::newMessage();
      if (message)
      {
         message->setDestination("httpAdapter");
         message->set("url", url);
         message->set("action", "update");
         message->set("count", 1);
         message->set("stationId", ToastBot::getId());
         Messaging::send(message);
      }
   }
}

