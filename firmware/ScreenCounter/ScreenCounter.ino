#include <ToastBot.h>

#include "ButtonRegistrar.hpp"
#include "ConfigPage.hpp"
#include "ScreenCounter.hpp"
#include "WebServer.hpp"

WebServer webServer(80);

// *****************************************************************************
//                                  Arduino
// *****************************************************************************

void setup()
{
   ToastBot::setup();

   ToastBot::addComponent(new ScreenCounter("counter"));

   webServer.setup();
   webServer.addPage(new ConfigPage());
}

void loop()
{
   ToastBot::loop();

   webServer.loop();
}
