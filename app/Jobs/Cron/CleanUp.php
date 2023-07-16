<?php
/**
 * This file contains the CleanUp cron job
 */

namespace App\Jobs\Cron;

use App\Services\CamService;
use Carbon\Carbon;
use Charm\Crown\Cronjob;
use Charm\Vivid\C;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Class CleanUp
 *
 * CleanUp cron job
 *
 * @package App\Jobs\Cron
 */
class CleanUp extends Cronjob
{
    /**
     * Cron job configuration
     */
    protected function configure()
    {
        $this->setName('CleanUp')
            ->runDaily(2, 30);
    }

    /**
     * Run that job.
     *
     * @return bool
     */
    public function run()
    {
        $this->removeKeogramSlices();
        $this->removeKeograms();
        $this->removeTimelapses();
        $this->cleanUpArchiveExceptQuarterly();
        $this->cleanUpArchiveExceptFullHour();
        $this->removeWholeDay();

        return true;
    }

    private function cleanUpArchiveExceptQuarterly()
    {
        $days = C::Config()->get('user:cleanup.keep_full_archive', 14);
        $oldest_date = Carbon::now()->subDays($days)->startOfDay();
        $basedir = C::Storage()->getDataPath() . DS . 'archive';

        foreach(C::Storage()->scanDir($basedir) as $file) {
            if(is_dir($basedir . DS . $file)) {
                $this->cleanUpArchiveImages($basedir . DS . $file, $oldest_date, ['00', '15', '30', '45']);
            }
        }
    }

    private function cleanUpArchiveExceptFullHour()
    {
        $days = C::Config()->get('user:cleanup.keep_reduced_archive', 28);
        $oldest_date = Carbon::now()->subDays($days)->startOfDay();
        $basedir = C::Storage()->getDataPath() . DS . 'archive';

        foreach(C::Storage()->scanDir($basedir) as $file) {
            if(is_dir($basedir . DS . $file)) {
                $this->cleanUpArchiveImages($basedir . DS . $file, $oldest_date, ['00']);
            }
        }
    }

    private function cleanUpArchiveImages(string $path, Carbon $oldest_date, array $whitelist)
    {
        try {
            $date = Carbon::parse(basename($path));
            if($date->startOfDay()->lt($oldest_date)) {
                foreach(C::Storage()->scanDir($path) as $file) {
                    $time_ext_suffix = explode("_", $file);
                    if(array_key_exists(1, $time_ext_suffix)) {
                        $time = $time_ext_suffix[1];

                        $in_whitelist = false;
                        foreach($whitelist as $wl) {
                            if(str_contains($time, $wl)) {
                                // In whitelist -> keep image
                                $in_whitelist = true;
                                break;
                            }
                        }

                        if(!$in_whitelist) {
                            C::Logging()->info('Cleanup: Removing old archived image: ' . $path . DS . $file);
                            unlink($path . DS . $file);
                        }
                    }
                }
            }
        } catch(\Exception $e) {
            // Ignore invalid dirs
        }
    }

    private function removeWholeDay()
    {
        $days = C::Config()->get('user:cleanup.keep_whole_day', 28);
        $oldest_date = Carbon::now()->subDays($days)->startOfDay();
        $basedir = C::Storage()->getDataPath() . DS . 'archive';

        // Remove archive dir
        foreach(C::Storage()->scanDir($basedir) as $file) {
            if(is_dir($basedir . DS . $file)) {
                $this->removeDirRecursivelyIfOlderThan($basedir . DS . $file, $oldest_date);
            }
        }

        // Remove thumbnails
        $thumbsdir = C::Storage()->getDataPath() . DS . 'archive' . DS . 'thumbnails';

        foreach(C::Storage()->scanDir($thumbsdir) as $file) {
            if(is_dir($thumbsdir . DS . $file)) {
                $this->removeDirRecursivelyIfOlderThan($thumbsdir . DS . $file, $oldest_date);
            }
        }
    }

    private function removeTimelapses()
    {
        $days = C::Config()->get('user:cleanup.keep_timelapse', 90);
        $oldest_date = Carbon::now()->subDays($days)->startOfDay();
        $basedir = C::Storage()->getDataPath() . DS . 'timelapses';
        foreach(C::Storage()->scanDir($basedir) as $file) {
            if(is_file($basedir . DS . $file)) {
                $this->removeFileIfOlderThan($basedir . DS . $file, $oldest_date);
            }
        }
    }

    private function removeKeograms()
    {
        $days = C::Config()->get('user:cleanup.keep_keogram', 90);
        $oldest_date = Carbon::now()->subDays($days)->startOfDay();
        $basedir = C::Storage()->getDataPath() . DS . 'keograms';
        foreach(C::Storage()->scanDir($basedir) as $file) {
            if(is_file($basedir . DS . $file)) {
                $this->removeFileIfOlderThan($basedir . DS . $file, $oldest_date);
            }
        }

        $thumbsdir = C::Storage()->getDataPath() . DS . 'keograms' . DS . 'thumbnails';
        foreach(C::Storage()->scanDir($thumbsdir) as $file) {
            if(is_file($thumbsdir . DS . $file)) {
                $this->removeFileIfOlderThan($thumbsdir . DS . $file, $oldest_date);
            }
        }
    }

    private function removeKeogramSlices()
    {
        $days = C::Config()->get('user:cleanup.keep_keogram_slices', 7);
        $oldest_date = Carbon::now()->subDays($days)->startOfDay();
        $basedir = C::Storage()->getDataPath() . DS . 'keograms';

        foreach(C::Storage()->scanDir($basedir) as $file) {
            if(is_dir($basedir . DS . $file)) {
                $this->removeDirRecursivelyIfOlderThan($basedir . DS . $file, $oldest_date);
            }
        }
    }

    private function removeDirRecursivelyIfOlderThan(string $path, Carbon $oldest_date)
    {
        try {
            $date = Carbon::parse(basename($path));
            if($date->startOfDay()->lt($oldest_date)) {
                // Delete thumbnail files inside the dir
                C::Logging()->info('Cleanup: Removing dir ' . $path);

                foreach(C::Storage()->scanDir($path) as $file) {
                    unlink($path . DS . $file);
                }

                // Delete dir itself
                rmdir($path);
            }
        } catch(\Exception $e) {
            // Ignore invalid dirs
        }
    }

    private function removeFileIfOlderThan(string $path, Carbon $oldest_date)
    {
        try {
            $pi = pathinfo($path);
            $filename = str_replace(['.' . $pi['extension'], '_thumbnail'], '', basename($path));

            $date = Carbon::parse($filename);
            if($date->startOfDay()->lt($oldest_date)) {
                // Delete file
                C::Logging()->info('Cleanup: Removing file ' . $path);
                unlink($path);
            }
        } catch(\Exception $e) {
            // Ignore invalid dirs
        }
    }
}