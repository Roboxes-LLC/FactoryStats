#include "ConfigPage.hpp"

#include <FS.h>

#include "Board.hpp"
#include "CommonDefs.hpp"
#include "Logger.hpp"
#include "Properties.hpp"
#include "ToastBot.hpp"

ConfigPage::ConfigPage() :
   Webpage("/config.html",
           "/config.html")
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
       }
       else if (action == "reset")
       {
          Board::getBoard()->reset();
       }
   }
   
   success = Webpage::handle(requestMethod, requestUri, arguments, responsePath);

   return (success);
}

void ConfigPage::replaceContent(
   String& content)
{
   // TODO
}

void ConfigPage::onConfigUpdate(
   const Dictionary& arguments)
{
  // TODO
}
