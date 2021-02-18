#pragma once

#include <RFC.h>

#include "Messaging/Component.hpp"
#include "Messaging/ComponentFactory.hpp"
#include "Timer/Timer.hpp"
#include "Timer/TimerListener.hpp"
#include "ConnectionManager.hpp"
#include "Display.hpp"

class Diagnostics : public Component, TimerListener
{

public:
   
   Diagnostics(
      const String& id,
      const int& updatePeriod,
      const String& connectionId,
      const String& displayId);
      
   Diagnostics(
      MessagePtr message);      
   
   virtual ~Diagnostics();   

   virtual void setup();

   virtual void loop();

   virtual void handleMessage(
      // The message to handle.
      MessagePtr message);
      
   virtual void timeout(
      Timer* timer);      
      
protected:

   void updateDiagnostics();

   String getMacAddress();
   
   int getUpTime();

   int getFreeMemory();
   
   ConnectionManager* getConnection();

   Display* getDisplay();
   
private:   

   Timer* updateTimer;

   int updatePeriod;
   
   String connectionId;
   
   String displayId;
};

REGISTER(Diagnostics, Diagnostics)
