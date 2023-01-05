#pragma once

#include "M5Defs.hpp"
#ifdef M5TOUGH

class IncrementButton : public Button
{
public:

   IncrementButton(
      int16_t x, 
      int16_t y, 
      int16_t w, 
      int16_t h, 
      bool rot1,
      const char* name, 
      ButtonColors off,
      ButtonColors on);

   virtual ~IncrementButton();

   static void drawButton(Button& button, ButtonColors bc);

   void setCount(int totalCount, int pendingCount);
    
private:

   void customDraw(ButtonColors bc);

   int totalCount;

   int pendingCount;
};

#endif
