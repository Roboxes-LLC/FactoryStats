#include "Component/Button.hpp"
#include "Logger/Logger.hpp"
#include "Messaging/Messaging.hpp"

#include "BreakDefs.hpp"
#include "ComponentDefs.hpp"
#include "DisplayM5Tough.hpp"
#include "MessagingDefs.hpp"
#include "Tester.hpp"

static const int TEST_RATE = 1000;  // 1 action per second

// A simple set of TEST actions that will be repeated periodically.
// TODO: Read from a configuration file.
static const Tester::TestAction TEST_ACTIONS[] =
{
   Tester::TestAction::INCREMENT,
   Tester::TestAction::WAIT,
   Tester::TestAction::WAIT
};

static const int TEST_ACTIONS_COUNT = (sizeof(TEST_ACTIONS) / sizeof(Tester::TestAction));

// **************************************************************************
//                                  Public

Tester::Tester(
   const String& id) :
      Component(id),
      timer(nullptr),
      actionIndex(0)
{

}

Tester::Tester(
   MessagePtr message) :
      Component(message),
      timer(nullptr),
      actionIndex(0)
{
}

Tester::~Tester()
{
}

// **************************************************************************
// Component interface

void Tester::setup()
{
   Component::setup();

   Messaging::subscribe(this, SERVER_STATUS);
}

void Tester::handleMessage(
   MessagePtr message)
{
   // SERVER_AVAILABLE
   if (message->getMessageId() == SERVER_AVAILABLE)
   {
      start();
   }
   else
   {
      Component::handleMessage(message);
   }
}

// **************************************************************************
// TimerListener interface

void Tester::timeout(
   Timer* timer)
{
   if (TEST_ACTIONS_COUNT > 0)
   {
      actionIndex++;
      if (actionIndex >= TEST_ACTIONS_COUNT)
      {
         actionIndex = 0;
      }

      runAction(TEST_ACTIONS[actionIndex]);
   }
}

// **************************************************************************
//                                  Private

void Tester::start()
{
   Logger::logDebug("Tester::start: Starting automated testing.");

   timer = Timer::newTimer("testTimer", TEST_RATE, Timer::TimerType::PERIODIC, this);

   if (timer)
   {
      timer->start();
   }
}

void Tester::stop()
{
   Logger::logDebug("Tester::stop: Stopping automated testing.");

   if (timer)
   {
      timer->stop();
      timer = nullptr;
   }
}

void Tester::runAction(
   const TestAction& action)
{
   Logger::logDebug("Tester::runAction: Running test action [%d].", action);

   switch (action)
   {
      case TestAction::INCREMENT:
      {
         MessagePtr message = Messaging::newMessage();
         if (message)
         {
            message->setTopic(Roboxes::Button::BUTTON_UP);
            message->setSource(SOFT_BUTTON);
            message->set("buttonId", DisplayM5Tough::DisplayButton::dbINCREMENT);

            Messaging::publish(message);
         }
         break;
      }

      case TestAction::DECREMENT:
      {
         MessagePtr message = Messaging::newMessage();
         if (message)
         {
            message->setTopic(Roboxes::Button::BUTTON_UP);
            message->setSource(SOFT_BUTTON);
            message->set("buttonId", DisplayM5Tough::DisplayButton::dbDECREMENT);

            Messaging::publish(message);
         }
         break;
      }

      case WAIT:
      default:
      {
         break;
      }
   }
}
