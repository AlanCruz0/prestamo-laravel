<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Rules\NoConsecutiveNumbers;
use App\Rules\RecaptchaValid;
use App\Models\User;
use App\Models\VerificationCode;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults(), new NoConsecutiveNumbers()],
            'g-recaptcha-response' => ['required', new RecaptchaValid()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        $this->issueVerificationCode((string) $user->email);

        $request->session()->put([
            'code_verified' => false,
            'code_verified_user_id' => (int) $user->id,
        ]);

        return redirect()->route('code-verification.notice');
    }

    private function issueVerificationCode(string $email): void
    {
        $codeLength = (int) config('services.verification.code_length', 6);
        $expiresInMinutes = (int) config('services.verification.expires_in_minutes', 10);
        $code = $this->generateNumericCode($codeLength);

        VerificationCode::query()
            ->where('email', $email)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        VerificationCode::query()->create([
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($expiresInMinutes),
        ]);

        Mail::to($email)->send(new VerificationCodeMail($code, $expiresInMinutes));
    }

    private function generateNumericCode(int $length): string
    {
        $min = (int) str_pad('1', $length, '0');
        $max = (int) str_pad('', $length, '9');

        return (string) random_int($min, $max);
    }
}
