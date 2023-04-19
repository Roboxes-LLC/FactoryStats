#pragma once

#include "M5Defs.hpp"
#ifdef M5TOUGH

#include "Display.hpp"

class DisplayM5Tough : public Display
{

public:
   
   DisplayM5Tough(
      const String& id);

   enum DisplayButton
   {
      dbFIRST,
      dbBACKGROUND = dbFIRST,
      dbPAUSE,
      dbSETTINGS,
      dbINCREMENT,
      dbDECREMENT,
      dbHOME,
      dbPREVIOUS,
      dbNEXT,
      dbROTATE,
      dbLAST,
      dbCOUNT = dbLAST - dbFIRST      
   };

   static const char* ButtonId[dbCOUNT];

   DisplayM5Tough(
      MessagePtr message);

   virtual void setup();

   virtual ~DisplayM5Tough();

   static DisplayM5Tough* getInstance();

   virtual void updateCount(
      const int& totalCount,
      const int& pendingCount,
      const bool& shouldRedraw = true);

   virtual void updateBreak(
      const bool& onBreak,
      const bool& shouldRedraw = true);

   void redraw();

   static void dispatchButton(Event& e);
   
protected:

   virtual bool skipMode(
      const DisplayMode& mode) const;

   void drawSplash();
   
   void drawId();   
   
   void drawConnection();
   
   void drawServer();
   
   void drawCount();
   
   void drawInfo();
   
   void drawPower();

   void drawRotation();

private:

   void createButtons();

   void drawHeader();

   void drawFooter();

   static const char* ButtonText[dbCOUNT];

   static DisplayM5Tough* instance;

   bool isSetup;

   Button* displayButtons[dbCOUNT];
};

REGISTER(DisplayM5Tough, DisplayM5Tough)

#endif
