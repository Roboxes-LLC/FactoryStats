#pragma once

#include "Connection/ConnectionManager.hpp"
#include "Messaging/Adapter.hpp"
#include "Messaging/Component.hpp"
#include "Messaging/ComponentFactory.hpp"
#include "Timer/Timer.hpp"
#include "Timer/TimerListener.hpp"
#include "WebServer/WebpageServer.hpp"

#include "Display.hpp"

// Component names
const String LIMIT_SWITCH = "limitSwitch";
const String BUTTON_A = "buttonA";
const String BUTTON_B = "buttonB";

class ShopSensor : public Component, TimerListener
{
  
public:  

   // Constructor.
   ShopSensor(
      const String& id,
      const int& updatePeriod,
      const String& connectionId,
      const String& displayId,
      const String& adapterId);
      
   // Constructor.
   ShopSensor(
      MessagePtr message);

   // Destructor.
   virtual ~ShopSensor();

   virtual void setup();

   // This operation handles a message directed to this sensor.
   virtual void handleMessage(
      // The message to handle.
      MessagePtr message);
      
   virtual void timeout(
      Timer* timer);      

protected:

   ConnectionManager* getConnection();

   Display* getDisplay();
   
   Adapter* getAdapter();
   
   void onConnectionUpdate(
      MessagePtr message);

   void onButtonUp(
      const String& buttonId);
      
   void onButtonLongPress(
      const String& buttonId);

   virtual void onServerResponse(
      MessagePtr message);      
      
   virtual bool sendUpdate();
      
   static bool isConnected();
   
   static String getIpAddress();
   
   static String getUid();
   
   static String getRequestUrl(
      const String& apiMessageId);

   // **************************************************************************

   // Subcomponents

   Timer* updateTimer;
   
   WebpageServer* webServer;
   
   // Config
   
   String uid;
   
   int updatePeriod;
   
   String connectionId;
   
   String displayId;
   
   String adapterId;
   
   // Status

   int count;
   
   int totalCount;
};

REGISTER(ShopSensor, ShopSensor)
