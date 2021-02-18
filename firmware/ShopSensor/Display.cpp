#include <M5StickCPlus.h>

#include "Display.hpp"

// For reference:
/*
TL_DATUM = Top left
TC_DATUM = Top centre
TR_DATUM = Top right
ML_DATUM = Middle left
MC_DATUM = Middle centre
MR_DATUM = Middle right
BL_DATUM = Bottom left
BC_DATUM = Bottom centre
BR_DATUM = Bottom right
*/

static const int DEFAULT_FONT = 1;
static const int DEFAULT_BACKGROUND_COLOR = BLACK;
static const int DEFAULT_TEXT_COLOR = YELLOW;
static const int DEFAULT_ACCENT_COLOR = BLUE;
static const int DEFAULT_HIGHLIGHT_COLOR = WHITE;

static const int MARGIN = 5;

Display::Display(
   const String& id) :
      Component(id),
      font(DEFAULT_FONT),
      backgroundColor(DEFAULT_BACKGROUND_COLOR),
      textColor(DEFAULT_TEXT_COLOR),
      accentColor(DEFAULT_ACCENT_COLOR),
      highlightColor(DEFAULT_HIGHLIGHT_COLOR),
      isConnected(false),
      isAccessPoint(false),
      isServerConnected(false),
      totalCount(0),
      pendingCount(0),
      upTime(0),
      freeMemory(0)
{
}
   
Display::Display(
   MessagePtr message) :
      Component(message),
      font(DEFAULT_FONT),
      backgroundColor(DEFAULT_BACKGROUND_COLOR),
      textColor(DEFAULT_TEXT_COLOR),
      accentColor(DEFAULT_ACCENT_COLOR),
      highlightColor(DEFAULT_HIGHLIGHT_COLOR),
      isConnected(false),
      isAccessPoint(false),
      isServerConnected(false),
      totalCount(0),
      pendingCount(0),
      upTime(0),
      freeMemory(0)
{
}
   
Display::~Display()
{
}   

void Display::setup()
{
   M5.begin();
   M5.Lcd.setRotation(1);
   M5.Lcd.setTextFont(font);
   
   setMode(SPLASH);
   
   redraw();
}

void Display::loop()
{
   M5.update();
}

void Display::handleMessage(
   MessagePtr message)
{
}

 Display::DisplayMode Display::getMode()
{
   return (mode);
}   

void Display::setMode(
   const DisplayMode& mode)
{
   if (this->mode != mode)
   {
      this->mode = mode;
      
      redraw();
   }
}

void Display::toggleMode()
{
   mode = (DisplayMode)(mode + 1);
   if (mode >= DISPLAY_MODE_LAST)
   {
      mode = DISPLAY_MODE_FIRST;
   }
   
   // Skip splash screen.
   if (mode == SPLASH)
   {
      toggleMode();
   }
   
   redraw();
}
      
void Display::updateSplash(
   const String& splashImage)
{
   this->splashImage = splashImage;
   
   if (mode == SPLASH)
   {
      redraw();
   }
}
   
void Display::updateId(
   const String& uid)
{
   this->uid = uid;
   
   if (mode == ID)
   {
      redraw();
   }
}   
   
void Display::updateConnection(
   const String& ssid,
   const String& accessPoint,
   const bool& isConnected,
   const bool& isAccessPoint,
   const String& ipAddress,
   const String& apIpAddress)
{
   this->ssid = ssid;
   this->accessPoint = accessPoint;
   this->isConnected = isConnected;
   this->isAccessPoint = isAccessPoint;
   this->ipAddress = ipAddress;
   this->apIpAddress = apIpAddress;
   
   if (mode == CONNECTION)
   {
      redraw();
   }
}   
   
void Display::updateServer(
   const String& url,
   const bool& isConnected)
{
   this->serverUrl = url;
   this->isServerConnected = isConnected;
   
   if (mode == SERVER)
   {
      redraw();
   }
}

void Display::updateServer(
   const bool& isConnected)
{
   this->isServerConnected = isConnected;
   
   if (mode == SERVER)
   {
      redraw();
   }
}         
   
void Display::updateCount(
   const int& totalCount,
   const int& pendingCount)
{
   this->totalCount = totalCount;
   this->pendingCount = pendingCount;
   
   if (mode == COUNT)
   {
      redraw();
   }
}   
   
void Display::updateInfo(
   const String& version,
   const String& macAddress,
   const int& upTime,
   const int& freeMemory)      
{
   this->version = version;
   this->macAddress = macAddress;
   this->upTime = upTime;
   this->freeMemory = freeMemory;
   
   if (mode == INFO)
   {
      redraw();
   }
}

void Display::redraw()
{
   switch (mode)
   {
      case SPLASH:
      {
         drawSplash();
         break;
      }
      
      case ID:
      {
         drawId();
         break;
      }
      
      case CONNECTION:
      {
         drawConnection();
         break;
      }
      
      case SERVER:
      {
         drawServer();
         break;
      }        
      
      case COUNT:
      {
         drawCount();
         break;
      }
      
      case INFO:
      {
         drawInfo();
         break;
      }      
      
      default:
      {
      }
   }
}
   
// *****************************************************************************

void Display::drawSplash()
{
   M5.Lcd.fillScreen(backgroundColor);

   // UID
   M5.Lcd.setTextColor(accentColor);
   M5.Lcd.setTextSize(5);
   M5.Lcd.setTextDatum(BC_DATUM);  // Middle/center
   M5.Lcd.drawString("FACTORY", (M5.Lcd.width() / 2), (M5.Lcd.height() / 2) - MARGIN, font);
   M5.Lcd.setTextDatum(TC_DATUM);  // Middle/center
   M5.Lcd.drawString("STATS", (M5.Lcd.width() / 2), (M5.Lcd.height() / 2) + MARGIN, font);
}

void Display::drawId()
{
   M5.Lcd.fillScreen(backgroundColor);

   // Label
   M5.Lcd.setTextColor(accentColor);
   M5.Lcd.setTextSize(3);
   M5.Lcd.setTextDatum(TC_DATUM);  // Top/center
   M5.Lcd.drawString("Sensor ID", (M5.Lcd.width() / 2), MARGIN, font);

   // UID
   M5.Lcd.setTextColor(textColor);
   M5.Lcd.setTextSize(5);
   M5.Lcd.setTextDatum(MC_DATUM);  // Middle/center
   M5.Lcd.drawString(uid, (M5.Lcd.width() / 2), (M5.Lcd.height() / 2), font);
}   

void Display::drawConnection()
{
   M5.Lcd.fillScreen(backgroundColor);
   
   // Label
   M5.Lcd.setTextColor(accentColor);
   M5.Lcd.setTextSize(3);
   M5.Lcd.setTextDatum(TC_DATUM);  // Top/center

   if (isAccessPoint && !isConnected)
   {
      M5.Lcd.drawString("Wifi Setup", (M5.Lcd.width() / 2), MARGIN, font);
   }
   else
   {
      M5.Lcd.drawString("Connection", (M5.Lcd.width() / 2), MARGIN, font);
   }
   
   M5.Lcd.setTextColor(textColor);
   
   if (isConnected)
   {
      // SSID
      M5.Lcd.setTextSize(3);
      M5.Lcd.setTextDatum(MC_DATUM);  // Middle/center
      M5.Lcd.drawString(ssid, (M5.Lcd.width() / 2), (M5.Lcd.height() / 2), font);
   
      // IP address
      M5.Lcd.setTextSize(3);
      M5.Lcd.setTextDatum(BC_DATUM);  // Bottom/center
      M5.Lcd.drawString(ipAddress, (M5.Lcd.width() / 2), (M5.Lcd.height() - MARGIN), font);
   }
   else if (isAccessPoint)
   {
      // SSID
      M5.Lcd.setTextSize(3);
      M5.Lcd.setTextDatum(MC_DATUM);  // Middle/center
      M5.Lcd.drawString(accessPoint, (M5.Lcd.width() / 2), (M5.Lcd.height() / 2), font);
   
      // IP address
      M5.Lcd.setTextSize(3);
      M5.Lcd.setTextDatum(BC_DATUM);  // Bottom/center
      M5.Lcd.drawString(apIpAddress, (M5.Lcd.width() / 2), (M5.Lcd.height() - MARGIN), font);
   }
   else
   {
      // SSID
      M5.Lcd.setTextSize(3);
      M5.Lcd.setTextDatum(MC_DATUM);  // Middle/center
      M5.Lcd.drawString(ssid, (M5.Lcd.width() / 2), (M5.Lcd.height() / 2), font);
   
      // "Connecting..."
      M5.Lcd.setTextSize(3);
      M5.Lcd.setTextDatum(BC_DATUM);  // Bottom/center
      M5.Lcd.drawString("Connecting...", (M5.Lcd.width() / 2), (M5.Lcd.height() - MARGIN), font);
   }
}

void Display::drawServer()
{
   M5.Lcd.fillScreen(backgroundColor);

   // Label
   M5.Lcd.setTextColor(accentColor);
   M5.Lcd.setTextSize(3);
   M5.Lcd.setTextDatum(TC_DATUM);  // Top/center
   M5.Lcd.drawString("Server", (M5.Lcd.width() / 2), MARGIN, font);

   // Server
   M5.Lcd.setCursor(0, 50, font);
   M5.Lcd.setTextSize(2);
   M5.Lcd.setTextColor(highlightColor);
   M5.Lcd.printf("%s\n", serverUrl.c_str());
   
   // Connection status
   M5.Lcd.setTextSize(3);
   M5.Lcd.setTextColor(textColor);
   M5.Lcd.setTextDatum(BC_DATUM);  // Bottom/center
   String status = (serverUrl == "") ? "UNCONFIGURED" : (isServerConnected ? "CONNECTED" : "OFFLINE");
   M5.Lcd.drawString(status, (M5.Lcd.width() / 2), (M5.Lcd.height() - MARGIN), font);
}  

void Display::drawCount()
{
   M5.Lcd.fillScreen(backgroundColor);

   // Label
   M5.Lcd.setTextColor(accentColor);
   M5.Lcd.setTextSize(3);
   M5.Lcd.setTextDatum(TC_DATUM);  // Top/center
   M5.Lcd.drawString("Counter", (M5.Lcd.width() / 2), MARGIN, font);

   // Total count
   M5.Lcd.setTextColor(textColor);
   M5.Lcd.setTextSize(5);
   M5.Lcd.setTextDatum(MC_DATUM);  // Middle/center
   M5.Lcd.drawString(String(totalCount + pendingCount), (M5.Lcd.width() / 2), (M5.Lcd.height() / 2), font);

   // "No connection"   
   if (!isConnected)
   {
      M5.Lcd.setTextColor(RED);
      M5.Lcd.setTextSize(3);
      M5.Lcd.setTextDatum(BC_DATUM);  // Bottom/center
      M5.Lcd.drawString("No connection", (M5.Lcd.width() / 2), (M5.Lcd.height() - MARGIN), font);
   }
   // Pending count
   else if (pendingCount > 0)
   {
      M5.Lcd.setTextSize(3);
      M5.Lcd.setTextDatum(BC_DATUM);  // Bottom/center
      M5.Lcd.drawString("Tx: " + String(pendingCount), (M5.Lcd.width() / 2), (M5.Lcd.height() - MARGIN), font);
   }
}

void Display::drawInfo()
{
   static const int SECONDS_PER_DAY = (60 * 60 * 24);
   static const int SECONDS_PER_HOUR = (60 * 60);
   static const int SECONDS_PER_MINUTE = 60;
   static const int BYTES_IN_KILOBYTES = 1024;
   
   M5.Lcd.fillScreen(backgroundColor);

   // Label
   M5.Lcd.setTextColor(accentColor);
   M5.Lcd.setTextSize(3);
   M5.Lcd.setTextDatum(TC_DATUM);  // Top/center
   M5.Lcd.drawString("Info", (M5.Lcd.width() / 2), MARGIN, font);

   M5.Lcd.setCursor(0, 30, font);
   M5.Lcd.setTextSize(2);

   // Version
   M5.Lcd.setTextColor(textColor);
   M5.Lcd.printf("Version: ");
   M5.Lcd.setTextColor(highlightColor);
   M5.Lcd.printf("%s\n", version.c_str());
   
   // MAC address
   M5.Lcd.setTextColor(textColor);
   M5.Lcd.printf("M: ");
   M5.Lcd.setTextColor(highlightColor);
   M5.Lcd.printf("%s\n", macAddress.c_str());
   
   /*
   // IP address
   M5.Lcd.setTextColor(textColor);
   M5.Lcd.printf("IP: %s\n", "");  // TODO
   */
   
   // Up time
   int days = (upTime / SECONDS_PER_DAY);
   int hours = ((upTime % SECONDS_PER_DAY) / SECONDS_PER_HOUR);
   int minutes = ((upTime % SECONDS_PER_HOUR) / SECONDS_PER_MINUTE);
   
   M5.Lcd.setTextColor(textColor);
   M5.Lcd.printf("Up time: ");
   M5.Lcd.setTextColor(highlightColor);
   if (days > 0)
   {
      M5.Lcd.printf("%d d %d h\n", days, hours);   
   }
   else if (hours > 0)
   {
      M5.Lcd.printf("%d h %d m\n", hours, minutes);
   }
   else
   {
      M5.Lcd.printf("%d min\n", minutes);   
   }
   
   // Memory
   int freeKilobytes = (freeMemory / BYTES_IN_KILOBYTES);
   M5.Lcd.setTextColor(textColor);
   M5.Lcd.printf("Memory: ");
   M5.Lcd.setTextColor(highlightColor);
   M5.Lcd.printf("%dk\n", freeKilobytes);
}
