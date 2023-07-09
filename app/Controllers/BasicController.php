<?php
/**
 * This file contains the BasicController class
 */

namespace App\Controllers;

use Carbon\Carbon;
use Charm\Vivid\C;
use Charm\Vivid\Controller;
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

        // Get sun info
        $lat = C::Config()->get('camera:location.lat');
        $lon = C::Config()->get('camera:location.lon');

        $sun = date_sun_info(time(), $lat, $lon);
        foreach($sun as $k => $v) {
            if($v === true || $v === false) {
                unset($sun[$k]);
                continue;
            }
            $sun[$k] = Carbon::createFromTimestamp($v);
        }

        // Get images of the last 24 hours (every 15 mins)
        $hours = [];
        $date = Carbon::now()->startOfHour();
        while($date->diffInHours(Carbon::now()) < 24) {
            $hour = [
                'hour' => $date->hour
            ];

            foreach([0, 15, 30, 45] as $min) {
                $file = C::Storage()->getDataPath() . DS . 'archive' . DS . 'thumbnails' . DS . $date->toDateString() . DS .
                    $date->isoFormat('YYYY-MM-DD_HH-mm') . '.jpg';

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

        return View::make('index')->with([
            'title' => 'Index',
            'current_file' => C::Storage()->pathToUrl($current_file),
            'current_data' => $current_data,
            'sun' => $sun,
            'hours' => $hours,
            'minutes' => ['00', '15', '30', '45']
        ]);
    }

    #[Route("GET", "/backend", "restricted", "auth")]
    public function getRestricted() : View
    {
        return View::make('backend.index')->with([

        ]);
    }

}