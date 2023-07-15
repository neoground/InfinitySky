<?php
/**
 * This file contains the CreateKeogram cron job
 */

namespace App\Jobs\Cron;

use App\Services\CamService;
use Carbon\Carbon;
use Charm\Crown\Cronjob;
use Charm\Vivid\C;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Class CreateKeogram
 *
 * CreateKeogram cron job
 *
 * @package App\Jobs\Cron
 */
class CreateKeogram extends Cronjob
{
    /**
     * Cron job configuration
     */
    protected function configure()
    {
        $this->setName('CreateKeogram')
            ->runDaily(0, 6);
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
        $cs->createKeogram(Carbon::now()->subDay()->toDateString());
        return true;
    }
}