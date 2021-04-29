#pragma once

#include "ShopSensor.hpp"

class ShopServer : public ShopSensor
{

public:

   // Constructor.
   ShopServer(
      const String& id,
      const int& updatePeriod,
      const int& pingPeriod,
      const String& connectionId,
      const String& displayId,
      const String& powerId,
      const String& adapterId,
      const String& clientAdapterId);

   // Constructor.
   ShopServer(
      MessagePtr message);

   // Destructor.
   virtual ~ShopServer();

   // This operation handles a message directed to this sensor.
   virtual void handleMessage(
	  // The message to handle.
	  MessagePtr message);

protected:

   virtual void onServerResponse(
      MessagePtr message);

   virtual bool sendUpdate();

   virtual void onSensorUpdate(
      MessagePtr message);

private:

   struct ClientInfo
   {
      // Addressing
      String source;
      String sourceIpAddress;

      // Sensor data
	   String uid;
	   String ipAddress;
	   String version;
	   int count;

	   // Client status
	   bool current;

	   inline ClientInfo()
	   {
	      source = "";
	      sourceIpAddress = "";
	      uid = "";
	      ipAddress = "";
	      version = "";
	      count = 0;
	      current = false;
	   }

	   inline bool operator==(const ClientInfo& rhs) const
      {
	      return (memcmp(this, &rhs, sizeof(ClientInfo)) == 0);
      }
   };

   typedef Map<String, ClientInfo> ClientMap;

   String clientAdapterId;

   ClientMap clients;
};

REGISTER(ShopServer, ShopServer)
