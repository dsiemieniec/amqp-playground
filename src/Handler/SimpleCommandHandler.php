<?php

declare(strict_types=1);

namespace App\Handler;

use App\Message\SimpleCommand;
use Psr\Log\LoggerInterface;
use Siemieniec\AmqpMessageBus\Attributes\AsMessageHandler;

#[AsMessageHandler]
final class SimpleCommandHandler extends AbstractLongRunningHandler
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(SimpleCommand $command): void
    {
        $this->logger->debug(\json_encode([
            'id' => $command->getId(),
            'text' => $command->getText()
        ], JSON_THROW_ON_ERROR));

        $this->sleep($command);
    }
}
