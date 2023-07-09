# InfinitySky: Your All-Sky-Camera Project

![Header Image](https://raw.githubusercontent.com/neoground/InfinitySky/main/assets/images/header_logo.jpg)

Hello, Star Gazers, Cloud Watchers, and Tech Wizards! 🌟☁️💻

Welcome to InfinitySky, where we bring the celestial dome to your homes with ease,
precision, and an eye for beauty. Our project is your digital window to the cosmos,
capturing the drama of clouds, the ballet of celestial bodies, and the thrill of
meteor showers. InfinitySky isn't just another camera project; it's your ticket
to ride across the universe while sipping coffee in your living room.

Please note: This project is in early alpha and work in progress. Breaking changes are
possible on every commit. We're actively working on reaching beta state until the end of the year,
so that the next year can be completely captured by InfinitySky.

## Features 📸🌌

With InfinitySky, you are not just stuck with the current view of the sky;
you're documenting a celestial journey:

- **Automated Archiving:** InfinitySky is an ever-watchful eye on the sky, snapping a photo every five minutes,
  automatically archiving and processing it. You won't miss a thing!
- **Raspberry Pi 3/4 Support:** Optimized for Raspberry Pi 3/4, and currently compatible with Raspberry Pi Cameras
  V2/V3. We are continually expanding to support all libcamera-enabled cameras and USB ones, like ZWO.
  Because we love choices!
- **Intelligent Exposures:** We know the golden hour from the blue hour. Our system smartly adjusts exposures and
  measurements depending on the time of day.
- **Keogram Creation:** Ever wondered how the sky looked the entire night or day? Keogram to the rescue! Get the entire
  celestial movement stitched into one image.
- **Daily Timelapse Creation:** Watch the sky in fast-forward. Review how the day unfolded or how stars traveled in a
  neat, compact timelapse video.

Plus, we have an impressive line of features in the works:

- **Web UI:** A compelling, user-friendly web UI is currently under development. It'll let you adjust settings, view
  captured media, and customize your InfinitySky experience.
- **Community Sharing:** We're looking to build a feature that will allow you to share your best celestial captures with
  a community of fellow astronomy enthusiasts.
- **Weather Predictions:** With enough data, we could even predict weather patterns. Imagine planning your stargazing
  nights better!
- And a lot more!

InfinitySky is more than a sky camera; it's your personal observatory and a great addition to your weather station
or astronomy data center, wrapped in one sleek package. It's not just for the lovers of space and weather; 
it's for teachers, researchers,  photographers, and technologists who'd like a reliable, smart window to the sky.

Stay tuned for the upcoming features and improvements. We will update this readme regularly because the sky is not the
limit for us! 🚀

We hope you're as excited as we are about this project. Whether you're an amateur stargazer or a seasoned astronomer,
InfinitySky promises a sky-gazing experience like never before. So hop on, and let's traverse the universe from our
backyard.

## Installation Guide 👩‍💻

Welcome aboard the InfinitySky journey! Follow the steps below to install and configure your all sky camera project:

### Step 1: Prepare Your Environment

Ensure you have a Raspberry Pi OS Lite or a minimal Debian OS installed. We're working on supporting all major
linux platforms soon.

Currently, this is only tested on a Raspberry Pi 3B+ with OS Lite, based on Debian 11 bullseye.

Missing packages will be installed automatically in the installation script.

Software requirements:

- Python 3.9 or higher
- PHP 8.2, with extensions:
  - cli
  - fpm
  - gd
  - imagick
  - opcache 
  - mbstring
  - xml
- libcamera
- web server, ideally nginx
- ffmpeg
- imagemagick
- composer

If you are using Raspberry OS Lite, libcamera and python should be installed already.

Additionally, these python packages are required and need to be installed via `pip`:

- astral
- pytz

### Step 2: Clone the Repository

Clone the InfinitySky repository to the `/opt/infinitysky` directory:

```bash
# First create the destination dir
sudo mkdir /opt/infinitysky 

# Then add permission to your current user for installation, adjust user and group
sudo chown user:group /opt/infinitysky

# Then clone the repository
git clone https://github.com/neoground/InfinitySky.git /opt/infinitysky
```

### Step 3: Run installation script

Run the installation script:

```bash
/opt/infinitysky/install.sh
```

### Step 4: Add to nginx

Add the nginx config of InfinitySky to your main nginx config (typically in `/etc/nginx/sites-enabled`):

```
server {
   ...
   include /opt/infinitysky/nginx.conf;
   
   # If you haven't configured nginx for PHP yet, you may also need to add this:
   location / {
       try_files $uri $uri/ /index.php$is_args$args;
   }
   location ~ \.php$ {
       fastcgi_split_path_info ^(.+\.php)(/.+)$;
       fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
       fastcgi_index index.php;
       include fastcgi.conf;
   }
}
```

### Step 5: Configure InfinitySky

You can find the full default configuration at `config/main.json`. Create a file `config/user.json` with
the same structure to override single options.

```bash
vim /opt/infinitysky/config/user.json
```

### Step 6: Visit InfinitySky

Finally, open your favorite web browser and visit the InfinitySky dashboard using the Raspberry Pi's IP address or
hostname, followed by `/infinitysky`. For example, if your Raspberry Pi's IP address is `192.168.1.10`, you would
navigate to `http://192.168.1.10/infinitysky`.

And voilà! You have successfully set up InfinitySky. Enjoy your celestial explorations! 🌌

Remember to check back regularly for updates and new features, as we're constantly working to improve InfinitySky. Safe
travels through the cosmos!
