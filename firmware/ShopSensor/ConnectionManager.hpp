#pragma once

#include "Board/WifiBoard.hpp"
#include "Connection/ConnectionDefs.hpp"
#include "Messaging/Component.hpp"
#include "Messaging/ComponentFactory.hpp"
#include "Timer/Timer.hpp"
#include "Timer/TimerListener.hpp"

class ConnectionManager : public Component, TimerListener
{

public:

   // Messaging constants.
   static const String CONNECTION;
   static const String AP_STARTED;
   static const String AP_STOPPED;
   static const String WIFI_CONNECTING;
   static const String WIFI_CONNECTED;
   static const String WIFI_DISCONNECTED;

   ConnectionManager(
      const String& id,
      const int& connectTimeout);
      
   ConnectionManager(
      MessagePtr message);      
   
   virtual ~ConnectionManager();   

   virtual void setup();

   virtual void loop();

   virtual void handleMessage(
      MessagePtr message);
      
   virtual void timeout(
      Timer* timer);          

   void setMode(
      const ConnectionMode& mode);
      
   WifiConfig getWifiConfig() const;
   
   WifiConfig getAPConfig() const;
      
   bool isWifiConnected() const;
   
   bool isAPRunning() const;
   
   String getIpAddress() const;
   
   String getAPIpAddress() const;
   
private:

   void loadConfig();
   
   void updateAP();
   
   void updateWifi();
   
   void connectWifi();
   
   void disconnectWifi();
   
   bool startConnectTimer();
   
   bool startRetryTimer();
   
   bool stopTimers();
   
   bool broadcastAPStarted(
      const String& ssid,
      const String& ipAddress);
      
   bool broadcastAPStopped(
      const String& ssid);   
   
   bool broadcastWifiConnecting(
      const String& ssid);
   
   bool broadcastWifiConnected(
      const String& ssid,
      const String& ipAddress);
      
   bool broadcastWifiDisconnected(
      const String& ssid);
      
   String getUid();      
   
   // **************************************************************************

   ConnectionMode mode;
   
   WifiConfig apConfig;
   
   WifiConfig wifiConfig;
   
   int connectTimeout;
   
   bool m_isAPRunning;
   
   bool m_isWifiConnected;
   
   int failureCount;
   
   WifiBoard* board;
   
   Timer* connectTimer;
};

REGISTER(ConnectionManager, ConnectionManager)
