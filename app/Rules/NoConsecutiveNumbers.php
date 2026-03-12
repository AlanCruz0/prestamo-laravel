<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoConsecutiveNumbers implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password = (string) $value;

        if ($this->hasConsecutiveNumbers($password)) {
            $fail('La contrasena no puede contener numeros consecutivos (por ejemplo: 123 o 432).');
        }
    }

    private function hasConsecutiveNumbers(string $value): bool
    {
        $previousDigit = null;
        $ascendingRun = 1;
        $descendingRun = 1;

        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];

            if (! ctype_digit($char)) {
                $previousDigit = null;
                $ascendingRun = 1;
                $descendingRun = 1;
                continue;
            }

            $currentDigit = (int) $char;

            if ($previousDigit === null) {
                $previousDigit = $currentDigit;
                continue;
            }

            if ($currentDigit === $previousDigit + 1) {
                $ascendingRun++;
            } else {
                $ascendingRun = 1;
            }

            if ($currentDigit === $previousDigit - 1) {
                $descendingRun++;
            } else {
                $descendingRun = 1;
            }

            if ($ascendingRun >= 3 || $descendingRun >= 3) {
                return true;
            }

            $previousDigit = $currentDigit;
        }

        return false;
    }
}
