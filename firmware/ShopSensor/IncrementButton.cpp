#include "M5Defs.hpp"
#ifdef M5TOUGH

#include "IncrementButton.hpp"

static const int COUNT_TEXT_SIZE = 7;

static const int PENDING_COUNT_TEXT_SIZE = 2;

static const int BUTTON_RADIUS = 80;

static const int BUTTON_VERTICAL_OFFSET = 10;

static const int PENDING_COUNT_OFFSET = 55;

IncrementButton::IncrementButton(
  int16_t x, 
  int16_t y, 
  int16_t w, 
  int16_t h, 
  bool rot1,
  const char* name, 
  ButtonColors off,
  ButtonColors on) :
     Button(x, y, w, h, rot1, name, off, on),
     totalCount(0),
     pendingCount(0),
     onBreak(false)
{
  this->drawFn = IncrementButton::drawButton;
}

IncrementButton::~IncrementButton()
{
}

void IncrementButton::drawButton(Button& button, ButtonColors bc)
{
   (static_cast<IncrementButton*>(&button))->customDraw(bc);
}

void IncrementButton::setCount(int totalCount, int pendingCount)
{
  this->totalCount = totalCount;
  this->pendingCount = pendingCount;
}

void IncrementButton::setOnBreak(const bool& onBreak)
{
  this->onBreak = onBreak;
}

void IncrementButton::customDraw(ButtonColors bc)
{
   M5.Lcd.setTextFont(1);  // Necessary because buttons use Free Fonts.

   Point center((x + (w / 2)), (y + (h / 2) + BUTTON_VERTICAL_OFFSET));
  
   if (bc.bg != NODRAW)
   {
      M5.Lcd.fillCircle(center.x, center.y, BUTTON_RADIUS, bc.bg);
   }

   if (bc.outline != NODRAW)
   {
      M5.Lcd.drawCircle(center.x, center.y, BUTTON_RADIUS, bc.outline);
   }

   // Total count
   M5.Lcd.setTextColor(bc.text);
   M5.Lcd.setTextSize(COUNT_TEXT_SIZE);
   M5.Lcd.setTextDatum(MC_DATUM);  // Middle/center
   M5.Lcd.drawString(String(totalCount + pendingCount), center.x, center.y, 1);  // TODO: font

   if (onBreak)
   {
      M5.Lcd.setTextSize(PENDING_COUNT_TEXT_SIZE);
      M5.Lcd.setTextDatum(MC_DATUM);  // Bottom/center
      M5.Lcd.drawString("Paused", center.x, (center.y + PENDING_COUNT_OFFSET), 1);  // TODO: font
   }
   else if (pendingCount != 0)
   {
      M5.Lcd.setTextSize(PENDING_COUNT_TEXT_SIZE);
      M5.Lcd.setTextDatum(MC_DATUM);  // Bottom/center
      M5.Lcd.drawString("Tx:" + String(pendingCount), center.x, (center.y + PENDING_COUNT_OFFSET), 1);  // TODO: font
   }
}

#endif
