#include <RFC.h>

#include "Component/Led.hpp"
#include "Messaging/ComponentFactory.hpp"
#include "Timer/Timer.hpp"

class StatusLed : public Led, TimerListener
{

public:

   StatusLed(
      const String& id,
      const int& pin);

   StatusLed(
      MessagePtr message);

   void onPowerOn();

   void onWifiConnected();

   void onCounterIncremented();

   void onCounterDecremented();

   void onFactoryReset();

   void timeout(
      Timer* timer);

private:

};

REGISTER(StatusLed, StatusLed)
