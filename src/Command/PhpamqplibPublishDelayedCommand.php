<?php

namespace App\Command;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:phpamqplib:publish-delayed',
)]
class PhpamqplibPublishDelayedCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Create connection
        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        // Declare exchange
        $channel->exchange_declare(
            'delayed_exchange',
            'x-delayed-message',
            durable: true,
            auto_delete: false,
            arguments: new AMQPTable([
                'x-delayed-type' => 'direct'
            ])
        );

        // Declare queue
        $channel->queue_declare('target_queue', false, true, false, false);

        // Bind exchange to queue
        $channel->queue_bind('target_queue', 'delayed_exchange', 'target_queue');

        // Publish delayed message
        $headers = ['x-delay' => 10000];
        $msg = new AMQPMessage('Hello World!', ['application_headers' => new AMQPTable($headers)]);
        $channel->basic_publish($msg, 'delayed_exchange', 'target_queue');

        // Close connection
        $channel->close();
        $connection->close();

        return Command::SUCCESS;
    }
}
