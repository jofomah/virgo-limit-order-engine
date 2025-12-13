<?php

namespace App\Rules;

use App\Models\AssetType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidAmountPrecision implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $symbol = request()->input('symbol');
        $assetType = AssetType::find($symbol);

        if (!$assetType) {
            $fail("Invalid asset symbol.");
            return;
        }

        $scale = $assetType->atomic_scale;

        // Convert to string safely
        $valueString = trim((string) $value);

        // Reject zero or negative
        if (bccomp($valueString, '0', $scale) <= 0) {
            $fail("The {$attribute} must be greater than zero.");
            return;
        }

        // Count decimals in the user input
        if (str_contains($valueString, '.')) {
            $decimals = strlen(substr(strrchr($valueString, '.'), 1));

            if ($decimals > $scale) {
                $fail("The {$attribute} may have at most {$scale} decimal places for {$symbol}.");
            }
        }
    }
}
