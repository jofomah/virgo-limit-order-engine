<?php

namespace App\Enums;

use JsonSerializable;

enum OrderSide: string implements JsonSerializable
{
    case BUY = 'buy';
    case SELL = 'sell';

    /**
     * Returns the human-readable label for the UI.
     */
    public function label(): string
    {
        return match($this) {
            self::BUY => 'Buy',
            self::SELL => 'Sell',
        };
    }

    public function isBuy(): bool
    {
        return self::BUY === $this;
    }

    public function isSell(): bool
    {
        return self::SELL === $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
        ];
    }
}