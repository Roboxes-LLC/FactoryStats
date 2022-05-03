if [ ! -f "/etc/xdg/lxsession/LXDE-pi/autostart.orig" ]
then
    sudo cp /etc/xdg/lxsession/LXDE-pi/autostart /etc/xdg/lxsession/LXDE-pi/autostart.orig
fi
sudo cp /home/pi/factorystats/autostart /etc/xdg/lxsession/LXDE-pi/autostart