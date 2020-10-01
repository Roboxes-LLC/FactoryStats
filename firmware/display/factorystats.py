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
SERVER = open("%s/server.txt" % DIR).read().replace('\n','')
print("Read server: '%s'" % SERVER)

# IP address, via script
IP_ADDRESS = os.popen(GET_IP).read()
print("Read IP address: '%s'" % IP_ADDRESS)

# MAC address, via script
MAC_ADDRESS = os.popen(GET_MAC).read()
print("Read MAC address: '%s'" % MAC_ADDRESS)

# "Random" UID
UID = MAC_ADDRESS.upper().replace(":", "")[-6:]

# ping parameters
PARAMS = {'uid':UID, 'ipAddress':IP_ADDRESS, 'macAddress':MAC_ADDRESS}

# presentation (JSON string)
PRESENTATION = ""

def getUrl(server):
   return ("http://" + SERVER + "/api/display/")

def pingServer():
   global serverAvailable
   
   try:
      status = requests.get(url = URL, params = PARAMS)

      serverAvailable = True

      if (status.status_code == 200):
         try:
            response = status.json()
            processPingResult(response)
         except ValueError:
            print("Failed to parse response: %s" % status.content)
      else:
         print("Bad response code: %d" % status.status_code)     
          
   except (requests.exceptions.ConnectionError, requests.HTTPError):
      if (serverAvailable == True):
         serverAvailable = False
         print("Server ping failed")
         processNoConnection()

def processPingResult(response):
   global SERVER
   global URL
   global PRESENTATION
   
   if (("success" in response) and (response["success"] == True)):
      # Process server redirection
      if "server" in response:
         SERVER = response["server"]
         URL = getUrl(SERVER)
         print("Redirecting to server: %s" % SERVER)
      
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
      # Copy UID into unconnected.html
      filename = "%s/www/unconnected.html" % DIR
      with open(filename) as file:
         content = file.read().replace("%UID", UID)
      with open(filename, "w") as file:
         file.write(content)
   
      # Copy unconnected.json into presentation.json
      shutil.copyfile("%s/www/unconnected.json" % DIR, "%s/www/presentation.json" % DIR)

# Global variable tracking server availability.
serverAvailable = True

# Ping server every 30 seconds.
URL = getUrl(SERVER)
print("Pinging server at %s" % URL)
try:
    while (True):
        success = pingServer()
        time.sleep(30)        
except KeyboardInterrupt:
    pass
