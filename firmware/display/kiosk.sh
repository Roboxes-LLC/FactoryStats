# Factory Stats kiosk script

# Disable screensaver and display power management
xset s noblank
xset s off
xset -dpms

# Hide the mouse
#unclutter -idle 0

# Power on television
echo "on 0.0.0.0" | cec-client -s

# Switch television input
echo "as" | cec-client -s

# Fix issue caused by changing the hostname under Chromium
rm -rf ~/.config/chromium/Singleton*

# Clear Chrome flags to disable pop-ups
sed -i 's/"exited_cleanly":false/"exited_cleanly":true/' ~/.config/chromium/Default/Preferences
sed -i 's/"exit_type":"Crashed"/"exit_type":"Normal"/' ~/.config/chromium/Default/Preferences

# Launch Chromium browser in kiosk mode
/usr/bin/chromium-browser --noerrdialogs --disable-infobars --disable-component-update --kiosk &
