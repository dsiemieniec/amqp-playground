<?php

namespace App\Rpc;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class RpcConsumer
{
    /** @var array<string, RpcConsumer>  */
    private static array $instances = [];

    /** @var array<string, AMQPMessage> */
    private array $responses = [];

    private function __construct(
        private readonly AMQPChannel $channel,
        private readonly string $callbackQueue
    ) {
        $this->channel->basic_consume(
            $this->callbackQueue,
            '',
            false,
            true,
            false,
            false,
            fn(AMQPMessage $response) => $this->responses[$response->get('correlation_id')] = $response
        );
    }

    public static function getInstance(AMQPChannel $channel, string $callbackQueue): self
    {
        if (!\array_key_exists($callbackQueue, self::$instances)) {
            self::$instances[$callbackQueue] = new self($channel, $callbackQueue);
        }

        return self::$instances[$callbackQueue];
    }

    public function await(string $correlationId): AMQPMessage
    {
        while (!\array_key_exists($correlationId, $this->responses)) {
            $this->channel->wait();
        }

        return $this->responses[$correlationId];
    }
}
