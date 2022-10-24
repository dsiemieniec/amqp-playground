<?php

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SimpleMessageHandler
{
    public function __invoke(SimpleMessage $message): void
    {
        echo $message->getMessage() . PHP_EOL;
    }
}
