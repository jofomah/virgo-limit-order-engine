<?php

namespace App\Casts;

use App\ValueObjects\Money;
use App\Enums\Currency;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class MoneyCast implements CastsAttributes
{
    public function __construct(
        protected ?string $currencyField = null
    ) {}

    public static function fromArgs(array $arguments): self
    {
        return new self($arguments[0] ?? null);
    }

    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return Money::fromMinor(0, $this->resolveCurrency($attributes));
        }

        return Money::fromMinor((int) $value, $this->resolveCurrency($attributes));
    }

    public function set($model, string $key, $value, array $attributes)
    {
        // Allow passing a Money object directly
        if ($value instanceof Money) {
            return [
                $key => $value->minor,
                $this->currencyField => $value->currency->value,
            ];
        }

        // Allow integer / numeric values (minor units)
        if (is_numeric($value)) {
            return [
                $key => (int) $value,
            ];
        }

        // Fallback for unexpected values
        if ($value === null) {
            return [
                $key => 0,
            ];
        }

        throw new \InvalidArgumentException("Invalid value for MoneyCast on field {$key}");
    }

    private function resolveCurrency(array $attributes): ?Currency
    {
        if (!$this->currencyField) {
            return null;
        }

        $code = $attributes[$this->currencyField] ?? null;

        return $code ? Currency::from($code) : null;
    }
}
