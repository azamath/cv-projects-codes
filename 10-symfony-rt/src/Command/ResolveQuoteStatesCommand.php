<?php

namespace App\Command;

use App\Handler\ResolveQuoteStatesHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:resolve-quote-states',
    description: 'Resolves quote states from signings.',
)]
class ResolveQuoteStatesCommand extends Command
{
    public function __construct(private ResolveQuoteStatesHandler $handler)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->handler->handle();

        return Command::SUCCESS;
    }
}
