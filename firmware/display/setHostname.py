# importing the requests library
import os 
import time

# getMac script
GET_MAC = "/home/pi/getMac.sh"

# Host name from file
HOST_NAME = open("/etc/hostname").read().replace('\n','')

# MAC address, via script
MAC_ADDRESS = os.popen(GET_MAC).read()
print("Read MAC address: '%s'" % (MAC_ADDRESS))

# Preferred host name
PREFERRED_HOST_NAME = "FACTSTATDISPLAY-" + MAC_ADDRESS.upper().replace(":", "")[-6:]

# Set "random" host name on first run
if (HOST_NAME != PREFERRED_HOST_NAME):
   file = open("/etc/hostname", "w")
   file.write(PREFERRED_HOST_NAME)
   file.close()
   print("Updated hostname: '%s'" % (PREFERRED_HOST_NAME))
   print("Rebooting ...")
   time.sleep(10)
   os.system('sudo shutdown -r now')
