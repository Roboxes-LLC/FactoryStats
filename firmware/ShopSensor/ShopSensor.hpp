#pragma once

#include "M5Defs.hpp"

#include "Connection/ConnectionManager.hpp"
#include "Messaging/Adapter.hpp"
#include "Messaging/Component.hpp"
#include "Messaging/ComponentFactory.hpp"
#include "Timer/Timer.hpp"
#include "Timer/TimerListener.hpp"
#include "WebServer/WebpageServer.hpp"

#include "BreakManager.hpp"
#include "Display.hpp"
#include "Power.hpp"

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
      const String& adapterId,
      const String& breakManagerId);
      
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

   Display* getDisplay() const;
   
   Power* getPower() const;
   
   Adapter* getAdapter() const;
   
   BreakManager* getBreakManager() const;

   void setDisplayMode(
      const Display::DisplayMode& displayMode,
      const int& duration = 0);
      
   void toggledDisplayMode();      

   void rotateDisplay();

   void onConnectionUpdate(
      MessagePtr message);

   void onButtonUp(
      const String& buttonId);

   void onSoftButtonUp(
      const int& buttonId);
      
   void onButtonLongPress(
      const String& buttonId);

   void onCountChanged(
      const int& deltaCount);

   virtual void onPowerInfo(
      MessagePtr message);      
      
   virtual bool sendUpdate();

   virtual void onServerResponse(
      MessagePtr message);

   void setServerAvailable(
      const bool& serverAvailable);

   bool sendServerStatus();
      
   static bool isConnected();
   
   static String getIpAddress();
   
   static String getUid();

   // Break handling

   bool isOnBreak() const;

   void confirmBreak(
      const int& confirmedBreakId) const;

   bool hasPendingBreak() const;

   void clearPendingBreak() const;

   String getPendingBreakCode() const;

   void toggleBreak() const;

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

   String breakManagerId;

   // Status

   bool serverAvailable;

   int count;
   
   int totalCount;
   
   long updateCount;

   int stationId;

   String stationLabel;

   String configuredBreakCode;

   String pendingBreakCode;

   int breakId;  // Station is considered paused if breakId != NO_BREAK_ID;
};

REGISTER(ShopSensor, ShopSensor)
