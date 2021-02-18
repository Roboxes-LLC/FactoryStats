#include "Logger/Logger.hpp"
#include "Properties/Properties.hpp"
#include "ConnectionManager.hpp"
#include "Robox.hpp"

const String ConnectionManager::CONNECTION = "CONNECTION";
const String ConnectionManager::AP_STARTED = "AP_STARTED";
const String ConnectionManager::AP_STOPPED = "AP_STOPPED";
const String ConnectionManager::WIFI_CONNECTING = "WIFI_CONNECTING";
const String ConnectionManager::WIFI_CONNECTED = "WIFI_CONNECTED";
const String ConnectionManager::WIFI_DISCONNECTED = "WIFI_DISCONNECTED";

static const int FAILURE_THRESHOLD = 3;

ConnectionManager::ConnectionManager(
   const String& id,
   const int& connectTimeout) :
      Component(id),
      mode(OFFLINE),
      connectTimeout(connectTimeout),
      m_isAPRunning(false),
      m_isWifiConnected(false),
      failureCount(0),
      board(0),
      connectTimer(0)
{
}
   
ConnectionManager::ConnectionManager(
   MessagePtr message) :
      Component(message),
      mode(OFFLINE),
      connectTimeout(message->getInt("connectTimeout")),
      m_isAPRunning(false),
      m_isWifiConnected(false),
      failureCount(0),
      board(0),
      connectTimer(0)
{
}      

ConnectionManager::~ConnectionManager()
{
}   

void ConnectionManager::setup()
{
   board = WifiBoard::getBoard();
   
   loadConfig();
}

void ConnectionManager::loop()
{
   updateAP();
   
   updateWifi();
}

void ConnectionManager::handleMessage(
   MessagePtr message)
{
}
   
void ConnectionManager::timeout(
   Timer* timer)
{
   if (timer == connectTimer)
   {
      connectTimer = 0;
      
      failureCount++;      
   
      Logger::logDebug(F("ConnectionManager::timeout: Failed to connect wifi to %s (x%d)"), 
                       wifiConfig.ssid.c_str(),
                       failureCount);
                       
      // Retry
      connectWifi();
   }
}          

void ConnectionManager::setMode(
   const ConnectionMode& mode)
{
   this->mode = mode;
}

WifiConfig ConnectionManager::getWifiConfig() const
{
   return (wifiConfig);
}

WifiConfig ConnectionManager::getAPConfig() const
{
   return (apConfig);
}

bool ConnectionManager::isWifiConnected() const
{
   return (m_isWifiConnected);
}

bool ConnectionManager::isAPRunning() const
{
   return (m_isAPRunning);
}

String ConnectionManager::getIpAddress() const
{
   return ((board && board->isConnected()) ? board->getIpAddress() : "");   
}

String ConnectionManager::getAPIpAddress() const
{
   return ((board && m_isAPRunning) ? board->getAPIpAddress() : "");
}
   
// **************************************************************************

void ConnectionManager::loadConfig()
{
   Properties& properties = Robox::getProperties();
   
   mode = parseConnectionMode(properties.getString("mode"));

   if (properties.isSet("ap.ssid"))
   {
      apConfig.ssid = properties.getString("ap.ssid");
      apConfig.password = properties.getString("ap.password");
   }
   else if (properties.isSet("deviceName"))
   {
      apConfig.ssid = properties.getString("deviceName") + "_" + getUid();
   }
   else
   {
      apConfig.ssid = "ROBOXES_" + getUid();
   }
      
   wifiConfig.ssid = properties.getString("wifi.ssid");
   wifiConfig.password = properties.getString("wifi.password");
}

void ConnectionManager::updateAP()
{
   if (board)
   {
      bool requireAP = ((mode == ACCESS_POINT) || 
                        (mode == ACCESS_POINT_PLUS_WIFI) ||
                        ((mode == WIFI) && (m_isWifiConnected == false) && (failureCount > FAILURE_THRESHOLD)));
                        
      if (!m_isAPRunning && requireAP && (apConfig.ssid != ""))
      {
         String ipAddress = board->getAPIpAddress();
               
         Logger::logDebug(F("ConnectionManager::updateAP: Started access point %s"), apConfig.ssid.c_str());
         
         broadcastAPStarted(apConfig.ssid, ipAddress);
      
         board->startAccessPoint(apConfig.ssid, apConfig.password);
         
         m_isAPRunning = true;  // Should be immediate, with no chance of failure.
      }
      else if (m_isAPRunning && !requireAP)
      {
         Logger::logDebug(F("ConnectionManager::updateAP: Stopped access point %s"), apConfig.ssid.c_str());
         
         broadcastAPStopped(apConfig.ssid);
      
         board->stopAccessPoint();
      }
   }
}

void ConnectionManager::updateWifi()
{
   if (board)
   {
      bool wasWifiConnected = m_isWifiConnected;
      
      m_isWifiConnected = board->isConnected();
      
      bool requireWifiConnected = ((mode == WIFI) || (mode == ACCESS_POINT_PLUS_WIFI));
      
      if (!wasWifiConnected && m_isWifiConnected)
      {
         String ipAddress = board->getIpAddress();
      
         Logger::logDebug(F("ConnectionManager::updateWifi: Wifi connected to %s at %s"), wifiConfig.ssid.c_str(), ipAddress.c_str());
      
         broadcastWifiConnected(wifiConfig.ssid, ipAddress);
         
         stopTimers();
         
         failureCount = 0;
      }
      else if (wasWifiConnected && !m_isWifiConnected)
      {
         Logger::logDebug(F("ConnectionManager::updateWifi: Wifi disconnected from %s"), wifiConfig.ssid.c_str());
      
         broadcastWifiDisconnected(wifiConfig.ssid);
      }
      
      if (requireWifiConnected && 
          !m_isWifiConnected && 
          !connectTimer)
      {
         connectWifi();
      }
      else if (!requireWifiConnected &&
               m_isWifiConnected)
      {
         disconnectWifi();
         
         stopTimers();
      } 
   }  
}

void ConnectionManager::connectWifi()
{
   if ((board) &&
       (wifiConfig.ssid != ""))
   {
      Logger::logDebug(F("ConnectionManager::connectWifi: %s to %s"), 
                      (failureCount > 0) ? "Retrying wifi connection" : "Connecting wifi",
                      wifiConfig.ssid.c_str());
                      
      broadcastWifiConnecting(wifiConfig.ssid);
   
      board->connectWifi(wifiConfig.ssid, wifiConfig.password, 0);
      
      startConnectTimer();
   }
}

void ConnectionManager::disconnectWifi()
{
   if (board)
   {
      Logger::logDebug(F("ConnectionManager::disconnectWifi: Disconnecting wifi from %s"), wifiConfig.ssid.c_str());
   
      board->disconnectWifi();
      
      startConnectTimer();
   }
}

bool ConnectionManager::startConnectTimer()
{
   if (connectTimer)
   {
      connectTimer->reset();
   }
   else
   {
      connectTimer = Timer::newTimer(
         "connect",
         (connectTimeout * 1000),
         Timer::ONE_SHOT,
         this);
         
      if (connectTimer)
      {
         connectTimer->start();
      }
   }
   
   return (connectTimer != 0);
}

bool ConnectionManager::stopTimers()
{
   if (connectTimer)
   {
      Timer::freeTimer(connectTimer);
      connectTimer = 0;
   }
}

bool ConnectionManager::broadcastAPStarted(
   const String& ssid,
   const String& ipAddress)
{
   bool success = false;
   
   MessagePtr message = Messaging::newMessage();
   
   if (message)
   {
      message->setTopic(CONNECTION);
      message->setMessageId(AP_STARTED);
      message->setSource(getId());
      message->set("ssid", ssid);
      message->set("ipAddress", ipAddress);
      
      success = Messaging::publish(message);
   }
   
   return (success);
}

bool ConnectionManager::broadcastAPStopped(
   const String& ssid)
{
   bool success = false;
   
   MessagePtr message = Messaging::newMessage();
   
   if (message)
   {
      message->setTopic(CONNECTION);
      message->setMessageId(AP_STOPPED);
      message->setSource(getId());
      message->set("ssid", ssid);
      
      success = Messaging::publish(message);
   }
   
   return (success);
}

bool ConnectionManager::broadcastWifiConnecting(
   const String& ssid)
{
   bool success = false;
   
   MessagePtr message = Messaging::newMessage();
   
   if (message)
   {
      message->setTopic(CONNECTION);
      message->setMessageId(WIFI_CONNECTING);
      message->setSource(getId());
      message->set("ssid", ssid);
      
      success = Messaging::publish(message);
   }
   
   return (success);
}

bool ConnectionManager::broadcastWifiConnected(
   const String& ssid,
   const String& ipAddress)
{
   bool success = false;
   
   MessagePtr message = Messaging::newMessage();
   
   if (message)
   {
      message->setTopic(CONNECTION);
      message->setMessageId(WIFI_CONNECTED);
      message->setSource(getId());
      message->set("ssid", ssid);
      message->set("ipAddress", ipAddress);
      
      success = Messaging::publish(message);
   }
   
   return (success);
}

bool ConnectionManager::broadcastWifiDisconnected(
   const String& ssid)
{
   bool success = false;
   
   MessagePtr message = Messaging::newMessage();
   
   if (message)
   {
      message->setTopic(CONNECTION);
      message->setMessageId(WIFI_DISCONNECTED);
      message->setSource(getId());
      message->set("ssid", ssid);
      
      success = Messaging::publish(message);
   }
   
   return (success);
}

String ConnectionManager::getUid()
{
   String uid = "";
   
   WifiBoard* board = WifiBoard::getBoard();
   
   if (board)
   {
      // Get the MAC address.
      unsigned char mac[6] = {0, 0, 0, 0, 0, 0};
      WifiBoard::getBoard()->getMacAddress(mac);
      
      // Last six hex digits of MAC address.
      char uidStr[7];
      sprintf(uidStr, "%02X%02X%02X", mac[3], mac[4], mac[5]);
      uid[6] = 0;  // Null terminate.
      
      uid = String(uidStr);
   }
   
   return (uid);   
}