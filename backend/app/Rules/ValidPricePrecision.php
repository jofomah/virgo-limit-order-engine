<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPricePrecision implements ValidationRule
{
    private int $scale = 8; // Your system's price scale

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $valueString = trim((string) $value);

        // Must be > 0
        if (bccomp($valueString, '0', $this->scale) <= 0) {
            $fail("The {$attribute} must be greater than zero.");
            return;
        }

        // Check decimal precision
        if (str_contains($valueString, '.')) {
            $decimals = strlen(substr(strrchr($valueString, '.'), 1));

            if ($decimals > $this->scale) {
                $fail("The {$attribute} may have at most {$this->scale} decimal places.");
            }
        }
    }
}
