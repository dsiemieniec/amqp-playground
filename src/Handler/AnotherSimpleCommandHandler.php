<?php

declare(strict_types=1);

namespace App\Handler;

use App\Message\AnotherSimpleCommand;
use Psr\Log\LoggerInterface;
use Siemieniec\AmqpMessageBus\Attributes\AsMessageHandler;

#[AsMessageHandler(AnotherSimpleCommand::class)]
final class AnotherSimpleCommandHandler extends AbstractLongRunningHandler
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(AnotherSimpleCommand $command): void
    {
        $this->logger->debug(\json_encode([
            'text1' => $command->getFirstText(),
            'text2' => $command->getSecondText(),
            'dateTime' => $command->getDateTime()->format(DATE_ISO8601)
        ], JSON_THROW_ON_ERROR));

        $this->sleep($command);
    }
}
