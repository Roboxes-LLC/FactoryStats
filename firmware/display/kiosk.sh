# Flexscreen kiosk script

# Disable screensaver and display power management
xset s noblank
xset s off
xset -dpms

MAC_ADDRESS=$(./getMac.sh)

# Launch Chromium browser in kiosk mode.
/usr/bin/chromium-browser --noerrdialogs --disable-infobars --kiosk http://www.roboxes.com/flexscreen/display.php?macAddress=$MAC_ADDRESS &
