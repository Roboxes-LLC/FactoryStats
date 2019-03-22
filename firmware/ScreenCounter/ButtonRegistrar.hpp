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

private:

   virtual void pingRegistry();
};

REGISTER(ButtonRegistrar, ButtonRegistrar)
