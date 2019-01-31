#include "Component/Led.hpp"
#include "ComponentFactory.hpp"
#include "Timer.hpp"

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

   void onCounterUpdated();

   void onFactoryReset();

   void timeout(
      Timer* timer);

private:

};

REGISTER(StatusLed, StatusLed)
