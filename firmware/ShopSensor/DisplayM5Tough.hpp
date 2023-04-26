#pragma once

#include "M5Defs.hpp"
#ifdef M5TOUGH

// https://github.com/m5stack/M5Core2/issues/79
#undef min

#include "STL/List.hpp"

#include "BreakDescription.hpp"
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
      dbPAGE_LEFT,
      dbPAGE_RIGHT,
      dbCANCEL,
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

   virtual void updateBreakDescriptions(
      const BreakDescriptionList& breakDescriptions,
      const bool& shouldRedraw = true);

   void advanceBreakPage(
      const int& deltaPageIndex);

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

   void drawPause();

private:

   void createButtons();

   void createBreakButtons(
      const BreakDescriptionList& breakDescriptions);

   void hideButtons();

   void hideBreakButtons();

   void showBreakButtons();

   int getBreakPageCount() const;

   void drawHeader();

   void drawFooter();

   static DisplayM5Tough* instance;

   bool isSetup;

   Button* displayButtons[dbCOUNT];

   typedef List<Button*> ButtonList;

   ButtonList buttons;

   int breakPageIndex;
};

REGISTER(DisplayM5Tough, DisplayM5Tough)

#endif
