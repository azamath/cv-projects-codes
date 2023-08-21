<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:process-expired-signings',
    description: 'Perform necessary action on expired signings',
)]
class ProcessExpiredSigningsCommand extends Command
{

    public function __construct(private \App\Handler\ProcessExpiredSigningsHandler $handler)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $result = $this->handler->handle();

        if (is_int($result)) {
            $io->info(sprintf("Number of processed expired signings: %d", $result));
        }

        return Command::SUCCESS;
    }
}
