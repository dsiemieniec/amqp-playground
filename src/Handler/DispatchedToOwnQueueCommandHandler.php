<?php

declare(strict_types=1);

namespace App\Handler;

use App\Message\DispatchedToOwnQueueCommand;
use App\Service\SomeTestService;
use Psr\Log\LoggerInterface;
use Siemieniec\AmqpMessageBus\Attributes\AsMessageHandler;

#[AsMessageHandler]
final class DispatchedToOwnQueueCommandHandler extends AbstractLongRunningHandler
{
    public function __construct(
        private SomeTestService $service,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(DispatchedToOwnQueueCommand $command): void
    {
        $this->logger->debug(\json_encode([
            'id' => $command->getId(),
            'text' => $command->getText()
        ], JSON_THROW_ON_ERROR));

        $this->service->doSomething();

        $this->sleep($command);
    }
}
