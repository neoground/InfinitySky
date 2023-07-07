#!/usr/bin/python
#
# Taking photos of the sky with the camera
#
# Called via timer periodically

from astral.sun import sun, time_at_elevation
from astral import LocationInfo, SunDirection
from datetime import datetime
import subprocess
import shutil
import os
import pytz
from config import ConfigManager

# Load config
conf = ConfigManager()

if conf.get('enabled') is "false":
    print('InfinitySky is disabled. Please enable in config.')
    exit()

# Location info - name, country, timezone, lat, lon
city = LocationInfo(conf.get('location.city'), conf.get('location.country'),
                    conf.get('location.timezone'), conf.get('location.lat'), conf.get('location.lon'))

# Paths
tmpfile = conf.get('camera.tmp_file')
currentfile = conf.get('camera.current_file')
archivedir = conf.get('camera.archive_dir')

# Current time
now = datetime.now().astimezone(pytz.timezone(city.timezone))

# Calculate civil twilight start / end
s = sun(city.observer, date=now)
tae_morning = time_at_elevation(city.observer, date=now, elevation=-6, direction=SunDirection.RISING)
tae_evening = time_at_elevation(city.observer, date=now, elevation=-6, direction=SunDirection.SETTING)

# Check the current time and adjust the settings accordingly
# Exposure in ms
# Default: day: auto exposure
exposure = 0
gain = 1

if now < tae_morning or now > tae_evening:
    # Before sunrise or after sunset -> Night
    exposure = 5000
    gain = 5

# Delete tmpfile if exists
try:
    os.remove(tmpfile)
except OSError:
    pass

# Take photo
if exposure > 0:
    # Custom long exposures
    print(":: Taking photo with exposure: " + str(exposure) + "ms...")
    subprocess.run(["libcamera-still", "--width", conf.get('camera.width'), "--height", conf.get('camera.height'),
                    "-n", "1", "--shutter", str(exposure * 1000),
                    "-t", str(exposure), "--lens-position", "0", "--sharpness", conf.get('camera.sharpness'),
                    "--saturation", conf.get('camera.saturation'),
                    "--gain", str(gain), "-o", tmpfile])
else:
    # Auto exposure
    print(":: Taking photo with auto settings...")
    subprocess.run(["libcamera-still", "--width", conf.get('camera.width'), "--height", conf.get('camera.height'),
                    "-n", "1", "-t", "5000",
                    "--lens-position", "0", "--sharpness", conf.get('camera.sharpness'),
                    "--saturation", conf.get('camera.saturation'), "-o", tmpfile])

# Apply adjustments
print(":: Processing photo...")
subprocess.run(["convert", tmpfile, "-rotate", conf.get('camera.rotate'), "-gravity", conf.get('camera.gravity'),
                "-crop", conf.get('camera.crop'), conf.get('camera.current_file')])

# Done with tmpfile
os.remove(tmpfile)

# Archive directory
print(":: Archiving photo...")

current_date = datetime.now().strftime('%Y-%m-%d')
current_time = datetime.now().strftime('%H-%M')
dest_dir = os.path.join(archivedir, current_date)

# Create directory if it doesn't exist
if not os.path.exists(dest_dir):
    os.makedirs(dest_dir)

# Copy the current image to the archive
dest = os.path.join(dest_dir, f"{current_date}T{current_time}.jpg")
shutil.copyfile(conf.get('camera.current_file'), dest)

print(":: Done!")
