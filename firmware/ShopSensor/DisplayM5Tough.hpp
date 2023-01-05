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
      dbPAUSE = dbFIRST,
      dbSETTINGS,
      dbINCREMENT,
      dbDECREMENT,
      dbHOME,
      dbPREVIOUS,
      dbNEXT,
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

   void redraw();

   static void dispatchButton(Event& e);
   
protected:

   void drawSplash();
   
   void drawId();   
   
   void drawConnection();
   
   void drawServer();
   
   void drawCount();
   
   void drawInfo();
   
   void drawPower();

private:

   void createButtons();

   void drawHeader();

   void drawFooter();

   static const char* ButtonText[dbCOUNT];

   static DisplayM5Tough* instance;

   Button* displayButtons[dbCOUNT];
};

REGISTER(DisplayM5Tough, DisplayM5Tough)

#endif
