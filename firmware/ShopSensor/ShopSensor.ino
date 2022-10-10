#include <Robox.h>

#include "Diagnostics.hpp"
#include "Display.hpp"
#include "M5Defs.hpp"
#include "Power.hpp"
#include "Robox.hpp"
#include "ShopButton.hpp"
#include "ShopSensor.hpp"
#include "ShopServer.hpp"

// *****************************************************************************
//                                  Arduino
// *****************************************************************************

void setup()
{
#if defined(M5STICKC) || defined(M5STICKC_PLUS)
   M5.begin();
   M5.Lcd.fillScreen(WHITE);
#endif

   Robox::setup();
}

void loop()
{
   Robox::loop();
}
