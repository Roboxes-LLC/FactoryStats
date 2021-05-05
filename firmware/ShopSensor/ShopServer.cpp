#include "Adapter/HttpClientAdapter.hpp"
#include "Logger/Logger.hpp"

#include "ShopServer.hpp"

ShopServer::ShopServer(
   const String& id,
   const int& updatePeriod,
   const int& pingPeriod,
   const String& connectionId,
   const String& displayId,
   const String& powerId,
   const String& adapterId,
   const String& clientAdapterId) :
      ShopSensor(id, updatePeriod, pingPeriod, connectionId, displayId, powerId, adapterId),
      clientAdapterId(clientAdapterId)
{

}

ShopServer::ShopServer(
   MessagePtr message) :
      ShopSensor(message),
      clientAdapterId(message->getString("clientAdapter"))
{

}

ShopServer::~ShopServer()
{

}

void ShopServer::handleMessage(
  MessagePtr message)
{
   if (message->getMessageId() == "sensor")
   {
      onSensorUpdate(message);

      Messaging::freeMessage(message);
   }
   else
   {
      ShopSensor::handleMessage(message);
   }
}

void ShopServer::onServerResponse(
   MessagePtr message)
{
   String uid = message->getTransaction();

   if (uid == this->uid)
   {
      ShopSensor::onServerResponse(message);
   }
   else
   {
      int responseCode =  message->getInt(HttpClientAdapter::RESPONSE_CODE);

      Logger::logDebug(F("ShopServer::onServerResponse: Got server response for client [%s]: %d."), uid, responseCode);

      ClientMap::Iterator findIt = clients.find(uid);

      if (findIt != clients.end())
      {
         ClientInfo& clientInfo = findIt->second;

         // Forward the reply message to the sensor.
         MessagePtr replyMessage = Messaging::copyMessage(message);
         replyMessage->setDestination(clientInfo.source);
         replyMessage->set("destination.ipAddress", clientInfo.sourceIpAddress);

         Messaging::send(replyMessage);
      }
      else
      {
         Logger::logWarning(F("ShopServer::sendUpdate: Failed to find client [%s]."), uid);
      }
   }
}

bool ShopServer::sendUpdate()
{
   bool success = true;

   // Send updates for all reported clients.
   for (ClientMap::Iterator it = clients.begin(); it != clients.end(); it++)
   {
      ClientInfo& clientInfo = it->second;

      if (clientInfo.current)
      {
         MessagePtr message = Messaging::newMessage();
         if (message)
         {
            message->setMessageId("sensor");
            message->setSource(getId());
            message->setDestination(adapterId);
            message->setTransaction(clientInfo.uid);

            // Specify HTTP parameters.
            message->set(HttpClientAdapter::REQUEST_TYPE, HttpClientAdapter::POST);
            message->set(HttpClientAdapter::ENCODING, HttpClientAdapter::JSON_ENCODING);
            message->set("subdomain", "flexscreentest");  // TODO: For local testing.  Remove.

            String url = getRequestUrl("sensor");
            if (url != "")
            {
               message->set(HttpClientAdapter::URL, url);
            }

            message->set("uid", clientInfo.uid);
            message->set("ipAddress", clientInfo.ipAddress);
            message->set("version", clientInfo.version);
            message->set("count", clientInfo.count);

            success &= Messaging::send(message);

            if (success)
            {
               Logger::logDebug(F("ShopServer::sendUpdate: Sent count [%d] to server for sensor [%s]."), clientInfo.count, clientInfo.uid);

               clientInfo.count = 0;
               clientInfo.current = false;
            }
            else
            {
               Logger::logWarning(F("ShopServer::sendUpdate: Failed to send count [%d] to server for sensor [%s]."), clientInfo.count, clientInfo.uid);
            }
         }
      }
   }

   // Send our own update.
   success &= ShopSensor::sendUpdate();

   return (success);
}

void ShopServer::onSensorUpdate(
   MessagePtr message)
{
   String uid = message->getString("uid");

   ClientInfo& clientInfo = clients[uid];

   clientInfo.source = message->getSource();
   clientInfo.sourceIpAddress = message->getString("source.ipAddress");

   clientInfo.uid = uid;
   clientInfo.ipAddress = message->getString("ipAddress");
   clientInfo.count += message->getInt("count");

   clientInfo.current = true;

   Logger::logDebug(F("ShopServer::onSensorUpdate: Sensor [%s] update: count = %d."), clientInfo.uid, clientInfo.count);

}
