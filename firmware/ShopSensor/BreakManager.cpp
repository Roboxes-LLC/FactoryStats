#include "Adapter/HttpClientAdapter.hpp"
#include "BreakDefs.hpp"
#include "BreakManager.hpp"
#include "Component/Button.hpp"
#include "FactoryStatsDefs.hpp"
#include "Logger/Logger.hpp"
#include "Messaging/Address.hpp"
#include "Messaging/Messaging.hpp"
#include "MessagingDefs.hpp"
#include "Robox.hpp"

// *****************************************************************************
//                                     Public

BreakManager::BreakManager(
   const String& id,
   const String& displayId,
   const String& adapterId) :
   Component(id),
   displayId(displayId),
   adapterId(adapterId),
   defaultBreakCode(UNKNOWN_BREAK_CODE),
   pendingBreakCode(NO_PENDING_BREAK_CODE),
   breakId(NO_BREAK_ID)
{
}

BreakManager::BreakManager(
   MessagePtr message) :
      Component(message),
      displayId(message->getString("display")),
      adapterId(message->getString("adapter")),
      defaultBreakCode(UNKNOWN_BREAK_CODE),
      pendingBreakCode(NO_PENDING_BREAK_CODE),
      breakId(NO_BREAK_ID)
{
   Logger::logDebug(F("BreakManager::BreakManager: Here"));
}

BreakManager::~BreakManager()
{
}

// *****************************************************************************
//                            Component interface

void BreakManager::setup()
{
   Component::setup();

   defaultBreakCode = Robox::getProperties().getString("breakCode");

   Messaging::subscribe(this, SERVER_STATUS);
   Messaging::subscribe(this, Roboxes::Button::BUTTON_UP);

   if (!getDisplay())
   {
      Logger::logWarning(F("BreakManager::setup: No available display."));
   }

   if (!getAdapter())
   {
      Logger::logWarning(F("BreakManager::setup: No available adapter."));
   }
}

void BreakManager::handleMessage(
   MessagePtr message)
{
   // SERVER_AVAILABLE
   if (message->getMessageId() == SERVER_AVAILABLE)
   {
      onServerAvailable();
   }
   //  BUTTON_UP
   else if (message->getTopic() == Roboxes::Button::BUTTON_UP)
   {
      if (message->getSource() == SOFT_BUTTON)
      {
         onSoftButtonUp(message->getInt("buttonId"));
      }
      else
      {
         onButtonUp(message->getSource());
      }
   }
   //  HTTP_RESPONSE
   else if (message->getMessageId() == HttpClientAdapter::HTTP_RESPONSE)
   {
      onServerResponse(message);
   }
   else
   {
      Component::handleMessage(message);
   }

   Messaging::freeMessage(message);
}

// *****************************************************************************

bool BreakManager::isOnBreak() const
{
   bool isStationOnBreak = false;

   if (pendingBreakCode != NO_PENDING_BREAK_CODE)
   {
      isStationOnBreak = (pendingBreakCode != NO_BREAK_CODE);
   }
   else
   {
      isStationOnBreak = (breakId != NO_BREAK_ID);
   }

   return (isStationOnBreak);
}

void BreakManager::confirmBreak(
   const int& confirmedBreakId)
{
   breakId = confirmedBreakId;
}

bool BreakManager::hasPendingBreak() const
{
   return (pendingBreakCode != NO_PENDING_BREAK_CODE);
}

void BreakManager::clearPendingBreak()
{
   pendingBreakCode = NO_PENDING_BREAK_CODE;
}

String BreakManager::getPendingBreakCode() const
{
   return (pendingBreakCode);
}

void BreakManager::toggleBreak()
{
   if (!isOnBreak())
   {
      // Toggle on.
      pendingBreakCode = defaultBreakCode;
   }
   else
   {
      // Toggle off.
      pendingBreakCode = NO_BREAK_CODE;
   }

   Display* display = getDisplay();
   if (display)
   {
      display->updateBreak(isOnBreak());
   }
}

// *****************************************************************************
//                                      Private

Display* BreakManager::getDisplay()
{
   static Display* display = 0;

   if (!display)
   {
      display = (Display*)Robox::getComponent(displayId);
   }

   return (display);
}

Adapter* BreakManager::getAdapter()
{
   static Adapter* adapter = 0;

   if (!adapter)
   {
      adapter = (Adapter*)Robox::getComponent(adapterId);
   }

   return (adapter);
}

bool BreakManager::hasDefaultBreakCode() const
{
   return ((defaultBreakCode != "") &&
           (defaultBreakCode != NO_BREAK_CODE));
}

void BreakManager::onServerAvailable()
{
   if (breakDescriptionList.size() == 0)
   {
      sendBreakDescriptionsRequest();
   }
}

void BreakManager::onButtonUp(
   const String& buttonId)
{
   if ((buttonId == LIMIT_SWITCH) ||
       (buttonId == BUTTON_A))
   {
      if (isOnBreak())
      {
         toggleBreak();
      }
   }
}

void BreakManager::onSoftButtonUp(
   const int& buttonId)
{
   switch (buttonId)
   {
      case DisplayM5Tough::DisplayButton::dbINCREMENT:
      case DisplayM5Tough::DisplayButton::dbDECREMENT:
      {
         if (isOnBreak())
         {
            toggleBreak();
         }
         break;
      }

      case DisplayM5Tough::DisplayButton::dbPAUSE:
      {
         if (isOnBreak())
         {
            toggleBreak();
         }
         else if (hasDefaultBreakCode())
         {
           toggleBreak();
         }
         else
         {
            Display* display = getDisplay();
            if (display)
            {
               display->setMode(Display::DisplayMode::PAUSE);
            }
         }
         break;
      }

      default:
      {
         if (buttonId >= BASE_BREAK_BUTTON_ID)
         {
            int breakButtonId = (buttonId - BASE_BREAK_BUTTON_ID);

            String breakCode = getBreakCode(breakButtonId);

            if (breakCode != NO_BREAK_CODE)
            {
               // Set break code.
               pendingBreakCode = breakCode;

               //  Update the display.
               Display* display = getDisplay();
               if (display)
               {
                  display->updateBreak(isOnBreak());

                  display->setMode(Display::DisplayMode::COUNT);
               }
            }
         }
         break;
      }
   }
}

void BreakManager::onServerResponse(MessagePtr message)
{
   int responseCode =  message->getInt(HttpClientAdapter::RESPONSE_CODE);

   Logger::logDebug(F("BreakManager::BreakManager: Got server response [%d]."), responseCode);

   if (responseCode == 200)
   {
      if (message->getTransaction() == BREAK_DESCRIPTIONS_REQUEST_MESSAGE_ID)
      {
         processBreakDescriptions(message);
      }
   }
}

bool BreakManager::sendBreakDescriptionsRequest()
{
   bool success = false;

   MessagePtr message = Messaging::newMessage();
   if (message)
   {
      message->setMessageId(BREAK_DESCRIPTIONS_REQUEST_MESSAGE_ID);
      message->setSource(getId());
      message->setDestination(adapterId);
      message->setTransaction(BREAK_DESCRIPTIONS_REQUEST_MESSAGE_ID);

      // Specify HTTP parameters.
      message->set(HttpClientAdapter::REQUEST_TYPE, HttpClientAdapter::GET);
      message->set(HttpClientAdapter::ENCODING, HttpClientAdapter::URL_ENCODING);
      //message->set("subdomain", "flexscreentest");  // TODO: For local testing.  Remove.

      String url = getRequestUrl(Robox::getProperties().getString("server"),
                                 "breakDescriptions");
      if (url != "")
      {
         message->set("url", url);
      }

      // Specify that the break description objects be "flattened" into individual variables.
      // Note: Improve by allowing the Message class to handle true JSON objects.
      message->set("flatten", "true");

      success = Messaging::send(message);

      if (success)
      {
         Logger::logDebug(F("BreakManager::sendBreakDescriptionsRequest: Sent break descriptions request to server."));
      }
      else
      {
         Logger::logWarning(F("BreakManager::sendBreakDescriptionsRequest: Failed to send break descriptions request to server."));
      }
   }

   return (success);
}

void BreakManager::processBreakDescriptions(
   MessagePtr message)
{
   // break.0.id = 1
   // break.0.code = "001"
   // break.0.desc = "Bathroom"

   BreakDescription breakDescription;

   breakDescriptionList.clear();

   for (Message::Iterator it = message->begin(); it != message->end(); it++)
   {
      String paramName = it->first;
      String paramValue = it->second;

      if (paramName.indexOf("break.") == 0)
      {
         if (it->first.indexOf("id") != -1)
         {
            // No need to record this.
         }
         else if (it->first.indexOf("code") != -1)
         {
            breakDescription.code = it->second;
         }
         else if (it->first.indexOf("desc") != -1)
         {
            breakDescription.description = it->second;

            // This assumes that the message contains the id, code, and desc properties for
            // each break description together, and in that order.
            breakDescriptionList.push_back(breakDescription);
         }
      }
   }

   // Update the break buttons.
   DisplayM5Tough* display = (DisplayM5Tough*)getDisplay();
   if (display)
   {
      display->updateBreakDescriptions(breakDescriptionList);
   }
}

String BreakManager::getBreakCode(
   const int& buttonId) const
{
   String breakCode = NO_BREAK_CODE;

   int index = 0;

   for (BreakDescriptionList::Iterator it = breakDescriptionList.begin();
                                       it != breakDescriptionList.end();
                                       it++, index++)
   {
      if (index == buttonId)
      {
         breakCode = it->code;
         continue;
      }
   }

   return (breakCode);
}

