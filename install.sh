#!/usr/bin/env bash
#
# Install script for InfinitySky
#
# Optimized for usage on Raspberry Pi 3 / 4
#
# Execute this script once you have all requirements met and cloned
# the repository to /opt/infinitysky.

# Requirements
echo "Installing missing packages..."
sudo apt update
sudo apt install python3 nginx-full ffmpeg imagemagick php8.0 php8.0-fpm php8.0-mbstring php8.0-cli
pip3 install astral pytz

# Set config
MAINCONF="/opt/infinitysky/config/main.json"

if [ -f "$MAINCONF" ]; then
  echo "Main config file already existing. Please check upgrade guide for changed values."
else
  echo "Creating main config file at config/main.json..."
  cp /opt/infinitysky/config/main_sample.json $MAINCONF
fi

# Create system user
if id "$1" >/dev/null 2>&1; then
    echo "User infinitysky already exists."
else
    echo "Creating infinitysky user..."
    sudo adduser --system --no-create-home --group infinitysky
    sudo usermod -a -G video infinitysky
    sudo usermod -a -G audio infinitysky
fi

# Systemd timers
sudo ln -sf /opt/infinitysky/src/systemd/infinitysky-camera.service /etc/systemd/system/infinitysky-camera.service
sudo ln -sf /opt/infinitysky/src/systemd/infinitysky-camera.timer /etc/systemd/system/infinitysky-camera.timer
sudo ln -sf /opt/infinitysky/src/systemd/infinitysky-daily.service /etc/systemd/system/infinitysky-daily.service
sudo ln -sf /opt/infinitysky/src/systemd/infinitysky-daily.timer /etc/systemd/system/infinitysky-daily.timer

sudo systemctl enable infinitysky-camera.timer
sudo systemctl start infinitysky-camera.timer

sudo systemctl enable infinitysky-daily.timer
sudo systemctl start infinitysky-daily.timer

# Install composer
if [ -f "/usr/local/bin/composer" ]; then
  echo "Composer detected."
else
  echo "Installing composer.."
  sudo wget -O /usr/local/bin/composer https://getcomposer.org/composer-stable.phar
  sudo chmod +x /usr/local/bin/composer

# Install Web UI


# Adjust permissions
chown -R infinitysky:infinitysky /opt/infinitysky


exit 0