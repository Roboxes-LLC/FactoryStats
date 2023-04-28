#pragma once

#include "Messaging/Component.hpp"
#include "Messaging/ComponentFactory.hpp"
#include "Timer/Timer.hpp"

class Tester : public Component, TimerListener
{

public:

   enum TestAction
   {
      WAIT,
      INCREMENT,
      DECREMENT,
      PAUSE,
      BREAK,
      RESUME,
      INFO,
      HOME,
      PREVIOUS,
      NEXT,
   };

   // Constructor.
   Tester(
      const String& id);

   // Constructor.
   Tester(
      MessagePtr message);

   // Destructor.
   virtual ~Tester();

   // **************************************************************************
   // Component interface

   virtual void setup();

   // This operation handles a message directed to this sensor.
   virtual void handleMessage(
      // The message to handle.
      MessagePtr message);

   // **************************************************************************
   // TimerListener interface

   virtual void timeout(
      Timer* timer);

private:

   void start();

   void stop();

   void runAction(
      const TestAction& action);

   Timer* timer;

   int actionIndex;
};

REGISTER(Tester, Tester)
