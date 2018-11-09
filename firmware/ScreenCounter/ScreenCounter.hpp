#include "Component.hpp"

class ScreenCounter : public Component
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

protected:

   void onButtonDown();      
};
