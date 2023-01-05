#include "M5Defs.hpp"
#ifdef M5TOUGH

#include "Component\Button.hpp"
#include "DisplayM5Tough.hpp"
#include "FactoryStatsDefs.hpp"
#include "IncrementButton.hpp"
#include "Robox.hpp"

static const ButtonColors ON_COLORS = {DEFAULT_ACCENT_COLOR, DEFAULT_TEXT_COLOR, DEFAULT_HIGHLIGHT_COLOR};    // bg, text, outline
static const ButtonColors OFF_COLORS = {DEFAULT_BACKGROUND_COLOR, DEFAULT_TEXT_COLOR, DEFAULT_ACCENT_COLOR};  // bg, text, outline

const char* DisplayM5Tough::ButtonId[dbCOUNT]
{
   "pause",
   "",
   "increment",
   "decrement",
   "",
   "",
   ""
};

const char* DisplayM5Tough::ButtonText[dbCOUNT] =
{
   "| |",
   "Info",
   "+1",
   "-1",
   "Count",
   "<",
   ">"
};

DisplayM5Tough* DisplayM5Tough::instance = nullptr;

DisplayM5Tough::DisplayM5Tough(
   const String& id) :
      Display(id)
{
}

DisplayM5Tough::DisplayM5Tough(
   MessagePtr message) :
      Display(message)
{

}

DisplayM5Tough::~DisplayM5Tough()
{
}

void DisplayM5Tough::setup()
{
  Display::setup();

  // Note:  Set after Display::setup() to account for rotation.
  content = Zone(0, 0, M5.Lcd.width(), (M5.Lcd.height() - FOOTER));
  center = Point((content.x + (content.w / 2)), (content.y + (content.h / 2)));

  createButtons();

  redraw();
}

DisplayM5Tough* DisplayM5Tough::getInstance()
{
  if (instance == nullptr)
  {
    instance = (DisplayM5Tough*)Robox::getComponent("display");
  }

  return (instance);
}

void DisplayM5Tough::updateCount(
   const int& totalCount,
   const int& pendingCount,
   const bool& shouldRedraw)
{
   (static_cast<IncrementButton*>(displayButtons[dbINCREMENT]))->setCount(totalCount, pendingCount);

   Display::updateCount(totalCount, pendingCount, shouldRedraw);
}

void DisplayM5Tough::redraw()
{
  M5.Lcd.setTextFont(1);  // Necessary because buttons use Free Fonts.

  Display::redraw();
}

void DisplayM5Tough::dispatchButton(Event& e)
{  
      /*
      dbFIRST,
      dbPAUSE = dbFIRST,
      dbSETTINGS,
      dbDECREMENT,
      dbHOME,
      dbPREVIOUS,
      dbNEXT,
      dbLAST,
      dbCOUNT = dbLAST - dbFIRST  
      */

   Serial.printf("Button pressed: %s\n", e.objName());

   if (strcmp(e.objName(), ButtonText[dbPAUSE]) == 0)
   {
      MessagePtr message = Messaging::newMessage();
      if (message)
      {
         message->setTopic(Roboxes::Button::BUTTON_UP);
         message->setSource(ButtonId[dbPAUSE]);

         Messaging::publish(message);
      }
   }
   else if (strcmp(e.objName(), ButtonText[dbSETTINGS]) == 0)
   {
      getInstance()->setMode(DISPLAY_MODE_INFO_FIRST);
   }
   else if (strcmp(e.objName(), ButtonText[dbINCREMENT]) == 0)
   {
      MessagePtr message = Messaging::newMessage();
      if (message)
      {
         message->setTopic(Roboxes::Button::BUTTON_UP);
         message->setSource(ButtonId[dbINCREMENT]);

         Messaging::publish(message);
      }
   }
   else if (strcmp(e.objName(), ButtonText[dbDECREMENT]) == 0)
   {
      MessagePtr message = Messaging::newMessage();
      if (message)
      {
         message->setTopic(Roboxes::Button::BUTTON_UP);
         message->setSource(ButtonId[dbDECREMENT]);

         Messaging::publish(message);
      }
   }
   else if (strcmp(e.objName(), ButtonText[dbHOME]) == 0)
   {
      getInstance()->setMode(COUNT);
   }
   else if (strcmp(e.objName(), ButtonText[dbPREVIOUS]) == 0)
   {
      DisplayMode newMode = (DisplayMode)(getInstance()->getMode() - 1);
      
      if (newMode < DISPLAY_MODE_INFO_FIRST)
      {
         newMode = (DisplayMode)(DISPLAY_MODE_INFO_LAST);
      }

      getInstance()->setMode(newMode);
   }
   else if (strcmp(e.objName(), ButtonText[dbNEXT]) == 0)
   {
      DisplayMode newMode = (DisplayMode)(getInstance()->getMode() + 1);

      if (newMode > DISPLAY_MODE_INFO_LAST)
      {
         newMode = DISPLAY_MODE_INFO_FIRST;
      }

      getInstance()->setMode(newMode);
   }
}

// *****************************************************************************

void DisplayM5Tough::drawSplash()
{
   Display::drawSplash();

   drawFooter();
}

void DisplayM5Tough::drawId()
{
   Display::drawId();

   drawFooter();
}

void DisplayM5Tough::drawConnection()
{
   Display::drawConnection();

   drawFooter();
}

void DisplayM5Tough::drawServer()
{
   Display::drawServer();

   drawFooter();
}

void DisplayM5Tough::drawCount()
{
   M5.Lcd.fillScreen(backgroundColor);

   M5.Lcd.setTextSize(FONT_MEDIUM);
   M5.Lcd.setTextDatum(BC_DATUM);  // Bottom/center

   // "No connection"   
   if (!isConnected)
   {
      M5.Lcd.setTextColor(RED);
      M5.Lcd.drawString("No connection", center.x, (content.y + MARGIN), font);
   }
   else if (stationId == UNKNOWN_STATION_ID)
   {
      M5.Lcd.setTextColor(ORANGE);
      M5.Lcd.drawString("Unassigned", center.x, (content.y + MARGIN), font);
   }
   else
   {
      M5.Lcd.setTextColor(ORANGE);
      M5.Lcd.drawString(stationLabel, center.x, (content.y + MARGIN), font);
   }

   drawFooter();
}

void DisplayM5Tough::drawInfo()
{
   Display::drawInfo();

   drawFooter();
}

void DisplayM5Tough::drawPower()
{
   Display::drawPower();

   drawFooter();
}

void DisplayM5Tough::createButtons()
{
   displayButtons[dbPAUSE] = new Button(20, 200, 80, 40, false, ButtonText[dbPAUSE], OFF_COLORS, ON_COLORS, MC_DATUM);
   displayButtons[dbSETTINGS] = new Button(120, 200, 80, 40, false, ButtonText[dbSETTINGS], OFF_COLORS, ON_COLORS, MC_DATUM);
   displayButtons[dbINCREMENT] = new IncrementButton(0, 0, M5.Lcd.width(), (M5.Lcd.height() - FOOTER), false, ButtonText[dbINCREMENT], OFF_COLORS, ON_COLORS);
   displayButtons[dbDECREMENT] = new Button(220, 200, 80, 40, false, ButtonText[dbDECREMENT], OFF_COLORS, ON_COLORS, MC_DATUM);
   displayButtons[dbPREVIOUS] = new Button(20, 200, 80, 40, false, ButtonText[dbPREVIOUS], OFF_COLORS, ON_COLORS, MC_DATUM);
   displayButtons[dbHOME] = new Button(120, 200, 80, 40, false, ButtonText[dbHOME], OFF_COLORS, ON_COLORS, MC_DATUM);
   displayButtons[dbNEXT] = new Button(220, 200, 80, 40, false, ButtonText[dbNEXT], OFF_COLORS, ON_COLORS, MC_DATUM);
   
   for (auto displayButton : displayButtons)
   {
      displayButton->addHandler(DisplayM5Tough::dispatchButton, E_TAP);
   }  
}

void DisplayM5Tough::drawFooter()
{
   // Background
   
   // Buttons

   for (auto displayButton : displayButtons)
   {
     displayButton->hide();
   }

   switch (getMode())
   {
      case COUNT:
      {
         displayButtons[dbINCREMENT]->draw();
         displayButtons[dbPAUSE]->draw();
         displayButtons[dbSETTINGS]->draw();
         displayButtons[dbDECREMENT]->draw();
         break;
      }

      case ID:
      case CONNECTION:
      case SERVER:
      case INFO:
      case POWER:
      {
         displayButtons[dbPREVIOUS]->draw();
         displayButtons[dbHOME]->draw();
         displayButtons[dbNEXT]->draw();
         break;
      }
      
      default:
      {
         break;
      }
   }
}

#endif
