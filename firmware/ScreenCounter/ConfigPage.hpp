#pragma once

#include "Webpage.hpp"

class ConfigPage : public Webpage
{

public:
  
   ConfigPage();

  virtual ~ConfigPage();

  virtual bool handle(
      const HTTPMethod& requestMethod,
      const String& requestUri,
      const Dictionary& arguments,
      String& responsePath);

protected:

  void replaceContent(
     String& content);

  void onConfigUpdate(
     const Dictionary& arguments);

};
