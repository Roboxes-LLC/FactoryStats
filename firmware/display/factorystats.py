# importing the requests library
import os 
import json
import requests
import shutil
import time

# Working directory
DIR = os.environ['HOME'] + "/factorystats"

# getMac script
GET_MAC = "%s/getMac.sh" % DIR

# GET_IP linux command
GET_IP = "hostname  -I | cut -f1 -d' ' | tr -d '\n' | tr -d '\r'"

# Host name from file
HOST_NAME = open("/etc/hostname").read().replace('\n','')

# server address from file
ORIG_SERVER = open("%s/server.txt" % DIR).read().replace('\n','')
SERVER = ORIG_SERVER
print("Read server: '%s'" % SERVER)

# IP address, via script
IP_ADDRESS = os.popen(GET_IP).read()
print("Read IP address: '%s'" % IP_ADDRESS)

# MAC address, via script
MAC_ADDRESS = os.popen(GET_MAC).read()
print("Read MAC address: '%s'" % MAC_ADDRESS)

# "Random" UID
UID = MAC_ADDRESS.upper().replace(":", "")[-6:]

# presentation (JSON string)
PRESENTATION = ""

# Global variable regulating ping rate (in seconds)
INIT_PING_RATE = 30
PING_RATE = INIT_PING_RATE

# Global variable tracking server availability.
SERVER_AVAILABLE = True

def updateIpAddress():
   global IP_ADDRESS
   IP_ADDRESS = os.popen(GET_IP).read()

def getUrl(server):
   return ("https://" + SERVER + "/api/display/")
   
def getParams():
   global UID
   global IP_ADDRESS
   global MAC_ADDRESS
   
   # Reaquire IP address, as it may have changed since the script loaded.
   updateIpAddress()
   
   return ({'uid':UID, 'ipAddress':IP_ADDRESS, 'macAddress':MAC_ADDRESS})

def pingServer():
   global URL
   global SERVER_AVAILABLE
   
   try:
      status = requests.get(url = URL, params = getParams())

      if (SERVER_AVAILABLE == False):
         SERVER_AVAILABLE = True
         print("Server ping succeeded")

      if (status.status_code == 200):
         try:
            response = status.json()
            processPingResult(response)
         except ValueError:
            print("Failed to parse response: %s" % status.content)
      else:
         print("Bad response code: %d" % status.status_code)     
          
   except (requests.exceptions.ConnectionError, requests.HTTPError):
      if (SERVER_AVAILABLE == True):
         SERVER_AVAILABLE = False
         print("Server ping failed")
         processNoConnection()

def processPingResult(response):
   global SERVER
   global URL
   global PRESENTATION
   global PING_RATE
   
   if (("success" in response) and (response["success"] == True)):
      # Process server redirection
      if "server" in response:
         SERVER = response["server"]
         URL = getUrl(SERVER)
         print("Redirecting to server: %s" % SERVER)
         
      # Process ping rate update
      if ("pingRate" in response):
         newPingRate = response["pingRate"]
         if (PING_RATE != newPingRate):
            PING_RATE = newPingRate
            print("Ping rate updated: %d" % PING_RATE)
      
      # Process presentation config
      if ("presentation" in response):
         newPresentation = json.dumps(response["presentation"])
         if (newPresentation != PRESENTATION):
            PRESENTATION = newPresentation         
            print("Updated presentation: %s" % response["presentation"])
            file = open("%s/www/presentation.json" % DIR, "w+")
            file.write(PRESENTATION)
            file.close()
   elif ("error" in response):
      print("Server error: %s" % response["error"])
   else:
      print("Undefined server error")

def processNoConnection():
   global SERVER
   global URL
   global PRESENTATION
   global PING_RATE   
         
   # Reset global variables
   SERVER = ORIG_SERVER
   URL = getUrl(SERVER)
   PRESENTATION = ""
   PING_RATE = INIT_PING_RATE

   # Copy UID into unconnected.html
   filename = "%s/www/unconnected.html" % DIR
   with open(filename) as file:
      content = file.read().replace("%UID", UID)
      file.close()
   with open(filename, "w") as file:
      file.write(content)
      file.close()
   
   # Copy unconnected.json into presentation.json
   unconnectedFilename = "%s/www/unconnected.json" % DIR
   presentationFilename = "%s/www/presentation.json" % DIR
   shutil.copyfile(unconnectedFilename, presentationFilename)
   with open(presentationFilename) as file:
      content = file.read()
      print("Updated presentation: %s" % content)
      file.close()

# Ping server every 30 seconds.
URL = getUrl(SERVER)
print("Pinging server at %s" % URL)
try:
    while (True):
        success = pingServer()
        time.sleep(PING_RATE)        
except KeyboardInterrupt:
    pass
