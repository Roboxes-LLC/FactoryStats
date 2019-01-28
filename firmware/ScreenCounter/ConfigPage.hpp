#pragma once

#include "TimerListener.hpp"
#include "Webpage.hpp"

class ConfigPage : public Webpage, TimerListener
{

public:
  
   ConfigPage();

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

   String infoText;

};
