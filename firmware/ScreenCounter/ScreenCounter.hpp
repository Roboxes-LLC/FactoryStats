#include <RFC.h>

#include "Messaging/Component.hpp"
#include "Timer/TimerListener.hpp"

class ScreenCounter : public Component, TimerListener
{
  
public:  

   // Constructor.
   ScreenCounter(
      const String& id);

   // Destructor.
   virtual ~ScreenCounter();

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

   void onDoubleClick(
      const String& buttonId);

   void onLongPress(
      const String& buttonId);

private:

   static void getMacAddress(
      char macAddress[18]);

   char macAddress[18];

   String serverUrl;

   Timer* doubleClickTimer;

   String lastPressedButtonId;
};
