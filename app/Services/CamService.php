<?php
/**
 * This file contains the CamService
 */

namespace App\Services;

use Carbon\Carbon;
use Charm\Vivid\C;
use claviska\SimpleImage;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class CamService
 *
 * Handling camera tasks
 */
class CamService
{
    public function __construct()
    {

    }

    public function takePhoto(OutputInterface $output): bool
    {
        $tmpfile = C::Storage()->getDataPath() . DS . 'current_unprocessed.jpg';
        $current_file = C::Storage()->getDataPath() . DS . 'current.jpg';

        // Capture photo
        $output->writeln('Taking a photo...');
        if(!$this->capture($tmpfile)) {
            // Error while capturing!
            $output->writeln('<error>Error while capturing photo</error>');
            return false;
        }

        // Post-processing
        $output->writeln('Processing photo...');
        $image = new SimpleImage();
        $image->fromFile($tmpfile)
            ->rotate(C::Config()->get('camera:camera.rotate'))
            ->crop(C::Config()->get('camera:camera.crop.top_left_x'),
                   C::Config()->get('camera:camera.crop.top_left_y'),
                   C::Config()->get('camera:camera.crop.bottom_right_x'),
                   C::Config()->get('camera:camera.crop.bottom_right_y'))
            ->toFile($current_file, 'image/jpeg', 93);

        C::Storage()->deleteFileIfExists($tmpfile);

        // Archiving
        $output->writeln('Archiving photo...');
        $archive_dir = C::Storage()->getDataPath() . DS . 'archive'
            . DS . Carbon::now()->toDateString();
        $thumbnails_dir = C::Storage()->getDataPath() . DS . 'archive'
            . DS . 'thumbnails' . DS . Carbon::now()->toDateString();

        $filename = Carbon::now()->isoFormat('YYYY-MM-DD_HH-mm') . '.jpg';

        C::Storage()->createDirectoriesIfNotExisting($archive_dir);
        copy($current_file, $archive_dir . DS . $filename);

        // Create thumbnail for archived image
        $output->writeln('Creating thumbnail...');
        $thumbnail = new SimpleImage();
        $thumbnail->fromFile($archive_dir . DS . $filename)
            ->thumbnail(400, 400)
            ->toFile($thumbnails_dir . DS . $filename, 'image/jpeg', 80);

        // Create slice for keogram
        $keograms_dir = C::Storage()->getDataPath() . DS . 'keograms'
            . DS . Carbon::now()->toDateString();

        C::Storage()->createDirectoriesIfNotExisting($keograms_dir);
        $this->createKeogramSlice($current_file, $keograms_dir . DS . $filename);

        return true;
    }

    public function capture(string $file) : bool
    {
        $mode = $this->getMode();

        // Default: day
        $exposure = 'auto';
        $gain = 1;
        $timeout = 5000;

        if($mode == 'twilight') {
            $exposure = C::Config()->get('camera:camera.capture.twilight.exposure');
            $gain = C::Config()->get('camera:camera.capture.twilight.gain');
            $timeout = $exposure;
        } elseif($mode == 'night') {
            $exposure = C::Config()->get('camera:camera.capture.night.exposure');
            $gain = C::Config()->get('camera:camera.capture.night.gain');
            $timeout = $exposure;
        }

        C::Storage()->deleteFileIfExists($file);

        $capture_cmd = [
            "libcamera-still",
            "--width", C::Config()->get('camera:camera.width'),
            "--height", C::Config()->get('camera:camera.height'),
            "-n", "1",
            "-t", $timeout,
            "--lens-position", "0",
            "--sharpness", C::Config()->get('camera:camera.sharpness'),
            "--saturation", C::Config()->get('camera:camera.saturation'),
            "--gain", $gain,
            "-o", $file
        ];

        if($exposure != 'auto') {
            // Append custom exposure
            $capture_cmd[] = '--shutter';
            $capture_cmd[] = $exposure * 1000;
        }

        $capture = new Process($capture_cmd);
        $capture->run();

        return $capture->isSuccessful();
    }

    private function getMode() : string
    {
        // Mode detection for current time: day / twilight / night
        $now = Carbon::now();

        $lat = C::Config()->get('camera:location.lat');
        $lon = C::Config()->get('camera:location.lon');

        $sun = date_sun_info(time(), $lat, $lon);

        if(!is_array($sun)) {
            // Invalid data...
            return 'day';
        }

        // Check sun info and turn values into Carbon objects
        foreach($sun as $k => $v) {
            // Timestamp
            $sun[$k] = Carbon::createFromTimestamp($v);
        }

        if($now->lt($sun['civil_twilight_begin']) || $now->gt($sun['civil_twilight_end'])) {
            // Before dawn beginning or after dawn end (102°)
            return 'night';
        }

        if($now->lt($sun['sunrise']) || $now->gt($sun['sunset'])) {
            // Before close to sunrise or a bit after sunset (96°)
            return 'twilight';
        }

        // Not night, not dawn, so it's clear!
        return 'day';
    }

    /**
     * Create a single slice of a single camera image
     *
     * This is the single slice which will then be added to the keogram image of the whole day.
     *
     * @param string $source absolute path to source file
     * @param string $dest absolute path where sliced file will be saved to
     *
     * @return void
     *
     * @throws \Exception
     */
    public function createKeogramSlice(string $source, string $dest) : void
    {
        C::Storage()->deleteFileIfExists($dest);
        $image = new SimpleImage();
        $image->fromFile($source)
            ->resize(
                C::Config()->get('camera:keogram.slice_width'),
                C::Config()->get('camera:keogram.slice_height')
            )->toFile($dest, 'image/jpeg', 90);
    }

    /**
     * Create the keogram of a single day
     *
     * @param string $day_string the day string (YYYY-MM-DD)
     *
     * @return bool false on error, true on success
     */
    public function createKeogram(string $day_string): bool
    {
        $keograms_dir = C::Storage()->getDataPath() . DS . 'keograms' . DS . $day_string;
        $archive_dir = C::Storage()->getDataPath() . DS . 'archive' . DS . $day_string;

        if(!file_exists($keograms_dir) || !file_exists($archive_dir)) {
            return false;
        }

        try {
            // Prepare montage
            $imagick = new \Imagick();

            // Go through all images of this day and create missing keogram slices
            $files = C::Storage()->scanDir($keograms_dir);
            foreach($files as $file) {
                if(!file_exists($keograms_dir . DS . $file)) {
                    $this->createKeogramSlice($archive_dir . DS . $file, $keograms_dir . DS . $file);
                }

                $imagick->addImage(new \Imagick($keograms_dir . DS . $file));
            }

            // Build final keogram image
            $draw = new \ImagickDraw();
            $draw->setStrokeColor('black');
            $draw->setFillColor('white');

            $draw->setStrokeWidth(0);
            $draw->setFontSize(24);

            $slice_width = C::Config()->get('camera:keogram.slice_width');
            $slice_height = C::Config()->get('camera:keogram.slice_height');

            $imagick->newimage($slice_width * count($files), $slice_height, 'black');

            $montage = $imagick->montageImage(
                $draw,
                count($files) . "x1",
                $slice_width . "x" . $slice_height . "+0+0>",
                \Imagick::MONTAGEMODE_CONCATENATE,
                "10x10+2+2"
            );

            $filename = $keograms_dir . DS . $day_string . '.jpg';
            $filename_thumbnail = $keograms_dir . DS . $day_string . '_thumbnail.jpg';

            C::Storage()->deleteFileIfExists($filename);

            $montage->setImageFormat('jpg');
            $montage->writeImage($filename);

            // Create thumbnail for archived image
            $thumbnail = new SimpleImage();
            $thumbnail->fromFile($filename)
                ->thumbnail(600, 300)
                ->toFile($filename_thumbnail, 'image/jpeg', 80);

            return true;

        } catch(\Exception $e) {
            return false;
        }
    }

    public function createTimelapse(string $day_string)
    {
        $archive_dir = C::Storage()->getDataPath() . DS . 'archive'
            . DS . $day_string;

        if(!file_exists($archive_dir)) {
            return false;
        }

        $video_path = C::Storage()->getDataPath() . DS . 'timelapses' . DS . $day_string . '.mp4';
        C::Storage()->createDirectoriesIfNotExisting(dirname($video_path));
        C::Storage()->deleteFileIfExists($video_path);

        $ffmpeg = new Process([
            "ffmpeg", "-framerate", C::Config()->get('camera:timelapse.framrate'), "-pattern_type", "glob",
            "-i", "'" . $archive_dir . DS . "*.jpg'", "-c:v", "libx264", "-pix_fmt", "yuv420p", $video_path
        ]);
        $ffmpeg->run();
        return $ffmpeg->isSuccessful();
    }

}