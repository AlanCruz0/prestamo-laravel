<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use ReCaptcha\ReCaptcha;

class RecaptchaValid implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Permitir desarrollo sin verificación si RECAPTCHA_SKIP_VERIFICATION=true
        if (config('services.recaptcha.skip_verification')) {
            return;
        }

        $secretKey = config('services.recaptcha.secret_key');
        
        if (!$secretKey) {
            $fail('La configuracion de reCAPTCHA no esta completa.');
            return;
        }

        $recaptcha = new ReCaptcha($secretKey);
        $response = $recaptcha->verify((string) $value);

        if (! $response->isSuccess()) {
            $fail('La verificacion de reCAPTCHA fallo. Intenta de nuevo.');
        }
    }
}
