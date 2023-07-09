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
sudo apt install python3 nginx-full ffmpeg imagemagick
pip3 install astral pytz

# Create system user
echo "Adding www-data user to needed groups: video, audio"
sudo usermod -a -G video www-data
sudo usermod -a -G audio www-data

# Install PHP 8.1

# Check if PHP is installed
if ! command -v php >/dev/null 2>&1; then
  echo "PHP is not installed. Attempting to install..."

  # Update the package list
  sudo apt-get update

  # Check if PHP 8.2 is available
  if apt-cache pkgnames | grep -q "^php8.2-common$"; then
    echo "PHP 8.2 is available. Installing..."
    sudo apt-get install -y php8.2 php8.2-cli php8.2-common php8.2-fpm php8.2-gd php8.2-imagick php8.2-opcache php8.2-mbstring php8.2-xml
  else
    echo "PHP 8.2 is not available. Adding Sury PHP repository..."

    # Add Sury PHP repository
    sudo wget -qO /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/php.list

    # Update the package list again
    sudo apt-get update

    # Try to install PHP 8.2 again
    if apt-cache pkgnames | grep -q "^php8.2-common$"; then
      echo "PHP 8.2 is now available. Installing..."
      sudo apt-get install -y php8.2 php8.2-cli php8.2-common php8.2-fpm php8.2-gd php8.2-imagick php8.2-opcache php8.2-mbstring php8.2-xml
    else
      echo "PHP 8.2 is still not available. Please check your package sources or manually install PHP."
      exit 1
    fi
  fi
else
  echo "PHP is already installed."
fi

# Validate the PHP version
php_version=$(php -r "echo floatval(PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION) >= 8.2 ? '1' : '0';")

# Check if PHP version is at least 8.2
if [ "$php_version" == "1" ]; then
  echo "PHP version $php_version detected."
else
  echo "PHP version is $php_version, which is less than 8.1!"
  exit 1
fi

# Install composer
if [ -f "/usr/local/bin/composer" ]; then
  echo "Composer detected."
else
  echo "Installing composer.."
  sudo wget -O /usr/local/bin/composer https://getcomposer.org/composer-stable.phar
  sudo chmod +x /usr/local/bin/composer

# Install Web UI
cd /opt/infinitysky/www
mkdir data var/cache var/logs
/usr/local/bin/composer install
echo "Local" > app/app.env

# Adjust permissions
chown -R www-data:www-data /opt/infinitysky

# Systemd timers
sudo ln -sf /opt/infinitysky/var/systemd/infinitysky-cron.service /etc/systemd/system/infinitysky-cron.service
sudo ln -sf /opt/infinitysky/var/systemd/infinitysky-cron.timer /etc/systemd/system/infinitysky-cron.timer

sudo systemctl daemon-reload
sudo systemctl enable infinitysky-cron.timer

echo " "
echo "InfinitySky timer has been added to systemd which starts automatically next boot."
echo "When you are done configuring InfinitySky you can start the system via:"
echo "> sudo systemctl start infinitysky-cron.timer"
echo " "

echo "Installation complete!"

exit 0