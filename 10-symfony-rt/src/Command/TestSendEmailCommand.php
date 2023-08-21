<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'test:send-email',
    description: 'Command for testing email sending.',
)]
class TestSendEmailCommand extends Command
{
    public function __construct(private MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('recipient', InputArgument::REQUIRED, 'Test email recipient');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $recipient = $input->getArgument('recipient');

        $io->note(sprintf('Email will be sent to: %s', $recipient));

        $email = (new Email())
            ->to($recipient)
            ->subject('Test message from app2. You can delete it.')
            ->text('Message sending was successful.');

        $this->mailer->send($email);

        $io->success('Email has been sent.');

        return Command::SUCCESS;
    }
}
