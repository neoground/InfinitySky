<?php
/**
 * This file contains the BasicController class
 */

namespace App\Controllers;

use Carbon\Carbon;
use Charm\Vivid\C;
use Charm\Vivid\Controller;
use Charm\Vivid\Kernel\Output\Redirect;
use Charm\Vivid\Kernel\Output\View;
use Charm\Vivid\Router\Attributes\Route;

/**
 * Class BasicController
 *
 * Handling basic endpoints
 *
 * @package App\Controllers
 */
class BasicController extends Controller
{
    #[Route("GET", "/", "index")]
    public function getIndex() : View
    {
        // Get current file data
        $current_file = C::Storage()->getDataPath() . DS . 'current.jpg';
        $current_data = [];
        if(file_exists($current_file)) {
            $current_data = [
                'modified_at' => Carbon::createFromTimestamp(filemtime($current_file))
            ];
        }

        $sun = $this->getSunInfo();

        // Get images of the last 24 hours (every 15 mins)
        $hours = [];

        $mins = [
            '00',
            '05',
            '10',
            '15',
            '20',
            '25',
            '30',
            '35',
            '40',
            '45',
            '50',
            '55',
        ];

        $date = Carbon::now()->startOfHour();
        while($date->diffInHours(Carbon::now()) < 24) {
            $hour = [
                'hour' => $date->hour,
            ];

            foreach($mins as $min) {
                $file = C::Storage()->getDataPath() . DS . 'archive' . DS . 'thumbnails' . DS . $date->toDateString() . DS .
                    $date->isoFormat('YYYY-MM-DD_HH-') . $min . '.jpg';

                if(file_exists($file)) {
                    $hour['min_' . $min] = C::Storage()->pathToUrl($file);
                    $hour['min_' . $min . '_full'] = C::Storage()->pathToUrl(
                        str_replace(DS . 'thumbnails', '', $file));
                }

            }

            // Only add if we got at least one image
            if(count($hour) > 1) {
                $hours[] = $hour;
            }

            $date->subHour();
        }

        $curfile = false;
        if(!empty($current_data)) {
            $curfile = C::Storage()->pathToUrl($current_file) . '?v=' .
                $current_data['modified_at']->isoFormat('YYYY-MM-DD_HH-mm');
        }

        return View::make('index')->with([
            'title' => 'Index',
            'nav_active' => 'home',
            'current_file' => $curfile,
            'current_data' => $current_data,
            'sun' => $sun,
            'hours' => $hours,
            'minutes' => ['00', '15', '30', '45'],
            'all_minutes' => $mins,
        ]);
    }

    #[Route("GET", "/yesterday", "yesterday")]
    public function getYesterday() : View
    {
        return $this->getDate(Carbon::now()->subDay()->toDateString());
    }

    #[Route("GET", "/day/{date}", "day")]
    public function getDate(string $date) : View|Redirect
    {
        if(!file_exists(C::Storage()->getDataPath() . DS . 'archive' . DS . $date)) {
            return View::makeError('NotFound', 404);
        }

        try {
            $cdate = Carbon::parse($date);
        } catch(\Exception $e) {
            return View::makeError('InvalidDate', 400);
        }

        if($cdate->isToday() || $cdate->isFuture()) {
            return Redirect::to('index');
        }

        $sun = $this->getSunInfo($cdate);

        // Get images of the last 24 hours (every 15 mins)
        $hours = [];

        $mins = [
            '00',
            '05',
            '10',
            '15',
            '20',
            '25',
            '30',
            '35',
            '40',
            '45',
            '50',
            '55',
        ];

        $date = $cdate->copy()->startOfDay();
        while($date->toDateString() == $cdate->toDateString()) {
            $hour = [
                'hour' => $date->hour,
            ];

            foreach($mins as $min) {
                $file = C::Storage()->getDataPath() . DS . 'archive' . DS . 'thumbnails' . DS . $date->toDateString() . DS .
                    $date->isoFormat('YYYY-MM-DD_HH-') . $min . '.jpg';

                if(file_exists($file)) {
                    $hour['min_' . $min] = C::Storage()->pathToUrl($file);
                    $hour['min_' . $min . '_full'] = C::Storage()->pathToUrl(
                        str_replace(DS . 'thumbnails', '', $file));
                }

            }

            // Only add if we got at least one image
            if(count($hour) > 1) {
                $hours[] = $hour;
            }

            $date->addHour();
        }

        $timelapse_file = C::Storage()->getDataPath() . DS . 'timelapses' . DS . $cdate->toDateString() . '.mp4';
        $keogram_file = C::Storage()->getDataPath() . DS . 'keograms' . DS . $cdate->toDateString() . '.jpg';

        $mode = $this->getDayMode($date);

        $overview_mins = ['00', '15', '30', '45'];
        if($mode == 'hourly') {
            $overview_mins = ['00'];
        }

        return View::make('day')->with([
            'title' => C::Formatter()->formatDate($date) . ' | Archive',
            'nav_active' => $cdate->isYesterday() ? 'yesterday' : 'archive',
            'sun' => $sun,
            'hours' => $hours,
            'minutes' => $overview_mins,
            'all_minutes' => $mins,
            'timelapse_url' => (file_exists($timelapse_file)) ? C::Storage()->pathToUrl($timelapse_file) : false,
            'keogram_url' => (file_exists($keogram_file)) ? C::Storage()->pathToUrl($keogram_file) : false,
            'date' => $cdate,
            'mode' => $mode
        ]);
    }

    private function getDayMode(Carbon $date): string
    {
        // Detect if this day still has all images / hourly only and so on...
        $days = C::Config()->get('user:cleanup.keep_full_archive', 14);
        if($date->lte(Carbon::now()->subDays($days)->startOfDay())) {

            $days = C::Config()->get('user:cleanup.keep_reduced_archive', 28);
            if($date->lte(Carbon::now()->subDays($days)->startOfDay())) {
                return 'hourly';
            }

            return 'quarterly';
        }

        return 'full';
    }

    #[Route("GET", "/archive", "archive")]
    public function getArchive() : View
    {
        $data = [];

        $cover_photo_suffixes = C::Config()->get('user:archive.thumbnail', []);

        foreach(C::Storage()->scanDir(C::Storage()->getDataPath() . DS . 'archive', SCANDIR_SORT_DESCENDING) as $archdir) {
            if(str_starts_with($archdir, '20')) {
                // Got date directory
                try {
                    $date = Carbon::parse($archdir);
                } catch(\Exception $e) {
                    continue;
                }

                if($date->isToday() || $date->isFuture()) {
                    continue;
                }

                $keogram_file = C::Storage()->getDataPath() . DS . 'keograms' . DS . $archdir . '.jpg';
                $keogram_thumbnail_file = C::Storage()->getDataPath() . DS . 'keograms' . DS . 'thumbnails'
                    . DS . $archdir . '_thumbnail.jpg';

                $cover_photo =  false;
                foreach($cover_photo_suffixes as $suf) {
                    $cover_photo_path = C::Storage()->getDataPath() . DS . 'archive' . DS . $archdir . DS . $archdir
                        . '_' . $suf . '.jpg';
                    if(file_exists($cover_photo_path)) {
                        $cover_photo = C::Storage()->pathToUrl($cover_photo_path);
                        break;
                    }
                }

                $data[$archdir] = [
                    'date' => $archdir,
                    'cover_photo' => $cover_photo,
                    'keogram_url' => (file_exists($keogram_file)) ? C::Storage()->pathToUrl($keogram_file) : false,
                    'keogram_thumbnail_url' => (file_exists($keogram_thumbnail_file)) ? C::Storage()->pathToUrl($keogram_thumbnail_file) : false,
                ];
            }
        }

        return View::make('archive')->with([
            'title' => 'Archive',
            'nav_active' => 'archive',
            'data' => $data
        ]);
    }

    private function getSunInfo(Carbon $date = null): array
    {
        if(empty($date)) {
            $date = Carbon::now();
        }

        // Get sun info
        $lat = C::Config()->get('camera:location.lat');
        $lon = C::Config()->get('camera:location.lon');

        $sun = date_sun_info($date->timestamp, $lat, $lon);
        foreach($sun as $k => $v) {
            if($v === true || $v === false) {
                unset($sun[$k]);
                continue;
            }
            $sun[$k] = Carbon::createFromTimestamp($v);
        }
        return $sun;
    }

}