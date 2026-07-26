<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhoneOtp;
use App\Models\User;
use App\Services\Sms\SmsGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * "Continuer avec mon numero" - phone/OTP sign-in for the Flutter app.
 * Two steps: POST /send-code texts a 6-digit code, POST /verify-code
 * checks it and creates-or-logs-in the User, same response shape as
 * AuthController::login()/register() (user + Sanctum token).
 */
class PhoneOtpController extends Controller
{
    public function __construct(private readonly SmsGateway $sms)
    {
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'min:6', 'max:20'],
        ]);
        $phone = $data['phone'];

        $rateLimitKey = 'phone-otp-send:'.$phone;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            throw ValidationException::withMessages([
                'phone' => ['Trop de tentatives, merci de reessayer dans quelques minutes.'],
            ]);
        }
        RateLimiter::hit($rateLimitKey, 300); // 3 sends / 5 min per phone

        $code = (string) random_int(100000, 999999);

        PhoneOtp::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->sms->send($phone, "Votre code MOKILI TOUR : $code (valable 5 minutes).");

        return response()->json(['message' => 'Code envoye par SMS.']);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $otp = PhoneOtp::where('phone', $data['phone'])
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $otp || $otp->attempts >= 5) {
            throw ValidationException::withMessages([
                'code' => ['Code invalide ou expire.'],
            ]);
        }

        if (! Hash::check($data['code'], $otp->code_hash)) {
            $otp->increment('attempts');

            throw ValidationException::withMessages([
                'code' => ['Code invalide ou expire.'],
            ]);
        }

        $otp->update(['verified_at' => now()]);

        $user = DB::transaction(function () use ($data) {
            $user = User::where('phone', $data['phone'])->first();

            if (! $user) {
                $user = User::create([
                    'name' => 'Utilisateur '.substr($data['phone'], -4),
                    'email' => 'tel_'.preg_replace('/\D/', '', $data['phone']).'@social.mokili.local',
                    'phone' => $data['phone'],
                    'password' => Hash::make(Str::random(40)),
                    'phone_verified_at' => now(),
                ]);
            } elseif (! $user->phone_verified_at) {
                $user->update(['phone_verified_at' => now()]);
            }

            return $user;
        });

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('mokili-mobile')->plainTextToken,
        ]);
    }
}
