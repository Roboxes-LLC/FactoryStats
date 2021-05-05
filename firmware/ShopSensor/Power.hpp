#pragma once

#include <RFC.h>

#include "Messaging/Component.hpp"
#include "Messaging/ComponentFactory.hpp"
#include "Timer/Timer.hpp"
#include "Timer/TimerListener.hpp"
#include "Display.hpp"

class Power : public Component, TimerListener
{

public:

   // Messaging constants.
   static const String POWER_INFO;  // topic
   static const String POWER_UPDATE;
   static const String POWER_SOURCE;

   Power(
      const String& id,
      const String& displayId);

   Power(
      MessagePtr message);

   virtual ~Power();

   virtual void setup();

   virtual void loop();

   virtual void handleMessage(
      MessagePtr message);

   virtual void timeout(
      Timer* timer);

   int getBatteryLevel();

   bool isUsbPower();
   
   bool isCharging();
   
   void powerOff();

private:

   void update();

   bool sendPowerUpdate();

   bool sendPowerSourceUpdate();
   
   Display* getDisplay();
   
   String displayId;

   int batteryLevel;

   bool isUsbPowerSource;
   
   bool isBatteryCharging;

   Timer* updateTimer;

   Timer* pollTimer;
};

REGISTER(Power, Power)
