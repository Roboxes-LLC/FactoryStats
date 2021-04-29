#pragma once

#include <RFC.h>

#include "Messaging/Component.hpp"
#include "Messaging/ComponentFactory.hpp"

class Display : public Component
{

public:

   enum DisplayMode
   {
      DISPLAY_MODE_FIRST = 0,
      SPLASH = DISPLAY_MODE_FIRST,
      ID,
      CONNECTION,
      SERVER,
      COUNT,
      INFO,
      POWER,
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
      const String& splashImage);
      
   void updateId(
      const String& uid);
      
   void updateConnection(
      const String& ssid,
      const String& accessPoint,
      const bool& isConnected,
      const bool& isAccessPoint,
      const String& ipAddress,
      const String& apIpAddress);
      
   void updateServer(
      const String& url,
      const bool& isConnected);      
      
   void updateServer(
      const bool& isConnected);      
      
   void updateCount(
      const int& totalCount,
      const int& pendingCount);
      
   void updateInfo(
      const String& version,
      const String& macAddress,
      const int& upTime,
      const int& freeMemory);      
      
   void updatePower(
      const int& batteryLevel,
      const bool& isUsbPower,
      const bool& isCharging);      
      
   void redraw();
   
private:

   void drawSplash();
   
   void drawId();   
   
   void drawConnection();
   
   void drawServer();
   
   void drawCount();
   
   void drawInfo();
   
   void drawPower();
   
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
