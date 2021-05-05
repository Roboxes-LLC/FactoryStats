// Uncomment to build for M5Stick-C or M5Stick-C Plus
#define M5STICKC
//#define M5STICKCPLUS

#ifdef M5STICKC_PLUS
#include <M5StickCPlus.h>
#else
#include <M5StickC.h>
#endif

#include <Robox.h>

#include "Diagnostics.hpp"
#include "Display.hpp"
#include "Power.hpp"
#include "Robox.hpp"
#include "ShopSensor.hpp"
#include "ShopServer.hpp"

// *****************************************************************************
//                                  Arduino
// *****************************************************************************

void setup()
{
#ifdef M5STICKC || M5STICKC_PLUS
   M5.begin();
   M5.Lcd.fillScreen(WHITE);
#endif

   Robox::setup();
}

void loop()
{
   Robox::loop();
}
