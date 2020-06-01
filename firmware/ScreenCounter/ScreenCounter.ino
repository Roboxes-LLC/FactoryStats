#include <Robox.h>

#include "ButtonRegistrar.hpp"
#include "ConfigPage.hpp"
#include "PartCounter.hpp"
#include "Robox.hpp"
#include "ScreenCounter.hpp"
#include "WebServer/WebpageServer.hpp"

WebpageServer webServer(80);

// *****************************************************************************
//                                  Arduino
// *****************************************************************************

void setup()
{
   Robox::setup();

#define PPTP
#ifdef PPTP
   Robox::addComponent(new ScreenCounter("counter"));
#else   
   Robox::addComponent(new PartCounter("counter", 2000));
#endif

   webServer.setup();
   webServer.addPage(new ConfigPage());
}

void loop()
{
   Robox::loop();

   webServer.loop();
}
