<?php

namespace App\Command;

use App\Handler\SyncQuoteStatesHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:sync-quote-states',
    description: 'Performs synchronization of quote states which are pending for sync.',
)]
class SyncQuoteStatesCommand extends Command
{
    private SyncQuoteStatesHandler $quoteStateSyncHandler;

    public function __construct(SyncQuoteStatesHandler $quoteStateSyncHandler)
    {
        $this->quoteStateSyncHandler = $quoteStateSyncHandler;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->quoteStateSyncHandler->handle();

        return Command::SUCCESS;
    }
}
