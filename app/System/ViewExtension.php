<?php
/**
 * This file contains the app specific ViewExtension class
 */

namespace App\System;

use Charm\Vivid\Base\BasicViewExtension;
use Charm\Vivid\C;

/**
 * Class ViewExtension
 *
 * Adding view functions to twig views and much more!
 *
 * @package App\System
 */
class ViewExtension extends BasicViewExtension
{
    /**
     * Get software version
     *
     * @return string
     */
    public function getVersion()
    {
        return C::App()->getVersion();
    }

    public function getPhpVersion()
    {
        return PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
    }

}