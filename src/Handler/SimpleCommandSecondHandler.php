<?php

declare(strict_types=1);

namespace App\Handler;

use App\Message\SimpleCommand;
use Psr\Log\LoggerInterface;
use Siemieniec\AmqpMessageBus\Attributes\AsMessageHandler;

#[AsMessageHandler]
class SimpleCommandSecondHandler extends AbstractLongRunningHandler
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(SimpleCommand $simpleCommand): void
    {
        $this->logger->debug('Second Handler for simple command');

        $this->sleep($simpleCommand);
    }
}
