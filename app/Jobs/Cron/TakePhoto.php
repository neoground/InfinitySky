<?php
/**
 * This file contains the TakePhoto cron job
 */

namespace App\Jobs\Cron;

use App\Services\CamService;
use Charm\Crown\Cronjob;
use Charm\Vivid\C;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Class TakePhoto
 *
 * TakePhoto cron job
 *
 * @package App\Jobs\Cron
 */
class TakePhoto extends Cronjob
{
    /**
     * Cron job configuration
     */
    protected function configure()
    {
        $this->setName('TakePhoto')
            ->setSchedule('*/5');
    }

    /**
     * Run that job.
     *
     * @return bool
     */
    public function run()
    {
        if(!C::Config()->get('camera:enabled', false)) {
            return false;
        }

        $cs = new CamService();
        $cs->takePhoto(new NullOutput());
        return true;
    }
}