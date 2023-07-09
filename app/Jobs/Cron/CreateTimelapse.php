<?php
/**
 * This file contains the CreateTimelapse cron job
 */

namespace App\Jobs\Cron;

use App\Services\CamService;
use Carbon\Carbon;
use Charm\Crown\Cronjob;
use Charm\Vivid\C;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Class CreateTimelapse
 *
 * CreateTimelapse cron job
 *
 * @package App\Jobs\Cron
 */
class CreateTimelapse extends Cronjob
{
    /**
     * Cron job configuration
     */
    protected function configure()
    {
        $this->setName('CreateTimelapse')
            ->runDaily(0, 15);
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
        $cs->createTimelapse(Carbon::now()->subDay()->toDateString());
        return true;
    }
}