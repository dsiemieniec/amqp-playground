<?php

namespace App\Command;

use App\Message\SimpleMessage;
use Siemieniec\AmqpMessageBus\Message\MessagePublisherInterface;
use Siemieniec\AmqpMessageBus\Message\Properties\MessageProperties;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:amqp-mmessage-bus:publish-delayed'
)]
class AmqpMessageBusPublishDelayedCommand extends Command
{
    public function __construct(
        private MessagePublisherInterface $publisher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $properties = MessageProperties::builder()
            ->addHeader('x-delay', 30000)
            ->build();

        $this->publisher->publish(new SimpleMessage('Hello'), $properties);

        return Command::SUCCESS;
    }
}
