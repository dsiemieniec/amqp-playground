<?php

declare(strict_types=1);

namespace App\Message;

use DateTimeInterface;

class AnotherSimpleCommand extends AbstractLongRunningCommand
{
    public function __construct(
        private string $firstText,
        private string $secondText,
        private DateTimeInterface $dateTime
    ) {
    }

    public function getFirstText(): string
    {
        return $this->firstText;
    }

    public function getSecondText(): string
    {
        return $this->secondText;
    }

    public function getDateTime(): DateTimeInterface
    {
        return $this->dateTime;
    }
}
