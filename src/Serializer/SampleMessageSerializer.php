<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Message\AnotherSimpleCommand;
use DateTimeImmutable;
use Siemieniec\AmqpMessageBus\Exception\DeserializationException;
use Siemieniec\AmqpMessageBus\Exception\SerializationException;
use Siemieniec\AmqpMessageBus\Message\Properties\MessageProperties;
use Siemieniec\AmqpMessageBus\Rabbit\MessageEnvelope;
use Siemieniec\AmqpMessageBus\Rabbit\MessageEnvelopeInterface;
use Siemieniec\AmqpMessageBus\Serializer\MessageSerializerInterface;

class SampleMessageSerializer implements MessageSerializerInterface
{
    public function serialize(object $message, MessageProperties $properties): MessageEnvelopeInterface
    {
        /** @var AnotherSimpleCommand $message */

        $body = \json_encode([
            'first_text' => $message->getFirstText(),
            'second_text' => $message->getSecondText(),
            'date_time' => $message->getDateTime()->format(DATE_ISO8601)
        ]);
        if ($body === false) {
            throw new SerializationException(\json_last_error_msg());
        }

        return new MessageEnvelope($body, \get_class($message), $properties);
    }

    public function deserialize(MessageEnvelopeInterface $envelope): AnotherSimpleCommand
    {
        $data = \json_decode((string)$envelope->getBody(), true);

        $dateTime = DateTimeImmutable::createFromFormat(DATE_ISO8601, $data['date_time']);
        if ($dateTime === false) {
            throw new DeserializationException(
                \sprintf('Invalid date format. Expected %s given  %s', DATE_ISO8601, $data['date_time'])
            );
        }

        return new AnotherSimpleCommand(
            $data['first_text'],
            $data['second_text'],
            $dateTime
        );
    }
}
