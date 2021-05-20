#pragma once

#include "Connection/ConnectionManager.hpp"
#include "Messaging/Adapter.hpp"
#include "Messaging/Component.hpp"
#include "Messaging/ComponentFactory.hpp"
#include "Timer/Timer.hpp"
#include "Timer/TimerListener.hpp"
#include "WebServer/WebpageServer.hpp"

#include "Display.hpp"
#include "Power.hpp"

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
      const int& pingPeriod,
      const String& connectionId,
      const String& displayId,
      const String& powerId,
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
   
   Power* getPower();
   
   Adapter* getAdapter();
   
   void setDisplayMode(
      const Display::DisplayMode& displayMode,
      const int& duration = 0);
      
   void toggledDisplayMode();      
         
   void onConnectionUpdate(
      MessagePtr message);

   void onButtonUp(
      const String& buttonId);
      
   void onButtonLongPress(
      const String& buttonId);

   virtual void onServerResponse(
      MessagePtr message);      
      
   virtual void onPowerInfo(
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
   
   Timer* displayTimer;
   
   WebpageServer* webServer;
   
   // Config
   
   String uid;
   
   // The period (in milliseconds) between server updates.
   // Note: Only updated if count > 0.
   int updatePeriod;
   
   // The period (in update counts) between mandatory server updates. 
   int pingPeriod;
   
   String connectionId;
   
   String displayId;
   
   String powerId;
   
   String adapterId;
   
   // Status

   int count;
   
   int totalCount;
   
   long updateCount;
};

REGISTER(ShopSensor, ShopSensor)
