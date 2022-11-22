<?php

declare(strict_types=1);

namespace App\Handler;

use App\Message\LongRunningCommand;
use Siemieniec\AmqpMessageBus\Attributes\AsMessageHandler;

#[AsMessageHandler]
class LongRunningHandler
{
    public function __invoke(LongRunningCommand $command): void
    {
        echo PHP_EOL;
        $seconds = $command->getSeconds();
        echo \sprintf('Running for %d second(s)...', $seconds) . PHP_EOL;
        while ($seconds > 0) {
            \sleep(1);
            echo \sprintf('%d second(s) left', --$seconds) . PHP_EOL;
        }
        echo 'Finished' . PHP_EOL;
    }
}
