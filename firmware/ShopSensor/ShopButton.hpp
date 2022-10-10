#pragma once

#include "ShopSensor.hpp"
#include "ButtonDefs.hpp"

class ShopButton : public ShopSensor
{
  
public:  

   // Constructor.
   ShopButton(
      const String& id,
      const int& updatePeriod,
      const int& pingPeriod,
      const String& connectionId,
      const String& displayId,
      const String& powerId,
      const String& adapterId);
      
   // Constructor.
   ShopButton(
      MessagePtr message);

   // Destructor.
   virtual ~ShopButton();

   virtual void setup();

   // This operation handles a message directed to this sensor.
   virtual void handleMessage(
      // The message to handle.
      MessagePtr message);
      
   virtual void timeout(
      Timer* timer);      

protected:

   void onButtonUp(
      const String& buttonId);

   void onButtonClick(
      const String& buttonId);
   
   void onButtonDoubleClick(
      const String& buttonId);
   
   void onButtonLongPress(
      const String& buttonId);

   virtual bool ping();

   virtual bool sendButtonPress(
      const ButtonPress& buttonPress);

   virtual void onServerResponse(
      MessagePtr message);      
   
   static String getRequestUrl(
      const String& apiMessageId);

   // **************************************************************************

   Timer* pingTimer;

   bool pingRequired;
};

REGISTER(ShopButton, ShopButton)
