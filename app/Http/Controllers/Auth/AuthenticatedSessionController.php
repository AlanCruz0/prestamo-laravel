<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Mail\VerificationCodeMail;
use App\Models\VerificationCode;
use App\Rules\RecaptchaValid;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->validate([
            'g-recaptcha-response' => ['required', new RecaptchaValid()],
        ]);

        $request->authenticate();

        $request->session()->regenerate();

        $this->issueVerificationCode((string) $request->user()->email);

        $request->session()->put([
            'code_verified' => false,
            'code_verified_user_id' => (int) $request->user()->id,
        ]);

        return redirect()->route('code-verification.notice');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
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
