<?php
/**
 * This file contains the beginning of life.
 */

/**
 *       __
 *      /_/\
 *     / /\ \         CHARM FRAMEWORK
 *    / / /\ \        ENTRY POINT
 *   / / /\ \ \
 *  / /_/__\ \ \      The foundation of your
 * /_/______\_\/\     intergalactic journey.
 * \_\_________\/
 *
 * Welcome to Charm!
 *
 * Docs: https://neoground.com/docs/charm
 */

/**--**--**--**--**--**--**--**--**--**--**--**--**
 *           Stay hungry, stay foolish            *
 *  https://www.youtube.com/watch?v=UF8uR6Z6KLc   *
 **--**--**--**--**--**--**--**--**--**--**--**--**/

// First we require our composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Then we include our basic configuration
require_once __DIR__ . '/app/Engine.php';

// Finally we start the system
Charm\Vivid\Kernel\Handler::getInstance()->start();
