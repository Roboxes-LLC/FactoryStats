# importing the requests library
import os 
import requests
import time

# getMac script
GET_MAC = "/home/pi/getMac.sh"

# GET_IP linux command
GET_IP = "hostname  -I | cut -f1 -d' ' | tr -d '\n' | tr -d '\r'"

# kiosk script
KIOSK = "bash /home/pi/kiosk.sh"

# server address from file
SERVER = open("/home/pi/server.txt").read().replace('\n','')
print("Read server: '%s'" % (SERVER))

# display UID from file
UID = open("/home/pi/uid.txt").read().replace('\n','')
print("Read UID: '%s'" % (UID))

# ping url 
URL = "http://" + SERVER + "/api/display/"

#IP address
IP_ADDRESS = os.popen(GET_IP).read()
print("Read IP address: '%s'" % (IP_ADDRESS))

#MAC address
MAC_ADDRESS = os.popen(GET_MAC).read()
print("Read MAC address: '%s'" % (MAC_ADDRESS))

# launch Chromium browser
#print("Launching Chromium browser")
#os.system(KIOSK) 

# ping parameters 
PARAMS = {'uid':UID, 'ipAddress':IP_ADDRESS, 'macAddress':MAC_ADDRESS}

def pingServer():
    print("ping")
    requests.get(url = URL, params = PARAMS)
    time.sleep(30)

print("Pinging server at %s" % (URL))
try:
    while True:
        pingServer()
except KeyboardInterrupt:
    pass
