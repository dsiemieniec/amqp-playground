<?php

namespace App\Handler;

use App\Message\SimpleMessage;
use Siemieniec\AmqpMessageBus\Attributes\AsMessageHandler as AsAmqpBusMessageHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler as AsMessengerMessageHandler;

//#[AsMessengerMessageHandler]
#[AsAmqpBusMessageHandler]
class SimpleMessageHandler
{
    public function __invoke(SimpleMessage $message): void
    {
        echo $message->getMessage() . PHP_EOL;
    }
}
