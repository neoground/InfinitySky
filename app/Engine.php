<?php
/**
 * This file contains the basic configuration.
 */

namespace App;

use App\System\Middleware\Auth;
use Charm\Vivid\C;
use Charm\Vivid\Kernel\EngineManager;
use Charm\Vivid\Kernel\Interfaces\ModuleInterface;
use Charm\Vivid\Router\Elements\Filter;

/**
 * Class Engine
 *
 * @package App
 */
class Engine extends EngineManager implements ModuleInterface
{
    /**
     * Load the module
     */
    public function loadModule()
    {
        // Add route filters
        Filter::add('auth', Auth::class . "::checkAuth");

        // Here you can add code that will be executed on the init of your app
    }

    public function getVersion()
    {
        $composer = json_decode(file_get_contents($this->getBaseDirectory() . DS . '..' . DS . 'composer.json'), true);
        if(is_array($composer) && array_key_exists('version', $composer)) {
            return $composer['version'];
        }
        return false;
    }
}