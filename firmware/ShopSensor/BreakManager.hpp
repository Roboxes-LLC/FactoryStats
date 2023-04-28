#pragma once

#include "Connection/ConnectionManager.hpp"
#include "Messaging/Adapter.hpp"
#include "Messaging/Component.hpp"
#include "Messaging/ComponentFactory.hpp"

#include "BreakDescription.hpp"
#include "ComponentDefs.hpp"
#include "Display.hpp"
#include "M5Defs.hpp"

class BreakManager : public Component
{

public:

   // Constructor.
   BreakManager(
      const String& id,
      const String& displayId,
      const String& adapterId);

   // Constructor.
   BreakManager(
      MessagePtr message);

   // Destructor.
   virtual ~BreakManager();

   // **************************************************************************
   // Component interface

   virtual void setup();

   // This operation handles a message directed to this sensor.
   virtual void handleMessage(
      // The message to handle.
      MessagePtr message);

   // **************************************************************************

   bool isOnBreak() const;

   void confirmBreak(
      const int& confirmedBreakId);

   bool hasPendingBreak() const;

   void clearPendingBreak();

   String getPendingBreakCode() const;

   void toggleBreak();

private:

   Display* getDisplay();

   Adapter* getAdapter();

    bool hasDefaultBreakCode() const;

   void onServerAvailable();

   void onButtonUp(
      const String& buttonId);

   void onSoftButtonUp(
      const int& buttonId);

   virtual void onServerResponse(
      MessagePtr message);

   bool sendBreakDescriptionsRequest();

   void processBreakDescriptions(
      MessagePtr message);

   String getBreakCode(
      const int& buttonId) const;

   String displayId;

   String adapterId;

   // Maps break codes to break descriptions.
   Dictionary breakCodes;

   String defaultBreakCode;

   String pendingBreakCode;

   int breakId;  // Station is considered paused if breakId != NO_BREAK_CODE;

   BreakDescriptionList breakDescriptionList;
};

REGISTER(BreakManager, BreakManager)
