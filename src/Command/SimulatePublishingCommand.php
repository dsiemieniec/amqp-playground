<?php

declare(strict_types=1);

namespace App\Command;

use App\Message\AbstractLongRunningCommand;
use App\Message\AnotherSimpleCommand;
use App\Message\DispatchedToOwnQueueCommand;
use App\Message\SimpleCommand;
use App\Utils\Delay;
use DateTimeImmutable;
use Siemieniec\AmqpMessageBus\Message\MessagePublisherInterface;
use Siemieniec\AmqpMessageBus\Message\Properties\MessageProperties;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:simulate-publishing',
)]
class SimulatePublishingCommand extends Command
{
    public function __construct(
        private MessagePublisherInterface $messagePublisher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('numberOfCommands', InputArgument::REQUIRED, 'Number of commands to publish')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $numberOfCommands = (int)$input->getArgument('numberOfCommands');
        $progressBar = new ProgressBar($output, $numberOfCommands);

        $progressBar->start();

        while ($numberOfCommands > 0) {
            $i = \random_int(1, 30);
            while ($i > 0 && $numberOfCommands > 0) {
                $properties = MessageProperties::builder()
                    ->addHeader('x-delay', (string) Delay::seconds(\random_int(1, 15)))
                    ->build();
                $this->messagePublisher->publish(
                    $this->getRandomCommand()->setExecutionTime(\random_int(0, 5)),
                    $properties
                );

                --$i;
                --$numberOfCommands;
                $progressBar->advance();
            }
            \sleep(\random_int(1, 15));
        }
        $progressBar->finish();
        $io->success('Done');

        return Command::SUCCESS;
    }

    private function getRandomCommand(): AbstractLongRunningCommand
    {
        $i = \random_int(0, 2);
        if ($i === 0) {
            return new SimpleCommand(\random_int($i, 99999999), \uniqid('', true));
        } elseif ($i === 1) {
            return new AnotherSimpleCommand(
                \uniqid('', true),
                \uniqid('', true),
                new DateTimeImmutable()
            );
        }

        return new DispatchedToOwnQueueCommand(\random_int($i, 99999999), \uniqid('', true));
    }
}
