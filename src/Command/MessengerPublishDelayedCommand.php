<?php

namespace App\Command;

use App\Message\SimpleMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:messenger:publish-delayed'
)]
class MessengerPublishDelayedCommand extends Command
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->messageBus->dispatch(
            new SimpleMessage('Hello'),
            [
                new AmqpStamp(
                    routingKey: 'target_queue',
                    attributes: [
                        'headers' => [
                            'x-delay' => 10000
                        ]
                    ]
                )
            ]
        );

        return Command::SUCCESS;
    }
}
