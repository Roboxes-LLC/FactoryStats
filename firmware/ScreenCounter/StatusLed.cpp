#include "StatusLed.hpp"

const String SLOW_BLINK = "--__";

const String FAST_BLINK = "-_";

StatusLed::StatusLed(
   const String& id,
   const int& pin) :
      Led(id, pin)
{
}

StatusLed::StatusLed(
   MessagePtr message) :
      Led(message)
{
}

void StatusLed::onPowerOn()
{
   blink(SLOW_BLINK);
}

void StatusLed::onWifiConnected()
{
   setBrightness(100);
}

void StatusLed::onCounterUpdated()
{
   blink(SLOW_BLINK);

   Timer* timer = Timer::newTimer(getId() + ".timer", 2000, Timer::ONE_SHOT, this);
   timer->start();
}

void StatusLed::onFactoryReset()
{
   blink(FAST_BLINK);
}

void StatusLed::timeout(
   Timer* timer)
{
   onWifiConnected();
}
