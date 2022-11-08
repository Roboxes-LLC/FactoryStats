#include <Robox.h>

#include "Diagnostics.hpp"
#include "Display.hpp"
#include "DisplayM5Tough.hpp"
#include "M5Defs.hpp"
#include "Power.hpp"
#include "Robox.hpp"
#include "ShopSensor.hpp"
#include "ShopServer.hpp"

// *****************************************************************************
//                                  Arduino
// *****************************************************************************

void setup()
{
#if defined(M5STICKC) || defined(M5STICKC_PLUS) || defined(M5TOUGH)
   M5.begin();
   M5.Lcd.fillScreen(WHITE);
#endif

   Robox::setup();
}

void loop()
{
   Robox::loop();
}
