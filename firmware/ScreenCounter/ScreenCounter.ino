#include <ToastBot.h>

#include "ButtonRegistrar.hpp"
#include "ConfigPage.hpp"
#include "PartCounter.hpp"
#include "ScreenCounter.hpp"
#include "WebServer.hpp"

WebServer webServer(80);

// *****************************************************************************
//                                  Arduino
// *****************************************************************************

void setup()
{
   ToastBot::setup();

//#define PPTP
#ifdef PPTP
   ToastBot::addComponent(new ScreenCounter("counter"));
#else   
   ToastBot::addComponent(new PartCounter("counter", 2000));
#endif

   webServer.setup();
   webServer.addPage(new ConfigPage());
}

void loop()
{
   ToastBot::loop();

   webServer.loop();
}
