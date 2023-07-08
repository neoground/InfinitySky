<?php
/**
 * This file contains the basic configuration.
 */

namespace App;

use App\System\Middleware\Auth;
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
}