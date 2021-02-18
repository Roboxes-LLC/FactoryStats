#pragma once

#include <RFC.h>

#include "Timer/TimerListener.hpp"
#include "WebServer/Webpage.hpp"

class ConfigPage : public Webpage, TimerListener
{

public:
  
   ConfigPage(
      const String& uid);

  virtual ~ConfigPage();

  virtual bool handle(
      const HTTPMethod& requestMethod,
      const String& requestUri,
      const Dictionary& arguments,
      String& responsePath);

   virtual void timeout(
    Timer* timer);

protected:

  void replaceContent(
     String& content);

  void onConfigUpdate(
     const Dictionary& arguments);

private:

   String uid;

   String infoText;

};
