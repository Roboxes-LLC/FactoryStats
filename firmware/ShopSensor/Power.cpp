#include "Logger/Logger.hpp"
#include "M5Defs.hpp"
#include "Power.hpp"
#include "Robox.hpp"

const String Power::POWER_INFO = "POWER_INFO";
const String Power::POWER_UPDATE = "POWER_UPDATE";
const String Power::POWER_SOURCE = "POWER_SOURCE";

static const int UPDATE_PERIOD = 10000;  // 10 seconds
static const int POLL_PERIOD = 1000;  // 1 second

// *****************************************************************************
//                                   Public

Power::Power(
   const String& id,
   const String& displayId) :
      Component(id),
      displayId(displayId),
      batteryLevel(0),
      isUsbPowerSource(false),
      isBatteryCharging(false),
      updateTimer(nullptr),
      pollTimer(nullptr)
{

}

Power::Power(
   MessagePtr message) :
      Component(message),
      displayId(message->getString("display")),
      batteryLevel(0),
      isUsbPowerSource(false),
      isBatteryCharging(false),
      updateTimer(nullptr),
      pollTimer(nullptr)
{

}

Power::~Power()
{

}

void Power::setup()
{
   updateTimer = Timer::newTimer(
      getId() + ".update",
      UPDATE_PERIOD,
      Timer::PERIODIC,
      this);

   if (updateTimer)
   {
      updateTimer->start();
   }

   pollTimer = Timer::newTimer(
      getId() + ".powerSource",
      POLL_PERIOD,
      Timer::PERIODIC,
      this);

   if (pollTimer)
   {
      pollTimer->start();
   }

   Component::setup();
}

void Power::loop()
{
   Component::loop();
}

void Power::handleMessage(
   MessagePtr message)
{
   Component::handleMessage(message);
}

void Power::timeout(
   Timer* timer)
{
   if (timer == pollTimer)
   {
      bool wasUsbPowerSource = isUsbPowerSource;

      update();

      if (isUsbPowerSource != wasUsbPowerSource)
      {
         sendPowerSourceUpdate();
      }
   }
   else if (timer == updateTimer)
   {
      sendPowerUpdate();
   }
}

int Power::getBatteryLevel()
{
   return (batteryLevel);
}

bool Power::isUsbPower()
{
   return (isUsbPowerSource);
}

void Power::powerOff()
{
   M5.Axp.PowerOff();
}

// *****************************************************************************
//                                   Private

void Power::update()
{
   isUsbPowerSource = (M5.Axp.GetIusbinData() > 0);
   
   if (isUsbPowerSource)
   {
      isBatteryCharging = (M5.Axp.GetBatChargeCurrent() > 0);
      
      batteryLevel = 0;  // TODO: How to determine when charging? 
   }
   else
   {
      isBatteryCharging = false;
      
      int prevBatteryLevel = batteryLevel; 
      
      float batVoltage = M5.Axp.GetBatVoltage();
      batteryLevel = (batVoltage < 3.2) ? 0 : ( batVoltage - 3.2 ) * 100;
      batteryLevel = (prevBatteryLevel == 0) ? batteryLevel : min(prevBatteryLevel, batteryLevel);  // Smooth out fluctuations on discharge. 
   }
   
   Display* display = getDisplay();
   if (display)
   {
      display->updatePower(batteryLevel, isUsbPowerSource, isBatteryCharging);  
   }
}

bool Power::sendPowerUpdate()
{
   bool success = false;

   MessagePtr message = Messaging::newMessage();

   if (message)
   {
      message->setTopic(POWER_INFO);
      message->setMessageId(POWER_UPDATE);
      message->setSource(getId());
      message->set("batteryLevel", batteryLevel);
      message->set("isUsbPower", isUsbPowerSource);
      message->set("isCharging", isBatteryCharging);

      success = Messaging::publish(message);
   }

   return (success);
}

bool Power::sendPowerSourceUpdate()
{
   bool success = false;

   MessagePtr message = Messaging::newMessage();

   if (message)
   {
      message->setTopic(POWER_INFO);
      message->setMessageId(POWER_SOURCE);
      message->setSource(getId());
      message->set("isUsbPower", isUsbPowerSource);
      message->set("isCharging", isBatteryCharging);

      success = Messaging::publish(message);
   }

   return (success);
}


Display* Power::getDisplay()
{
   static Display* display = 0;
   
   if (!display)
   {
      display = (Display*)Robox::getComponent(displayId);
   }
   
   return (display);
}