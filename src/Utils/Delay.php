<?php

declare(strict_types=1);

namespace App\Utils;

use InvalidArgumentException;
use Stringable;

class Delay implements Stringable
{
    private function __construct(
        private int $value
    ) {
    }

    public static function milliseconds(int $value): Delay
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Value must be greater or equal to 0');
        }

        return new Delay($value);
    }

    public static function seconds(int $value): Delay
    {
        return self::milliseconds($value * 1000);
    }

    public static function minutes(int $value): Delay
    {
        return self::seconds($value * 60);
    }

    public static function hours(int $value): Delay
    {
        return self::minutes($value * 60);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->getValue();
    }
}
