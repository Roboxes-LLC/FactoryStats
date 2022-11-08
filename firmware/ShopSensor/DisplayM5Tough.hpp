#pragma once

#include <RFC.h>

#include "Display.hpp"

class DisplayM5Tough : public Display
{

public:
   
   DisplayM5Tough(
      const String& id);

   DisplayM5Tough(
      MessagePtr message);

   virtual ~DisplayM5Tough();
   
protected:

   void drawSplash();
   
   void drawId();   
   
   void drawConnection();
   
   void drawServer();
   
   void drawCount();
   
   void drawInfo();
   
   void drawPower();

private:

   void drawHeader();

   void drawFooter();
};

REGISTER(DisplayM5Tough, DisplayM5Tough)
