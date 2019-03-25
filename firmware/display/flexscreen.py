# importing the requests library
import os 
import requests
import time

# getMac script
GET_MAC = "/home/pi/getMac.sh"

#GET_IP linux command
GET_IP = "hostname  -I | cut -f1 -d' ' | tr -d '\n' | tr -d '\r'"

# kiosk script
KIOSK = "/home/pi/kiosk.sh"

# server url 
URL = "http://192.168.0.117/flexscreen/api/registerDisplay/"

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
PARAMS = {'ipAddress':IP_ADDRESS, 'macAddress':MAC_ADDRESS}

def pingServer():
    print("ping")
    requests.get(url = URL, params = PARAMS)
    time.sleep(10)

print("Pinging server at %s" % (URL))
try:
    while True:
        pingServer()
except KeyboardInterrupt:
    pass
