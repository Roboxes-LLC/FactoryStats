#include <Board.h>
#include <ToastBot.h>

#include "WebServer.hpp"
#include "ConfigPage.hpp"
#include "ScreenCounter.hpp"

WebServer webServer(80);

// *****************************************************************************
//                                  Arduino
// *****************************************************************************

void setup()
{
   ToastBot::setup(new Esp8266Board());

   ToastBot::addComponent(new ScreenCounter("counter"));

   webServer.setup();
   webServer.addPage(new ConfigPage());
}

void loop()
{
   ToastBot::loop();

   webServer.loop();
}
