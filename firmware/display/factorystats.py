# importing the requests library
import os 
import json
import requests
import time

# getMac script
GET_MAC = "/home/pi/getMac.sh"

# GET_IP linux command
GET_IP = "hostname  -I | cut -f1 -d' ' | tr -d '\n' | tr -d '\r'"

# kiosk script
KIOSK = "bash /home/pi/kiosk.sh"

# Host name from file
HOST_NAME = open("/etc/hostname").read().replace('\n','')

# server address from file
SERVER = open("/home/pi/server.txt").read().replace('\n','')
print("Read server: '%s'" % SERVER)

# IP address, via script
IP_ADDRESS = os.popen(GET_IP).read()
print("Read IP address: '%s'" % IP_ADDRESS)

# MAC address, via script
MAC_ADDRESS = os.popen(GET_MAC).read()
print("Read MAC address: '%s'" % MAC_ADDRESS)

# "Random" UID
UID = MAC_ADDRESS.upper().replace(":", "")[-6:]

# launch Chromium browser
print("Launching Chromium browser")
os.system(KIOSK)

# ping parameters
PARAMS = {'uid':UID, 'ipAddress':IP_ADDRESS, 'macAddress':MAC_ADDRESS}

# presentation (JSON string)
PRESENTATION = ""

def getUrl(server):
   return ("http://" + SERVER + "/api/display/")

def pingServer():
    status = requests.get(url = URL, params = PARAMS)
    if (status.status_code == 200):
       try:
          response = status.json()
          processPingResult(response)
       except ValueError:
          print("Bad response: %s" % status.content)

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
            file = open("/home/pi/www/presentation.json", "w+")
            file.write(PRESENTATION)
            file.close()
   elif ("error" in response):
      print("Server error: %s" % response["error"])
   else:
      print("Undefined server error")

# Launch the web server that will serve the presentation.json file.
print("Starting Python web server")
os.system("python /home/pi/webserver.py")

# Ping server every 30 seconds.
URL = getUrl(SERVER)
print("Pinging server at %s" % URL)
try:
    while (True):
        success = pingServer()
        time.sleep(30)        
except KeyboardInterrupt:
    pass
