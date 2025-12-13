<?php

namespace App\Enums;

use JsonSerializable;

enum OrderStatus: int implements JsonSerializable
{
    case OPEN = 1;
    case FILLED = 2;
    case CANCELLED = 3;

    /**
     * Returns the human-readable, descriptive label.
     */
    public function label(): string
    {
        return match($this) {
            self::OPEN => 'Open',
            self::FILLED => 'Filled',
            self::CANCELLED => 'Cancelled'
        };
    }

    /**
     * Prepares the enum for JSON serialization in API responses.
     */
    public function jsonSerialize(): array
    {
        return [
            'code' => $this->value,
            'label' => $this->label(),
        ];
    }
}