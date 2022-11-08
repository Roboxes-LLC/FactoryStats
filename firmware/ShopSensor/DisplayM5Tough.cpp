#include "DisplayM5Tough.hpp"

static const int headerHeight = 15;
static const int footerHeight = 15;

DisplayM5Tough::DisplayM5Tough(
   const String& id) :
      Display(id)
{

}

DisplayM5Tough::DisplayM5Tough(
   MessagePtr message) :
      Display(message)
{

}

// *****************************************************************************

void DisplayM5Tough::drawSplash()
{
   Display::drawSplash();
}

void DisplayM5Tough::drawId()
{
   Display::drawId();
}

void DisplayM5Tough::drawConnection()
{
   Display::drawConnection();
}

void DisplayM5Tough::drawServer()
{
   Display::drawServer();
}

void DisplayM5Tough::drawCount()
{
   Display::drawCount();
}

void DisplayM5Tough::drawInfo()
{
   Display::drawInfo();
}

void DisplayM5Tough::drawPower()
{
   Display::drawPower();
}

void DisplayM5Tough::drawFooter()
{
   // Background

   // Buttons
}
