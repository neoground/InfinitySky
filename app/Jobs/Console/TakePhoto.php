<?php
/**
 * This file contains a console command.
 */

namespace App\Jobs\Console;

use App\Services\CamService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TakePhoto
 *
 * TakePhoto console command
 *
 * @package App\Jobs\Console
 */
class TakePhoto extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("camera:shoot")
            ->setDescription("Take a new photo and save it in the archive");
    }

    /**
     * The execution
     *
     * @param InputInterface   $input
     * @param OutputInterface  $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Taking a fresh new photo. Say cheese, sky!");
        $cs = new CamService();
        $cs->takePhoto($output);
        $output->writeln('Done!');
        return self::SUCCESS;
    }
}