#include "Adapter/HttpClientAdapter.hpp"
#include "BreakDefs.hpp"
#include "BreakManager.hpp"
#include "Component/Button.hpp"
#include "Connection/ConnectionManager.hpp"
#include "FactoryStatsDefs.hpp"
#include "Logger/Logger.hpp"
#include "Messaging/Address.hpp"
#include "Messaging/Messaging.hpp"
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

   Messaging::subscribe(this, ConnectionManager::CONNECTION);
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
   // WIFI_CONNECTED, WIFI_DISCONNECTED
   if (message->getTopic() == ConnectionManager::CONNECTION)
   {
      onConnectionUpdate(message);
   }
   //  BUTTON_UP
   else if (message->getTopic() == Roboxes::Button::BUTTON_UP)
   {
      onButtonUp(message->getSource());
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

void BreakManager::onConnectionUpdate(
   MessagePtr message)
{
   if (message->getMessageId() == ConnectionManager::WIFI_CONNECTED)
   {
      sendBreakDescriptionsRequest();
   }
}

void BreakManager::onButtonUp(
   const String& buttonId)
{
   Logger::logDebug("BreakManager::onButtonUp: Button [%s] pressed.", buttonId.c_str());

   if ((buttonId == LIMIT_SWITCH) ||
       (buttonId == BUTTON_A) ||
       (buttonId == INCREMENT_BUTTON) ||
       (buttonId == DECREMENT_BUTTON))
   {
      if (isOnBreak())
      {
         toggleBreak();
      }
   }
   else if (buttonId == PAUSE_BUTTON)
   {
      toggleBreak();
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
   Logger::logDebug("BreakManager::processBreakDescriptions");

   for (Message::Iterator it = message->begin(); it != message->end(); it++)
   {
      String paramName = it->first;
      String paramValue = it->second;

      if (paramName.indexOf("break.") == 0)
      {
         Logger::logDebug("%s -> %s", it->first.c_str(), it->second.c_str());
      }
   }

}
