# Daily script
#
# Creates keogram and timelapse
#
# Can have optional parameter with date to process specific date
# If no date is provided, it processed yesterday as default,
# because it runs via timer every night at 00:05

from datetime import datetime, timedelta
import subprocess
import os
import shutil
from config import ConfigManager


def create_keogram(date=datetime.now(), conf=ConfigManager()):
    date_string = date.strftime("%Y-%m-%d")
    print("Creating keogram for " + date_string + "..")
    archive_dir = os.path.join(conf.get('camera.archive_dir'), date_string)
    output_file = os.path.join(conf.get('camera.keograms_dir'), date_string + ".jpg")

    # Collect the filenames of all images in the archive directory
    image_files = [os.path.join(archive_dir, f) for f in sorted(os.listdir(archive_dir)) if f.endswith('.jpg')]

    # Create a temporary directory to store the resized images
    if os.path.exists(conf.get('camera.tmp_dir')) and os.path.isdir(conf.get('camera.tmp_dir')):
        shutil.rmtree(conf.get('camera.tmp_dir'))

    os.makedirs(conf.get('camera.tmp_dir'), exist_ok=True)

    os.makedirs(conf.get('camera.keograms_dir'), exist_ok=True)

    # Resize the images and store in the temporary directory
    for i, file in enumerate(image_files):
        temp_file = os.path.join('temp', f'{i}.jpg')
        subprocess.run(['convert', file, '-resize',
                        conf.get('camera.keogram.slice_width') + "x" + conf.get('camera.keogram.slice_height'),
                        temp_file], check=True)

    # Use ImageMagick's montage command to combine the resized images into a keogram
    image_dir = os.path.join(conf.get('camera.tmp_dir'), "*.jpg")
    subprocess.run(['montage', '-mode', 'concatenate', '-tile', f'{len(image_files)}x1', image_dir, output_file],
                   check=True)

    # Delete the temporary directory when done
    shutil.rmtree(conf.get('camera.tmp_dir'))


def create_timelapse(date=datetime.now(), conf=ConfigManager()):
    date_string = date.strftime("%Y-%m-%d")
    print("Creating timelapse for " + date_string + "..")

    archive_dir = os.path.join(conf.get('camera.archive_dir'), date_string, "*.jpg")
    video_path = os.path.join(conf.get('camera.timelapses_dir'), date_string + ".mp4")

    os.makedirs(conf.get('camera.timelapses_dir'), exist_ok=True)

    subprocess.run(["ffmpeg", "-framerate", conf.get('timelapse.framerate'), "-pattern_type", "glob",
                    "-i", "'" + archive_dir + "'", "-c:v", "libx264", "-pix_fmt", "yuv420p", video_path])


def main():
    yesterday = datetime.now() - timedelta(days=1)
    conf = ConfigManager()

    if conf.get('enabled') is "false":
        print('InfinitySky is disabled. Please enable in config.')
        exit()

    create_keogram(yesterday, conf)
    create_timelapse(yesterday, conf)


if __name__ == "__main__":
    main()
