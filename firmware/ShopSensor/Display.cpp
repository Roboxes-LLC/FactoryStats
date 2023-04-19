#include "Display.hpp"
#include "FactoryStatsDefs.hpp"
#include "M5Defs.hpp"

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

Display::Display(
   const String& id) :
      Component(id),
      mode(DisplayMode::UNKNOWN),
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
      stationId(UNKNOWN_STATION_ID),
      onBreak(false),
      upTime(0),
      freeMemory(0),
      batteryLevel(0),
      isUsbPower(false),
      isCharging(false),
      penX(0),
      penY(0)
{
}

Display::Display(
   MessagePtr message) :
      Component(message),
      mode(DisplayMode::UNKNOWN),
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
      stationId(UNKNOWN_STATION_ID),
      onBreak(false),
      upTime(0),
      freeMemory(0),
      batteryLevel(0),
      isUsbPower(false),
      isCharging(false),
      penX(0),
      penY(0)
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

   Serial.begin(9600);  // M5.begin() sets it 115200

   // Calculate useful positions.
   content = Zone(0, 0, M5.Lcd.width(), (M5.Lcd.height() - FOOTER));
   center = Point((content.x + (content.w / 2)), (content.y + (content.h / 2)));
   topMiddle = Point(center.x, content.y);
   bottomMiddle = Point(center.x, (content.y + content.h));
   
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
   
   // Skip certain screens, based on display type.
   if (skipMode(mode))
   {
      toggleMode();
   }
   
   redraw();
}

void Display::setRotation(
   const Rotation& rotation)
{
   M5.Lcd.setRotation(rotation);
}

Rotation Display::getRotation() const
{
   return (static_cast<Rotation>(M5.Lcd.getRotation()));
}
      
void Display::updateSplash(
   const String& splashImage,
   const bool& shouldRedraw)
{
   this->splashImage = splashImage;
   
   if (shouldRedraw && (mode == SPLASH))
   {
      redraw();
   }
}
   
void Display::updateId(
   const String& uid,
   const bool& shouldRedraw)
{
   this->uid = uid;
   
   if (shouldRedraw && (mode == ID))
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
   const String& apIpAddress,
   const bool& shouldRedraw)
{
   this->ssid = ssid;
   this->accessPoint = accessPoint;
   this->isConnected = isConnected;
   this->isAccessPoint = isAccessPoint;
   this->ipAddress = ipAddress;
   this->apIpAddress = apIpAddress;
   
   if (shouldRedraw && ((mode == CONNECTION) || (mode == COUNT)))
   {
      redraw();
   }
}   
   
void Display::updateServer(
   const String& url,
   const bool& isConnected,
   const bool& shouldRedraw)
{
   this->serverUrl = url;
   this->isServerConnected = isConnected;
   
   if (shouldRedraw && (mode == SERVER))
   {
      redraw();
   }
}

void Display::updateServer(
   const bool& isConnected,
   const bool& shouldRedraw)
{
   this->isServerConnected = isConnected;
   
   if (shouldRedraw && (mode == SERVER))
   {
      redraw();
   }
}         
   
void Display::updateCount(
   const int& totalCount,
   const int& pendingCount,
   const bool& shouldRedraw)
{
   this->totalCount = totalCount;
   this->pendingCount = pendingCount;
   
   if (shouldRedraw && (mode == COUNT))
   {
      redraw();
   }
}

void Display::updateStation(
   const int& stationId,
   const String& stationLabel,
   const bool& shouldRedraw)
{
   this->stationId = stationId;
   this->stationLabel = stationLabel;

   if (shouldRedraw && (mode == COUNT))
   {
      redraw();
   }
}

void Display::updateBreak(
   const bool& onBreak,
   const bool& shouldRedraw)
{
   this->onBreak = onBreak;

   if (shouldRedraw && (mode == COUNT))
   {
      redraw();
   }
}
   
void Display::updateInfo(
   const String& version,
   const String& macAddress,
   const int& upTime,
   const int& freeMemory,
   const bool& shouldRedraw)
{
   this->version = version;
   this->macAddress = macAddress;
   this->upTime = upTime;
   this->freeMemory = freeMemory;
   
   if (shouldRedraw && (mode == INFO))
   {
      redraw();
   }
}

void Display::updatePower(
   const int& batteryLevel,
   const bool& isUsbPower,
   const bool& isCharging,
   const bool& shouldRedraw)
{
   this->batteryLevel = batteryLevel;
   this->isUsbPower = isUsbPower;
   this->isCharging = isCharging;
   
   if (shouldRedraw && (mode == POWER))
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
      
      case POWER:
      {
         drawPower();
         break;
      }

      case ROTATION:
      {
         drawRotation();
         break;
      }
      
      default:
      {
         M5.Lcd.fillScreen(backgroundColor);
         break;
      }
   }
}
   
// *****************************************************************************

bool Display::skipMode(
   const DisplayMode& mode) const
{
   return ((mode == DisplayMode::SPLASH) ||
           (mode == DisplayMode::ROTATION));  // Skip ROTATE mode in M5Stick.
}

void Display::drawSplash()
{
   M5.Lcd.fillScreen(backgroundColor);

   // Factory Stats
   M5.Lcd.setTextColor(accentColor);
   M5.Lcd.setTextSize(FONT_XLARGE);
   M5.Lcd.setTextDatum(BC_DATUM);  // Bottom/center
   M5.Lcd.drawString("FACTORY", center.x, (center.y - MARGIN), font);
   M5.Lcd.setTextDatum(TC_DATUM);  // Top/center
   M5.Lcd.drawString("STATS", center.x, (center.y + MARGIN), font);
}

void Display::drawId()
{
   M5.Lcd.fillScreen(backgroundColor);

   // Label
   M5.Lcd.setTextColor(accentColor);
   M5.Lcd.setTextSize(FONT_MEDIUM);
   M5.Lcd.setTextDatum(TC_DATUM);  // Top/center
   M5.Lcd.drawString("Sensor ID", topMiddle.x, (topMiddle.y + MARGIN), font);

   // UID
   M5.Lcd.setTextColor(textColor);
   M5.Lcd.setTextSize(FONT_XLARGE);
   M5.Lcd.setTextDatum(MC_DATUM);  // Middle/center
   M5.Lcd.drawString(uid, center.x, center.y, font);
}   

void Display::drawConnection()
{
   M5.Lcd.fillScreen(backgroundColor);
   
   // Label
   M5.Lcd.setTextColor(accentColor);
   M5.Lcd.setTextSize(FONT_MEDIUM);
   M5.Lcd.setTextDatum(TC_DATUM);  // Top/center

   if (isAccessPoint && !isConnected)
   {
      M5.Lcd.drawString("Wifi Setup", topMiddle.x, (topMiddle.y + MARGIN), font);
   }
   else
   {
      M5.Lcd.drawString("Connection", topMiddle.x, (topMiddle.y + MARGIN), font);
   }
   
   M5.Lcd.setTextColor(textColor);
   
   if (isConnected)
   {
      // SSID
      M5.Lcd.setTextSize(FONT_MEDIUM);
      M5.Lcd.setTextDatum(MC_DATUM);  // Middle/center
      M5.Lcd.drawString(ssid, center.x, center.y, font);
   
      // IP address
      M5.Lcd.setTextSize(FONT_MEDIUM);
      M5.Lcd.setTextDatum(BC_DATUM);  // Bottom/center
      M5.Lcd.drawString(ipAddress, bottomMiddle.x, (bottomMiddle.y - MARGIN), font);
   }
   else if (isAccessPoint)
   {
      // SSID
      M5.Lcd.setTextSize(FONT_MEDIUM);
      M5.Lcd.setTextDatum(MC_DATUM);  // Middle/center
      M5.Lcd.drawString(accessPoint, center.x, center.y, font);
   
      // IP address
      M5.Lcd.setTextSize(FONT_MEDIUM);
      M5.Lcd.setTextDatum(BC_DATUM);  // Bottom/center
      M5.Lcd.drawString(apIpAddress, bottomMiddle.x, (bottomMiddle.y - MARGIN), font);
   }
   else
   {
      // SSID
      M5.Lcd.setTextSize(FONT_MEDIUM);
      M5.Lcd.setTextDatum(MC_DATUM);  // Middle/center
      M5.Lcd.drawString(ssid, center.x, center.y, font);
   
      // "Connecting..."
      M5.Lcd.setTextSize(FONT_MEDIUM);
      M5.Lcd.setTextDatum(BC_DATUM);  // Bottom/center
      M5.Lcd.drawString("Connecting...", bottomMiddle.x, (bottomMiddle.y - MARGIN), font);
   }
}

void Display::drawServer()
{
   M5.Lcd.fillScreen(backgroundColor);

   // Label
   M5.Lcd.setTextColor(accentColor);
   M5.Lcd.setTextSize(FONT_MEDIUM);
   M5.Lcd.setTextDatum(TC_DATUM);  // Top/center
   M5.Lcd.drawString("Server", topMiddle.x, (topMiddle.y + MARGIN), font);

   // Server
   M5.Lcd.setCursor(0, (center.y - MARGIN), font);
   M5.Lcd.setTextSize(FONT_SMALL);
   M5.Lcd.setTextColor(highlightColor);
   M5.Lcd.printf("%s\n", serverUrl.c_str());
   
   // Connection status
   M5.Lcd.setTextSize(FONT_MEDIUM);
   M5.Lcd.setTextColor(textColor);
   M5.Lcd.setTextDatum(BC_DATUM);  // Bottom/center
   String status = (serverUrl == "") ? "UNCONFIGURED" : (isServerConnected ? "CONNECTED" : "OFFLINE");
   M5.Lcd.drawString(status, bottomMiddle.x, (bottomMiddle.y - MARGIN), font);
}  

void Display::drawCount()
{
   M5.Lcd.fillScreen(backgroundColor);

   // Label
   M5.Lcd.setTextColor(accentColor);
   M5.Lcd.setTextSize(FONT_MEDIUM);
   M5.Lcd.setTextDatum(TC_DATUM);  // Top/center
   M5.Lcd.drawString("Counter", (M5.Lcd.width() / 2), MARGIN, font);

   // Total count
   M5.Lcd.setTextColor(textColor);
   M5.Lcd.setTextSize(FONT_XLARGE);
   M5.Lcd.setTextDatum(MC_DATUM);  // Middle/center
   M5.Lcd.drawString(String(totalCount + pendingCount), (M5.Lcd.width() / 2), (M5.Lcd.height() / 2), font);

   // "No connection"   
   if (!isConnected)
   {
      M5.Lcd.setTextColor(RED);
      M5.Lcd.setTextSize(FONT_MEDIUM);
      M5.Lcd.setTextDatum(BC_DATUM);  // Bottom/center
      M5.Lcd.drawString("No connection", (M5.Lcd.width() / 2), (M5.Lcd.height() - MARGIN), font);
   }
   // Pending count
   else if (pendingCount > 0)
   {
      M5.Lcd.setTextSize(FONT_MEDIUM);
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

#ifdef M5TOUGH
   static const int SPACER = 60;
#else
   static const int SPACER = 30;
#endif
   
   M5.Lcd.fillScreen(backgroundColor);

   // Label
   M5.Lcd.setTextColor(accentColor);
   M5.Lcd.setTextSize(FONT_MEDIUM);
   M5.Lcd.setTextDatum(TC_DATUM);  // Top/center
   M5.Lcd.drawString("Info", topMiddle.x, (topMiddle.y + MARGIN), font);

   M5.Lcd.setCursor(0, SPACER, font);
   M5.Lcd.setTextSize(FONT_SMALL);

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

void Display::drawPower()
{
#if defined(M5STICKC)
   static const int BATTERY_X = 35;
   static const int BATTERY_Y = 20;
   static const float BATTERY_SCALE = 4.5;
#elif defined(M5STICKC_PLUS)
   static const int BATTERY_X = 55;
   static const int BATTERY_Y = 35;
   static const float BATTERY_SCALE = 7.0;
#elif defined(M5TOUGH)
   static const int BATTERY_X = 75;
   static const int BATTERY_Y = 60;
   static const float BATTERY_SCALE = 10;
#endif   

   M5.Lcd.fillScreen(backgroundColor);

   // Label
   M5.Lcd.setTextColor(accentColor);
   M5.Lcd.setTextSize(FONT_MEDIUM);
   M5.Lcd.setTextDatum(TC_DATUM);  // Top/center
   M5.Lcd.drawString("Power", topMiddle.x, (topMiddle.y + MARGIN), font);

   M5.Lcd.setCursor(0, 30, font);
   M5.Lcd.setTextSize(FONT_SMALL);
   
   // Power source
   if (isUsbPower)
   {
      // USB
      M5.Lcd.setTextColor(highlightColor);
      M5.Lcd.setTextSize(FONT_LARGE);
      M5.Lcd.setTextDatum(MC_DATUM);  // Middle/center
      M5.Lcd.drawString("USB", center.x, center.y, font);
      
      // Charging status
      M5.Lcd.setTextSize(FONT_MEDIUM);
      M5.Lcd.setTextColor(textColor);
      M5.Lcd.setTextDatum(BC_DATUM);  // Bottom/center
      String status = (isCharging ? "CHARGING ..." : "CHARGED");
      M5.Lcd.drawString(status, bottomMiddle.x, (bottomMiddle.y - MARGIN), font);
   }
   else
   {
      // Battery
      drawBattery(BATTERY_X, BATTERY_Y, BATTERY_SCALE, highlightColor, batteryLevel);
      
      // Battery level
      M5.Lcd.setTextSize(FONT_MEDIUM);
      M5.Lcd.setTextColor(textColor);
      M5.Lcd.setTextDatum(BC_DATUM);  // Bottom/center
      String status = String(batteryLevel) + "%";
      M5.Lcd.drawString(status, bottomMiddle.x, (bottomMiddle.y - MARGIN), font);
   }
}

void Display::drawRotation()
{
   M5.Lcd.fillScreen(backgroundColor);

   // Label
   M5.Lcd.setTextColor(accentColor);
   M5.Lcd.setTextSize(FONT_MEDIUM);
   M5.Lcd.setTextDatum(TC_DATUM);  // Top/center
   M5.Lcd.drawString("Rotation", topMiddle.x, (topMiddle.y + MARGIN), font);
}

void Display::drawBattery(
   const int& x,
   const int& y,
   const float& scale,
   const int& color,
   const int& batteryLevel)
{
   setPen(x, y);
   
   // Battery
   lineTo(17,  0,  scale, color);
   lineTo(0,   2,  scale, color);
   lineTo(2,   0,  scale, color);
   lineTo(0,   4,  scale, color);
   lineTo(-2,  0,  scale, color);
   lineTo(0,   2,  scale, color);
   lineTo(-17, 0,  scale, color);
   lineTo(0,   -8, scale, color);
   
   // Bars
   int bars = ceil((float)batteryLevel / 25.0);
   int barColor = (bars > 1) ? GREEN : WHITE;
   for (int bar = 0; bar < bars; bar++)
   {
      M5.Lcd.fillRect(
         (x + (bar * 3 * scale) + (1 * (bar + 1)) * scale), 
         (y + (1 * scale)),
         (3 * scale),
         (6 * scale), 
         barColor);
   }
}

void Display::setPen(
   const int& x, 
   const int& y)
{
   penX = x;
   penY = y;
}

void Display::lineTo(
   const int& x, 
   const int& y,
   const float& scale,
   const int& color)
{
   M5.Lcd.drawLine(penX, penY, (penX + (x * scale)), (penY + (y * scale)), color);
   moveTo(x, y, scale);
}

void Display::moveTo(
   const int& x, 
   const int& y,
   const float& scale)
{
   penX = (penX + (x * scale));
   penY = (penY + (y * scale));
}
