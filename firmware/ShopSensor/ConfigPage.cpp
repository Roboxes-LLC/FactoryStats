#include "ConfigPage.hpp"
#include "Board/Board.hpp"
#include "Common/CommonDefs.hpp"
#include "Logger/Logger.hpp"
#include "Properties/Properties.hpp"
#include "Robox.hpp"
#include "Timer/Timer.hpp"

static const int RESET_DELAY = 3000;  // 3 seconds

ConfigPage::ConfigPage(
   const String& uid) :
   Webpage("/config.html",
           "/config.html"),
   uid(uid),
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
   Properties& properties = Robox::getProperties();
  
   content.replace("%uid", uid);
   content.replace("%ssid", properties.getString("wifi.ssid"));
   content.replace("%password", properties.getString("wifi.password"));
   content.replace("%server", properties.getString("server"));
   content.replace("%breakCode", properties.getString("breakCode"));
   content.replace("%info", infoText);
}

void ConfigPage::onConfigUpdate(
   const Dictionary& arguments)
{
   Properties& properties = Robox::getProperties();
     
   properties.set("wifi.ssid", arguments.getString("ssid"));
   properties.set("wifi.password", arguments.getString("password"));
   properties.set("server", arguments.getString("server"));
   properties.set("breakCode", arguments.getString("breakCode"));

   properties.save();
}

void ConfigPage::timeout(
    Timer* timer)
{
   Board::getBoard()->reset();  
}
