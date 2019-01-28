#include "Component/Led.hpp"
#include "Timer.hpp"

class StatusLed : public Led, TimerListener
{

public:

   StatusLed(
      const String& id,
      const int& pin);

   void onPowerOn();

   void onWifiConnected();

   void onCounterUpdated();

   void onFactoryReset();

   void timeout(
      Timer* timer);

private:

};
