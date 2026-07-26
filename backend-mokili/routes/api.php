<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PhoneOtpController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\TravelOfferController;
use App\Http\Controllers\Api\WalletVerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes (Sanctum token auth) - consumed by the Flutter mobile app.
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// "Continuer avec Google / Facebook / Instagram" - see SocialAuthController
// docblock for the full redirect/callback flow.
Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->whereIn('provider', ['google', 'facebook', 'instagram']);
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->whereIn('provider', ['google', 'facebook', 'instagram']);

// "Continuer avec mon numero" - phone/OTP sign-in, see PhoneOtpController.
Route::post('/auth/phone/send-code', [PhoneOtpController::class, 'send']);
Route::post('/auth/phone/verify-code', [PhoneOtpController::class, 'verify']);

// VOYAGE catalogue is public (browse without login, like the web site).
Route::get('/voyage/offers', [TravelOfferController::class, 'index']);
Route::get('/voyage/offers/{travelOffer:slug}', [TravelOfferController::class, 'show']);

// TODO: expose /logement/listings, /voiture/vehicles, /divertissement/events,
// /marketplace/products, /fret/shipments the same way once each module's
// mobile screens move past their current placeholder state.

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', fn (Request $request) => $request->user());

    Route::post('/wallet/verify', [WalletVerificationController::class, 'store']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/voyage/offers/{travelOffer:slug}/book', [BookingController::class, 'store']);
    Route::post('/bookings/{booking}/pay', [BookingController::class, 'pay']);
});
