<?php

namespace App\Rpc;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class FibonacciRpcClient
{
    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;
    private string $callbackQueue;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            'rabbitmq',
            5672,
            'guest',
            'guest'
        );
        $this->channel = $this->connection->channel();
        [$this->callbackQueue,,] = $this->channel->queue_declare(
            "",
            false,
            false,
            true,
            false
        );
    }

    public function call($n): RpcPromise
    {
        $correlationId = uniqid();

        $msg = new AMQPMessage(
            (string) $n,
            [
                'correlation_id' => $correlationId,
                'reply_to' => $this->callbackQueue
            ]
        );
        $this->channel->basic_publish($msg, '', 'rpc_queue');

        return RpcPromise::getInstance($this->channel, $this->callbackQueue, $correlationId);
    }
}
