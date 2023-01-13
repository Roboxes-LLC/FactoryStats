#pragma once

#include "M5Defs.hpp"
#include "Messaging/Component.hpp"
#include "Messaging/ComponentFactory.hpp"

static const int DEFAULT_FONT = 1;
static const int DEFAULT_BACKGROUND_COLOR = BLACK;
static const int DEFAULT_TEXT_COLOR = YELLOW;
static const int DEFAULT_ACCENT_COLOR = BLUE;
static const int DEFAULT_HIGHLIGHT_COLOR = WHITE;

#ifdef M5TOUGH
static const int FONT_SMALL = 2;
static const int FONT_MEDIUM = 3;
static const int FONT_LARGE = 4;
static const int FONT_XLARGE = 7;
static const int MARGIN = 10;
static const int FOOTER = 40;
#elif M5STICKC_PLUS
static const int FONT_SMALL = 2;
static const int FONT_MEDIUM = 3;
static const int FONT_LARGE = 4;
static const int FONT_XLARGE = 5;
static const int MARGIN = 5;
static const int FOOTER = 0;
#else
static const int FONT_SMALL = 1;
static const int FONT_MEDIUM = 2;
static const int FONT_LARGE = 3;
static const int FONT_XLARGE = 4;
static const int MARGIN = 5;
static const int FOOTER = 0;
#endif

class Display : public Component
{

public:

   enum DisplayMode
   {
      DISPLAY_MODE_FIRST = 0,
      SPLASH = DISPLAY_MODE_FIRST,
      COUNT,
      DISPLAY_MODE_INFO_FIRST,
      ID = DISPLAY_MODE_INFO_FIRST,
      CONNECTION,
      SERVER,
      INFO,
      POWER,
      DISPLAY_MODE_INFO_LAST = POWER,
      DISPLAY_MODE_LAST      
   };
   
   Display(
      const String& id);

   Display(
      MessagePtr message);      
   
   virtual ~Display();   

   virtual void setup();

   virtual void loop();

   // This operation handles a message directed to this sensor.
   virtual void handleMessage(
      // The message to handle.
      MessagePtr message);

   DisplayMode getMode();

   void setMode(
      const DisplayMode& mode);
      
   void toggleMode();      
      
   void updateSplash(
      const String& splashImage,
      const bool& shouldRedraw = true);
      
   void updateId(
      const String& uid,
      const bool& shouldRedraw = true);
      
   void updateConnection(
      const String& ssid,
      const String& accessPoint,
      const bool& isConnected,
      const bool& isAccessPoint,
      const String& ipAddress,
      const String& apIpAddress,
      const bool& shouldRedraw = true);
      
   void updateServer(
      const String& url,
      const bool& isConnected,
      const bool& shouldRedraw = true);
      
   void updateServer(
      const bool& isConnected,
      const bool& shouldRedraw = true);
      
   virtual void updateCount(
      const int& totalCount,
      const int& pendingCount,
      const bool& shouldRedraw = true);

   virtual void updateStation(
      const int& stationId,
      const String& stationLabel,
      const bool& shouldRedraw = true);

   virtual void updateBreak(
      const bool& onBreak,
      const bool& shouldRedraw = true);
      
   void updateInfo(
      const String& version,
      const String& macAddress,
      const int& upTime,
      const int& freeMemory,
      const bool& shouldRedraw = true);
      
   void updatePower(
      const int& batteryLevel,
      const bool& isUsbPower,
      const bool& isCharging,
      const bool& shouldRedraw = true);
      
   virtual void redraw();
   
protected:

   virtual void drawSplash();
   
   virtual void drawId();
   
   virtual void drawConnection();
   
   virtual void drawServer();
   
   virtual void drawCount();
   
   virtual void drawInfo();
   
   virtual void drawPower();

   void drawBattery(
      const int& x,
      const int& y,
      const float& scale,
      const int& color,
      const int& batteryLevel);
      
   void setPen(
      const int& x, 
      const int& y);
   
   void lineTo(
      const int& x, 
      const int& y,
      const float& scale,
      const int& color);
   
   void moveTo(
      const int& x, 
      const int& y,
      const float& scale);
      
   // ********************************************************************

   // Useful regions and points.
   Zone content;
   Point center;
   Point topMiddle;
   Point bottomMiddle;
   
   DisplayMode mode;
   
   int font;
   
   int backgroundColor;
   
   int textColor;
   
   int accentColor;
   
   int highlightColor;
   
   // Splash
   
   String splashImage;
   
   // ID
   
   String uid;
   
   // Connection
   
   String ssid;
   
   String accessPoint;
   
   bool isConnected;
   
   bool isAccessPoint;
   
   String ipAddress;
   
   String apIpAddress;
   
   // Server
   
   String serverUrl;
   
   bool isServerConnected;
   
   // Count
   
   int totalCount;
   
   int pendingCount;
   
   // Station

   int stationId;

   String stationLabel;

   int onBreak;

   // Diagnostics
   
   String version;
      
   String macAddress;
   
   int upTime;  // seconds
   
   int freeMemory;
   
   // Power
   
   int batteryLevel;
   
   bool isUsbPower;
   
   bool isCharging;
   
   // Pen
   
   int penX;
   int penY;
};

REGISTER(Display, Display)
