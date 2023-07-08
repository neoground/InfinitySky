<?php
/**
 * This is the console named Bob!
 */
set_time_limit(0);
define("CLI_PATH", $argv[0]);

// First we require our composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Then we include our basic configuration
require_once __DIR__ . '/app/Engine.php';

// Finally we start the console system
Charm\Vivid\Kernel\Handler::getInstance()->startConsole();