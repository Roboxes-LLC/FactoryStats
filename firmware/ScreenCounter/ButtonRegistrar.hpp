#include <RFC.h>

#include "Component/Registrar.hpp"
#include "Messaging/ComponentFactory.hpp"

class ButtonRegistrar : public Registrar
{

public:

   ButtonRegistrar(
      const String& id,
      const String& registryUrl,
      const int& refreshPeriod);

   ButtonRegistrar(
      MessagePtr message);

   virtual ~ButtonRegistrar();

   virtual void setup();

private:

   virtual void pingRegistry();

   String serverUrl;
};

REGISTER(ButtonRegistrar, ButtonRegistrar)
