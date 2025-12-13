<?php

namespace App\ValueObjects;

use App\Enums\Currency;
use \JsonSerializable;

class Money implements JsonSerializable
{
    public function __construct(
        public readonly int $minor,
        public readonly ?Currency $currency = null
    ) {}

    public static function fromMinor(int $value, ?Currency $currency): self
    {
        return new self($value, $currency);
    }

    public static function fromMajor(float $value, Currency $currency): self
    {
        return new self(
            (int) round($value * (10 ** $currency->decimals())),
            $currency
        );
    }

    public function major(): float
    {
        return $this->minor / (10 ** ($this->currency?->decimals() ?? 2));
    }

    public function formatted(): string
    {
        if (!$this->currency) {
            return number_format($this->major(), 2);
        }

        return $this->currency->symbol() . number_format($this->major(), 2);
    }

    public function jsonSerialize(): array
    {
        return [
            'minor' => $this->minor,
            'major' => $this->major(),
            'currency' => $this->currency?->value,
            'formatted' => $this->formatted(),
        ];
    }
}
