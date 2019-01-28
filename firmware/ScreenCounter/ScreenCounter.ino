#include <Board.h>
#include <ToastBot.h>

#include "WebServer.hpp"
#include "Component\Button.hpp"
#include "ConfigPage.hpp"
#include "ScreenCounter.hpp"
#include "StatusLed.hpp"

WebServer webServer(80);

// *****************************************************************************
//                                  Arduino
// *****************************************************************************

void setup()
{
   ToastBot::setup(new Esp8266Board());

   Properties& properties = ToastBot::getProperties();
   int buttonPin = properties.getInt("buttonPin");
   int ledPin = properties.getInt("ledPin");

   ToastBot::addComponent(new ScreenCounter("counter"));

   Button* button = new Button("button", buttonPin);
   button->setLongPress(5000);
   ToastBot::addComponent(button);

   ToastBot::addComponent(new StatusLed("led", ledPin));

   Adapter* httpAdapter = new HttpClientAdapter("httpAdapter", new RestfulProtocol());
   ToastBot::addComponent(httpAdapter);
                                           
   webServer.setup();
   webServer.addPage(new ConfigPage());
}

void loop()
{
   ToastBot::loop();

   webServer.loop();
}
