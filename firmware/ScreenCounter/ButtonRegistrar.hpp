#include "Component/Registrar.hpp"
#include "ComponentFactory.hpp"

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
