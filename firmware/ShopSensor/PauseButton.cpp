#include "M5Defs.hpp"
#ifdef M5TOUGH

#include "PauseButton.hpp"

PauseButton::PauseButton(
  int16_t x, 
  int16_t y, 
  int16_t w, 
  int16_t h, 
  bool rot1,
  const char* name, 
  ButtonColors off,
  ButtonColors on) :
     Button(x, y, w, h, rot1, name, off, on),
     onBreak(false)
{
  this->drawFn = PauseButton::drawButton;
}

PauseButton::~PauseButton()
{
}

void PauseButton::drawButton(Button& button, ButtonColors bc)
{
   (static_cast<PauseButton*>(&button))->customDraw(bc);
}

void PauseButton::setOnBreak(const bool& onBreak)
{
  this->onBreak = onBreak;
}

void PauseButton::customDraw(ButtonColors bc)
{
   static const int TRI_HEIGHT = 17;
   static const int TRI_WIDTH = 17;
   static const Point TRI_OFFSET(33, 10);

   static const int PAUSE_HEIGHT = 20;
   static const int PAUSE_WIDTH = 17;
   static const int BAR_WIDTH = 5;
   static const Point PAUSE_OFFSET(33, 10);

   ButtonColors altBc = bc;
   altBc.text = NODRAW;
   M5Buttons::drawFunction(*this, altBc);

   if (onBreak)
   {
      // Play icon

      Point topLeft((x + TRI_OFFSET.x), (y + TRI_OFFSET.y));

      Point p1(topLeft.x, topLeft.y);
      Point p2(topLeft.x, (topLeft.y + TRI_HEIGHT));
      Point p3((topLeft.x + TRI_WIDTH), (topLeft.y + (TRI_HEIGHT / 2)));

      M5.Lcd.fillTriangle(p1.x, p1.y, p2.x, p2.y, p3.x, p3.y, bc.text);
   }
   else
   {
      // Pause icon

      Point topLeft((x + PAUSE_OFFSET.x), (y + PAUSE_OFFSET.y));
      Point bottomRight((topLeft.x + PAUSE_WIDTH), (topLeft.y + PAUSE_HEIGHT));

      M5.Lcd.fillRoundRect(topLeft.x, topLeft.y, BAR_WIDTH, PAUSE_HEIGHT, 0, bc.text);
      M5.Lcd.fillRoundRect((bottomRight.x - BAR_WIDTH), (bottomRight.y - PAUSE_HEIGHT), BAR_WIDTH, PAUSE_HEIGHT, 0, bc.text);
   }
}

#endif
