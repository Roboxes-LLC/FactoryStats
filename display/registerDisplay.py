# importing the requests library 
import requests
import socket
import time
import uuid

# server url 
URL = "http://localhost/flexscreen/api/registerDisplay/"

#IP address
IP_ADDRESS = socket.gethostbyname(socket.gethostname())

#MAC address
MAC_ADDRESS = (':'.join(['{:02x}'.format((uuid.getnode() >> ele) & 0xff) 
for ele in range(0,8*6,8)][::-1])) 

# ping parameters 
PARAMS = {'ipAddress':IP_ADDRESS, 'macAddress':MAC_ADDRESS}

def pingServer():
    print("ping")
    requests.get(url = URL, params = PARAMS)
    time.sleep(10)

try:
    while True:
        pingServer()
except KeyboardInterrupt:
    pass
