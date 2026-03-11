<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendVerificationCodeRequest;
use App\Http\Requests\Auth\VerifyCodeRequest;
use App\Mail\VerificationCodeMail;
use App\Models\VerificationCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VerificationCodeController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Auth/VerifyCode', [
            'email' => (string) $request->user()->email,
            'status' => session('status'),
        ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $email = (string) $request->user()->email;

        $expiresInMinutes = $this->issueCode($email);

        $request->session()->put([
            'code_verified' => false,
            'code_verified_user_id' => (int) $request->user()->id,
        ]);

        return back()->with('status', 'Te enviamos un nuevo codigo. Expira en '.$expiresInMinutes.' minutos.');
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function submit(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:'.config('services.verification.code_length', 6)],
        ]);

        $email = (string) $request->user()->email;
        $code = (string) $request->string('code');

        $verificationCode = VerificationCode::query()
            ->where('email', $email)
            ->active()
            ->latest('id')
            ->first();

        if (! $verificationCode || ! Hash::check($code, $verificationCode->code_hash)) {
            throw ValidationException::withMessages([
                'code' => 'El codigo es invalido o ha expirado.',
            ]);
        }

        $verificationCode->update([
            'used_at' => now(),
        ]);

        $request->session()->put([
            'code_verified' => true,
            'code_verified_user_id' => (int) $request->user()->id,
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function send(SendVerificationCodeRequest $request): JsonResponse
    {
        $email = (string) $request->string('email');
        $expiresInMinutes = $this->issueCode($email);

        return response()->json([
            'message' => 'Codigo enviado correctamente.',
            'expires_in_minutes' => $expiresInMinutes,
        ]);
    }

    public function verify(VerifyCodeRequest $request): JsonResponse
    {
        $email = (string) $request->string('email');
        $code = (string) $request->string('code');

        $verificationCode = VerificationCode::query()
            ->where('email', $email)
            ->active()
            ->latest('id')
            ->first();

        if (! $verificationCode || ! Hash::check($code, $verificationCode->code_hash)) {
            return response()->json([
                'message' => 'El codigo es invalido o ha expirado.',
            ], 422);
        }

        $verificationCode->update([
            'used_at' => now(),
        ]);

        return response()->json([
            'message' => 'Codigo verificado correctamente.',
            'verified' => true,
        ]);
    }

    private function generateNumericCode(int $length): string
    {
        $min = (int) str_pad('1', $length, '0');
        $max = (int) str_pad('', $length, '9');

        return (string) random_int($min, $max);
    }

    private function issueCode(string $email): int
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

        return $expiresInMinutes;
    }
}
