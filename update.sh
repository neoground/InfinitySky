#!/usr/bin/env bash
#
# Update script for InfinitySky
#
# Optimized for usage on Raspberry Pi 3 / 4
#
# This script executes everything as the app's default user www-data

cd /opt/infinitysky
sudo -u www-data git stash
sudo -u www-data git pull
sudo -u www-data php bob.php cache:clear
sudo -u www-data composer install
echo " "
echo "Update complete. Enjoy the sky!"

exit 0