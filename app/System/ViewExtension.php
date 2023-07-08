<?php
/**
 * This file contains the app specific ViewExtension class
 */

namespace App\System;

use Charm\Vivid\Base\BasicViewExtension;

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
     * Get a random integer in a view (demo method)
     *
     * Use it in a view as {{ getRandomInt(1, 10) }}
     *
     * @param int $min min number
     * @param int $max max number
     *
     * @return int
     */
    public function getRandomInt($min, $max)
    {
        return rand($min, $max);
    }

}