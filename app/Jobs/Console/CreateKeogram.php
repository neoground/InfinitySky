<?php
/**
 * This file contains a console command.
 */

namespace App\Jobs\Console;

use App\Services\CamService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreateKeogram
 *
 * CreateKeogram console command
 *
 * @package App\Jobs\Console
 */
class CreateKeogram extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("camera:keogram")
            ->setDescription("Create a keogram for a specific day (date string as argument)")
            ->addArgument('datestring', InputArgument::REQUIRED);
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
        $date = $input->getArgument('datestring');
        $output->writeln('Creating keogram for day: ' . $date);

        $cs = new CamService();
        $cs->createKeogram($date);

        $output->writeln('Done!');
        return self::SUCCESS;
    }
}