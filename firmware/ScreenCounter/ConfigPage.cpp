#include <FS.h>

#include "ConfigPage.hpp"
#include "Board.hpp"
#include "CommonDefs.hpp"
#include "Logger.hpp"
#include "Properties.hpp"
#include "Timer.hpp"
#include "ToastBot.hpp"

static const int RESET_DELAY = 3000;  // 3 seconds

ConfigPage::ConfigPage() :
   Webpage("/config.html",
           "/config.html"),
   infoText("")
{
   // Nothing to do here.  
}

ConfigPage::~ConfigPage()
{
   // Nothing to do here.
}

bool ConfigPage::handle(
   const HTTPMethod& requestMethod,
   const String& requestUri,
   const Dictionary& arguments,
   String& responsePath)
{
   bool success = false;
   responsePath = "";

   if (canHandle(requestMethod, requestUri))
   {
       Properties& properties = ToastBot::getProperties();

       String action = arguments.getString("action");
    
       if (action == "update")
       {
          onConfigUpdate(arguments);

          infoText = "Config updated!  Reset required.";
       }
       else if (action == "reset")
       {
          Timer* timer = Timer::newTimer("resetTimer", RESET_DELAY, Timer::ONE_SHOT, this);
          timer->start();

          infoText = "Resetting board ...";
       }
   }
   
   success = Webpage::handle(requestMethod, requestUri, arguments, responsePath);

   return (success);
}

void ConfigPage::replaceContent(
   String& content)
{
   Properties& properties = ToastBot::getProperties();
  
   content.replace("%name", properties.getString("deviceName"));
   content.replace("%ssid", properties.getString("wifi.ssid"));
   content.replace("%password", properties.getString("wifi.password"));
   content.replace("%info", infoText);
}

void ConfigPage::onConfigUpdate(
   const Dictionary& arguments)
{
   Properties& properties = ToastBot::getProperties();
     
   properties.set("deviceName", arguments.getString("deviceName"));
   properties.set("wifi.ssid", arguments.getString("ssid"));
   properties.set("wifi.password", arguments.getString("password"));

   properties.save();
}

void ConfigPage::timeout(
    Timer* timer)
{
   Board::getBoard()->reset();  
}
