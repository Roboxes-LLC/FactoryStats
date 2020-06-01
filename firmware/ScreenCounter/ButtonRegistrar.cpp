#include <Robox.h>

#include "ButtonRegistrar.hpp"
#include "Robox.hpp"
#include "Board/WifiBoard.hpp"

ButtonRegistrar::ButtonRegistrar(
   const String& id,
   const String& adapterId,
   const int& refreshPeriod) :
      Registrar(id, adapterId, refreshPeriod)
{
}

ButtonRegistrar::ButtonRegistrar(
   MessagePtr message) :
      Registrar(message)
{
}

void ButtonRegistrar::setup()
{
   Registrar::setup();
      
   Properties& properties = Robox::getProperties();

   serverUrl = properties.getString("server");
}

ButtonRegistrar::~ButtonRegistrar()
{
}

void ButtonRegistrar::pingRegistry()
{
   if (WifiBoard::getBoard())
   {
      MessagePtr message = Messaging::newMessage();

      if (message)
      {
         message->setMessageId("registerButton");
         message->setDestination(getAdapterId());

         if (serverUrl != "")
         {
            message->set("url", serverUrl);
         }

         // Get the MAC address.
         unsigned char mac[6] = {0, 0, 0, 0, 0, 0};
         WifiBoard::getBoard()->getMacAddress(mac);
         char macAddress[18];
         sprintf(macAddress, "%02X:%02X:%02X:%02X:%02X:%02X", mac[0], mac[1], mac[2], mac[3], mac[4], mac[5]);

         message->set("ipAddress", WifiBoard::getBoard()->getIpAddress());
         message->set("macAddress", macAddress);

         Messaging::send(message);
      }
   }
}
