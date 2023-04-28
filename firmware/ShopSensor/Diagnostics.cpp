#include "Diagnostics.hpp"
#include "Robox.hpp"
#include "Version.hpp"

Diagnostics::Diagnostics(
   const String& id,
   const int& updatePeriod,
   const String& connectionId,
   const String& displayId) :
      Component(id),
      updateTimer(0),
      updatePeriod(updatePeriod),
      connectionId(connectionId),
      displayId(displayId)
{
}
   
Diagnostics::Diagnostics(
   MessagePtr message) :
      Component(message),
      updateTimer(0),
      updatePeriod(message->getInt("period")),
      connectionId(message->getString("connection")),
      displayId(message->getString("display"))
{
}      

Diagnostics::~Diagnostics()
{
   Timer::freeTimer(updateTimer);
}   

void Diagnostics::setup()
{
   if (updatePeriod > 0)
   {
      updateTimer = Timer::newTimer(
         "update",
         updatePeriod,
         Timer::PERIODIC,
         this);
      
      updateTimer->start();
   }
   
   updateDiagnostics();
}

void Diagnostics::loop()
{
}

void Diagnostics::handleMessage(
   // The message to handle.
   MessagePtr message)
{
}
   
void Diagnostics::timeout(
   Timer* timer)
{
   updateDiagnostics();
}      
      
// *****************************************************************************

void Diagnostics::updateDiagnostics()
{
   Display* display = getDisplay();
   if (display)
   {
      display->updateInfo(
         VERSION,
         getMacAddress(),
         getUpTime(),
         getFreeMemory());  
   }
}

String Diagnostics::getMacAddress()
{
   String macAddress = "";
   
   if (macAddress == "")
   {
      WifiBoard* board = WifiBoard::getBoard();
      if (board)
      {
         // Get the MAC address.
         unsigned char mac[6] = {0, 0, 0, 0, 0, 0};
         WifiBoard::getBoard()->getMacAddress(mac);
         
         // Pretty-print hex digits of MAC address.
         char macStr[18];
         sprintf(macStr, "%02X:%02X:%02X:%02X:%02X:%02X", mac[0], mac[1], mac[2], mac[3], mac[4], mac[5]);
         macStr[17] = 0;  // Null terminate.
         
         macAddress = String(macStr);
      }
   }
   
   return (macAddress);
}

int Diagnostics::getUpTime()
{
   static const int MILLISECONDS_PER_SECOND = 1000;
   
   int upTime = 0;
   
   Board* board = Board::getBoard();
   
   if (board)
   {
       upTime = (int)(board->systemTime() / MILLISECONDS_PER_SECOND);
   }
   
   return (upTime);
}

int Diagnostics::getFreeMemory()
{
   int memory = 0;
   
   Board* board = Board::getBoard();
   
   if (board)
   {
       memory = board->getFreeHeap();
   }
   
   return (memory);
}

Display* Diagnostics::getDisplay()
{
   static Display* display = 0;
   
   if (!display)
   {
      display = (Display*)Robox::getComponent(displayId);
   }
   
   return (display);
}
