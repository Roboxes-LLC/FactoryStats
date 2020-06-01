#include "Messaging/Component.hpp"
#include "Timer/TimerListener.hpp"

class PartCounter : public Component, TimerListener
{
  
public:  

   // Constructor.
   PartCounter(
      const String& id,
      const int& batchTime);

   // Destructor.
   virtual ~PartCounter();

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

   bool sendCount();

private:

   static void getMacAddress(
      char macAddress[18]);

   char macAddress[18];

   String serverUrl;

   int batchTime;

   int batchCount;

   Timer* batchTimer;
};
