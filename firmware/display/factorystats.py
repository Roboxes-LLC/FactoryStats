# importing the requests library
import os 
import json
import requests
import shutil
import time
import zipfile

# Working directory
DIR = os.environ['HOME'] + "/factorystats"

# Config directory
CONFIG_DIR = DIR + "/config"

# Utils directory
UTILS_DIR = DIR + "/utils"

# Temp directory
TEMP_DIR = "/tmp/factorystats/"

# getMac script
GET_MAC = "%s/getMac.sh" % UTILS_DIR

# GET_IP linux command
GET_IP = "hostname  -I | cut -f1 -d' ' | tr -d '\n' | tr -d '\r'"

# Host name from file
HOST_NAME = open("/etc/hostname").read().replace('\n','')

# server address from file
ORIG_SERVER = open("%s/server.txt" % CONFIG_DIR).read().replace('\n','')
SERVER = ORIG_SERVER
print("Read server: '%s'" % SERVER)

# IP address, via script
IP_ADDRESS = os.popen(GET_IP).read()
print("Read IP address: '%s'" % IP_ADDRESS)

# version from file
VERSION = open("%s/version.txt" % CONFIG_DIR).read().replace('\n','')
print("Read version: '%s'" % VERSION)

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

# HTTP string
# Uncomment for production
HTTP = "https"
# Uncomment for local testing
#HTTP = "http"

def updateIpAddress():
   global IP_ADDRESS
   IP_ADDRESS = os.popen(GET_IP).read()

def getUrl(server):
   global HTTP
   return ("%s://%s/api/display/" % (HTTP, server))
   
def getFirmwareUrl(server, imageName):
   global HTTP
   return ("%s://%s/firmware/display/%s" % (HTTP, server, imageName))
   
def getParams():
   global UID
   global IP_ADDRESS
   global MAC_ADDRESS
   
   # Reaquire IP address, as it may have changed since the script loaded.
   updateIpAddress()
   
   return ({'uid':UID, 'ipAddress':IP_ADDRESS, 'version':VERSION, 'macAddress':MAC_ADDRESS})

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
      
      # Process firmware upgrade request
      if (("upgradePending" in response) and ("firmwareImage" in response)):
         firmwareUpdate(response["firmwareImage"])
      
      # Process reset request
      if ("resetPending" in response):
         print("Rebooting device ...")
         os.system("sudo reboot");
            
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

def firmwareUpdate(imageName):
   global DIR
   global SERVER
   
   url = getFirmwareUrl(SERVER, imageName)
   
   # Target directories
   unzipDir = "%s%s/" % (TEMP_DIR, os.path.splitext(imageName)[0])
   
   destination = "%s%s" % (TEMP_DIR, imageName)
   
   # Make temp folder
   os.system("mkdir %s" % TEMP_DIR);
   
   # Download file
   print("Downloading from %s ..." % url)
   with open(destination, "wb") as f:
      r = requests.get(url)
      f.write(r.content)
      
   # Unzip
   print("Unzipping firmware ...")
   with zipfile.ZipFile(destination, 'r') as zip_ref:
      zip_ref.extractall(TEMP_DIR)
      
   # Copy
   print("Copying files ...")
   os.system("cp -r %s/*.* %s" % (unzipDir, DIR))  
   
   # Reboot
   print("Firmware updated.  Rebooting device ...")
   os.system("sudo reboot");       

# Ping server every PING_RATE seconds.
URL = getUrl(SERVER)
print("Pinging server at %s" % URL)
try:
    while (True):
        success = pingServer()
        time.sleep(PING_RATE)        
except KeyboardInterrupt:
    pass
