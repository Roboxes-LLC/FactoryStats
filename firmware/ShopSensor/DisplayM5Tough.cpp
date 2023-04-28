#include "M5Defs.hpp"
#ifdef M5TOUGH

#include "Component\Button.hpp"
#include "DisplayM5Tough.hpp"
#include "FactoryStatsDefs.hpp"
#include "IncrementButton.hpp"
#include "Logger/Logger.hpp"

#include "BreakDefs.hpp"
#include "ComponentDefs.hpp"
#include "PauseButton.hpp"
#include "Robox.hpp"

static const ButtonColors ON_COLORS = {DEFAULT_ACCENT_COLOR, DEFAULT_TEXT_COLOR, DEFAULT_HIGHLIGHT_COLOR};    // bg, text, outline
static const ButtonColors OFF_COLORS = {DEFAULT_BACKGROUND_COLOR, DEFAULT_TEXT_COLOR, DEFAULT_ACCENT_COLOR};  // bg, text, outline

static const int NUM_BREAK_DESCRIPTION_BUTTONS_PER_PAGE = 3;

static const char* BUTTON_TEXT[DisplayM5Tough::DisplayButton::dbCOUNT] =
{
   "",  // Background
   "| |",
   "Info",
   "+1",
   "-1",
   "Count",
   "<",
   ">",
   "Rotate",
   "<",
   ">",
   "Cancel"
};

DisplayM5Tough* DisplayM5Tough::instance = nullptr;

DisplayM5Tough::DisplayM5Tough(
   const String& id) :
      Display(id),
      isSetup(false)
{
}

DisplayM5Tough::DisplayM5Tough(
   MessagePtr message) :
      Display(message),
      isSetup(false),
      breakPageIndex(0)
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

  // Ready to draw.
  isSetup = true;

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

void DisplayM5Tough::updateBreak(
   const bool& onBreak,
   const bool& shouldRedraw)
{
   (static_cast<PauseButton*>(displayButtons[dbPAUSE]))->setOnBreak(onBreak);
   (static_cast<IncrementButton*>(displayButtons[dbINCREMENT]))->setOnBreak(onBreak);

   Display::updateBreak(onBreak, shouldRedraw);
}

void DisplayM5Tough::updateBreakDescriptions(
   const BreakDescriptionList& breakDescriptions,
   const bool& shouldRedraw)
{
   createBreakButtons(breakDescriptions);
}

void DisplayM5Tough::advanceBreakPage(
   const int& deltaPageIndex)
{
   breakPageIndex += deltaPageIndex;

   breakPageIndex = max(breakPageIndex, 0);
   breakPageIndex = min(breakPageIndex, (getBreakPageCount() - 1));

   if (getMode() == DisplayMode::PAUSE)
   {
      redraw();
   }
}

void DisplayM5Tough::redraw()
{
  if (isSetup)
  {
     M5.Lcd.setTextFont(1);  // Necessary because buttons use Free Fonts.

     Display::redraw();
  }
}

void DisplayM5Tough::dispatchButton(Event& e)
{  
   Logger::logDebug(F("DisplayM5Tough::dispatchButton: Button pressed: %s (%d)"), e.objName(), e.button->userData);

   if ((e.button != nullptr) &&
       (e.button->isVisible()))  // Added to guard against "false" presses reported by the M5Tough Button class.
   {
      switch (e.button->userData)
      {
         case dbPAUSE:
         case dbINCREMENT:
         case dbDECREMENT:
         case dbROTATE:
         {
            MessagePtr message = Messaging::newMessage();
            if (message)
            {
               message->setTopic(Roboxes::Button::BUTTON_UP);
               message->setSource(SOFT_BUTTON);
               message->set("buttonId", e.button->userData);

               Messaging::publish(message);
            }
            break;
         }

         case dbSETTINGS:
         {
            getInstance()->setMode(DISPLAY_MODE_INFO_FIRST);
            break;
         }

         case dbHOME:
         {
            getInstance()->setMode(COUNT);
            break;
         }

         case dbPREVIOUS:
         {
            DisplayMode newMode = (DisplayMode)(getInstance()->getMode() - 1);

            if (newMode < DISPLAY_MODE_INFO_FIRST)
            {
               newMode = (DisplayMode)(DISPLAY_MODE_INFO_LAST);
            }

            getInstance()->setMode(newMode);
            break;
         }

         case dbNEXT:
         {
            DisplayMode newMode = (DisplayMode)(getInstance()->getMode() + 1);

            if (newMode > DISPLAY_MODE_INFO_LAST)
            {
               newMode = DISPLAY_MODE_INFO_FIRST;
            }

            getInstance()->setMode(newMode);
            break;
         }

         case dbPAGE_LEFT:
         {
            getInstance()->advanceBreakPage(-1);
            break;
         }

         case dbPAGE_RIGHT:
         {
            getInstance()->advanceBreakPage(1);
            break;
         }

         case dbCANCEL:
         {
            getInstance()->setMode(DisplayMode::COUNT);
            break;
         }

         default:
         {
            if (e.button->userData >= BASE_BREAK_BUTTON_ID)
            {
               MessagePtr message = Messaging::newMessage();
               if (message)
               {
                  message->setTopic(Roboxes::Button::BUTTON_UP);
                  message->setSource(SOFT_BUTTON);
                  message->set("buttonId", e.button->userData);

                  Messaging::publish(message);
               }
            }
            break;
         }
      }
   }
}

// *****************************************************************************

bool DisplayM5Tough::skipMode(
   const DisplayMode& mode) const
{
   return ((mode == DisplayMode::SPLASH) ||
           (mode == DisplayMode::PAUSE));
}

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
   if (onBreak)
   {
      M5.Lcd.fillScreen(accentColor);
   }
   else
   {
      M5.Lcd.fillScreen(backgroundColor);
   }

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

void DisplayM5Tough::drawRotation()
{
   Display::drawRotation();

   drawFooter();
}

void DisplayM5Tough::drawPause()
{
   Display::drawPause();

   drawFooter();
}

void DisplayM5Tough::createButtons()
{
   // Background button is purely to catch gestures outside of the actual defined buttons.
   // See code in M5Buttons::which() for the reason for this.
   displayButtons[dbBACKGROUND] = new Button(0, 0, 0, 0, false, BUTTON_TEXT[dbBACKGROUND], OFF_COLORS, ON_COLORS);

   displayButtons[dbPAUSE] = new PauseButton(20, 200, 80, 40, false, BUTTON_TEXT[dbPAUSE], OFF_COLORS, ON_COLORS);
   displayButtons[dbSETTINGS] = new Button(120, 200, 80, 40, false, BUTTON_TEXT[dbSETTINGS], OFF_COLORS, ON_COLORS, MC_DATUM);
   displayButtons[dbINCREMENT] = new IncrementButton(0, 0, M5.Lcd.width(), (M5.Lcd.height() - FOOTER), false, BUTTON_TEXT[dbINCREMENT], OFF_COLORS, ON_COLORS);
   displayButtons[dbDECREMENT] = new Button(220, 200, 80, 40, false, BUTTON_TEXT[dbDECREMENT], OFF_COLORS, ON_COLORS, MC_DATUM);
   displayButtons[dbPREVIOUS] = new Button(20, 200, 80, 40, false, BUTTON_TEXT[dbPREVIOUS], OFF_COLORS, ON_COLORS, MC_DATUM);
   displayButtons[dbHOME] = new Button(120, 200, 80, 40, false, BUTTON_TEXT[dbHOME], OFF_COLORS, ON_COLORS, MC_DATUM);
   displayButtons[dbNEXT] = new Button(220, 200, 80, 40, false, BUTTON_TEXT[dbNEXT], OFF_COLORS, ON_COLORS, MC_DATUM);
   displayButtons[dbROTATE] = new Button((center.x - 75), (center.y - 50), 150, 100, false, BUTTON_TEXT[dbROTATE], OFF_COLORS, ON_COLORS, MC_DATUM);
   displayButtons[dbPAGE_LEFT] = new Button(topLeft.x, (topLeft.y + 40), 40, (content.h - 40), false, BUTTON_TEXT[dbPAGE_LEFT], ON_COLORS, OFF_COLORS, MC_DATUM, 0, 0, 0);             // Reversed colors.
   displayButtons[dbPAGE_RIGHT] = new Button((topRight.x - 40), (topRight.y + 40), 40, (content.h - 40), false, BUTTON_TEXT[dbPAGE_RIGHT], ON_COLORS, OFF_COLORS, MC_DATUM, 0, 0, 0);  // Reversed colors.
   displayButtons[dbCANCEL] = new Button(120, 200, 100, 40, false, BUTTON_TEXT[dbCANCEL], OFF_COLORS, ON_COLORS, MC_DATUM);
   
   for (int buttonId = dbFIRST; buttonId < dbLAST; buttonId++)
   {
      // Store the button id in the button itself.
      displayButtons[buttonId]->userData = buttonId;

      // Add a handler for "tap" events.
      displayButtons[buttonId]->addHandler(DisplayM5Tough::dispatchButton, E_TAP);
   }

   hideButtons();
}

void DisplayM5Tough::createBreakButtons(
   const BreakDescriptionList& breakDescriptions)
{
   const Point START_POS((topLeft.x + 40), (topLeft.y + 40));
   const int BUTTON_WIDTH = (M5.Lcd.width() - 80);
   const int BUTTON_HEIGHT = 53;

   Point position = START_POS;

   int buttonIndex = 0;
   Button* button = nullptr;

   for (BreakDescriptionList::Iterator it = breakDescriptions.begin(); it != breakDescriptions.end(); it++)
   {
      // Limit button label length.
      String label = it->description.substring(0, BREAK_BUTTON_LABEL_LENGTH);
      Logger::logDebug(F("DisplayM5Tough::createBreakButtons: Label: %s"), label.c_str());

      // Create the M5Stack soft button.
      button = new Button(position.x, position.y, BUTTON_WIDTH, BUTTON_HEIGHT, false, label.c_str(), OFF_COLORS, ON_COLORS, MC_DATUM);
      buttons.push_back(button);

      // Store the button id in the button itself.
      button->userData = (BASE_BREAK_BUTTON_ID +  buttonIndex);

      // Add a handler for "tap" events.
      button->addHandler(DisplayM5Tough::dispatchButton, E_TAP);

      position.y += BUTTON_HEIGHT;

      buttonIndex++;
      if  ((buttonIndex % NUM_BREAK_DESCRIPTION_BUTTONS_PER_PAGE) == 0)
      {
         position = START_POS;
      }
   }

   hideBreakButtons();
}

void DisplayM5Tough::hideButtons()
{
   for (auto displayButton : displayButtons)
   {
     displayButton->hide();
   }
}

void DisplayM5Tough::hideBreakButtons()
{
   for (ButtonList::Iterator it = buttons.begin(); it != buttons.end(); it++)
   {
      (*it)->hide();
   }
}

void DisplayM5Tough::showBreakButtons()
{
   int startIndex = (breakPageIndex * NUM_BREAK_DESCRIPTION_BUTTONS_PER_PAGE);
   int endIndex = ((breakPageIndex + 1) * NUM_BREAK_DESCRIPTION_BUTTONS_PER_PAGE);

   int buttonIndex = 0;
   for (ButtonList::Iterator it = buttons.begin(); it != buttons.end(); it++)
   {
      if ((buttonIndex >= startIndex) && (buttonIndex < endIndex))
      {
         (*it)->draw();
      }

      buttonIndex++;

      if (buttonIndex >= endIndex)
      {
         continue;
      }
   }
}

int DisplayM5Tough::getBreakPageCount() const
{
   return ((buttons.size() / NUM_BREAK_DESCRIPTION_BUTTONS_PER_PAGE) + 1);
}


void DisplayM5Tough::drawFooter()
{
   // Background
   
   // Buttons

   hideButtons();

   hideBreakButtons();

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

      case ROTATION:
      {
         displayButtons[dbROTATE]->draw();
         displayButtons[dbPREVIOUS]->draw();
         displayButtons[dbHOME]->draw();
         displayButtons[dbNEXT]->draw();
         break;
      }
      
      case PAUSE:
      {
         if (breakPageIndex > 0)
         {
            displayButtons[dbPAGE_LEFT]->draw();
         }

         if (breakPageIndex < (getBreakPageCount() - 1))
         {
            displayButtons[dbPAGE_RIGHT]->draw();
         }

         displayButtons[dbCANCEL]->draw();

         showBreakButtons();
      }

      default:
      {
         break;
      }
   }
}

#endif
