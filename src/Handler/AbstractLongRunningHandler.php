<?php

declare(strict_types=1);

namespace App\Handler;

use App\Message\AbstractLongRunningCommand;

abstract class AbstractLongRunningHandler
{
    protected function sleep(AbstractLongRunningCommand $command): void
    {
        \sleep($command->getExecutionTime());
    }
}
