<?php

namespace App\Rpc;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class RpcPromise
{
    private function __construct(
        private readonly RpcConsumer $consumer,
        private readonly string $correlationId
    ) {
    }

    public static function getInstance(AMQPChannel $channel, string $callbackQueue, string $correlationId): self
    {
        return new self(
            RpcConsumer::getInstance($channel, $callbackQueue),
            $correlationId
        );
    }

    public function await(): AMQPMessage
    {
        return $this->consumer->await($this->correlationId);
    }
}