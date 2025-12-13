<?php
namespace App\Enums;

use JsonSerializable;

enum Currency: string implements JsonSerializable {
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case NGN = 'NGN';
    case AED = 'AED';

    public function symbol(): string
    {
        return match ($this) {
            self::USD => '$',
            self::EUR => '€',
            self::GBP => '£',
            self::NGN => '₦',
            self::AED => 'د.إ',
        };
    }

    public function text(): string
    {
        return match ($this) {
            self::USD => 'U.S. Dollars',
            self::EUR => 'Euro',
            self::GBP => 'British Pounds',
            self::NGN => 'Nigerian Naira',
            self::AED => 'Dirham',
        };
    }

    public function decimals(): int
    {
        return 2; // expand if you support currencies with 0 or 3 decimals
    }

    public function jsonSerialize(): array
    {
        return [
            'code'   => $this->value,
            'symbol' => $this->symbol(),
            'text'   => $this->text(),
        ];
    }
}
