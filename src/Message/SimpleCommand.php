<?php

declare(strict_types=1);

namespace App\Message;

class SimpleCommand extends AbstractLongRunningCommand
{
    public function __construct(
        private int $id,
        private string $text
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
