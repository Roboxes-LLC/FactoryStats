#pragma once

#include "M5Defs.hpp"
#ifdef M5TOUGH

class PauseButton : public Button
{
public:

   PauseButton(
      int16_t x, 
      int16_t y, 
      int16_t w, 
      int16_t h, 
      bool rot1,
      const char* name, 
      ButtonColors off,
      ButtonColors on);

   virtual ~PauseButton();

   static void drawButton(Button& button, ButtonColors bc);

   void setOnBreak(const bool& onBreak);
    
private:

   void customDraw(ButtonColors bc);

   bool onBreak;
};

#endif
