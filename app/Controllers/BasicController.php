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

        return View::make('index')->with([
            'title' => 'Index',
            'current_file' => C::Storage()->pathToUrl($current_file),
            'current_data' => $current_data,
            'sun' => $sun
        ]);
    }

    #[Route("GET", "/backend", "restricted", "auth")]
    public function getRestricted() : View
    {
        return View::make('backend.index')->with([

        ]);
    }

}