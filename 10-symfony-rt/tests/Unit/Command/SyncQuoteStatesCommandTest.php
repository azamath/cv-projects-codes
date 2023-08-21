<?php

namespace App\Tests\Unit\Command;

use App\Handler\SyncQuoteStatesHandler;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SyncQuoteStatesCommandTest extends KernelTestCase
{
    public function testCommand(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $mockHandler = $this->createMock(SyncQuoteStatesHandler::class);
        $mockHandler->expects($this->once())->method('handle');
        self::getContainer()->set(
            SyncQuoteStatesHandler::class,
            $mockHandler,
        );

        $command = $application->find('app:sync-quote-states');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
    }
}
