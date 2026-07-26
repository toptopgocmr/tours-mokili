<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\FreightOfferController;
use App\Http\Controllers\Api\LodgingListingController;
use App\Http\Controllers\Api\PhoneOtpController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\TravelOfferController;
use App\Http\Controllers\Api\VehicleController;
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

// LOGEMENT (style Booking.com), VOITURE, DIVERTISSEMENT, MARKETPLACE
// (style Amazon) and FRET (style DHL) catalogues - public browsing like
// Voyage, "book" actions require login (see auth:sanctum group below).
Route::get('/logement/listings', [LodgingListingController::class, 'index']);
Route::get('/logement/listings/{listing:slug}', [LodgingListingController::class, 'show']);

Route::get('/voiture/vehicles', [VehicleController::class, 'index']);
Route::get('/voiture/vehicles/{vehicle:slug}', [VehicleController::class, 'show']);

Route::get('/divertissement/events', [EventController::class, 'index']);
Route::get('/divertissement/events/{event:slug}', [EventController::class, 'show']);

Route::get('/marketplace/products', [ProductController::class, 'index']);
Route::get('/marketplace/products/{product:slug}', [ProductController::class, 'show']);

Route::get('/fret/offers', [FreightOfferController::class, 'index']);
Route::get('/fret/offers/{offer:slug}', [FreightOfferController::class, 'show']);

// "Suivre un colis" - public, like DHL/UPS tracking pages.
Route::get('/fret/track/{code}', [ShipmentController::class, 'track']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', fn (Request $request) => $request->user());

    Route::post('/wallet/verify', [WalletVerificationController::class, 'store']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/voyage/offers/{travelOffer:slug}/book', [BookingController::class, 'store']);
    Route::post('/bookings/{booking}/pay', [BookingController::class, 'pay']);

    Route::post('/logement/listings/{listing:slug}/book', [LodgingListingController::class, 'book']);
    Route::post('/voiture/vehicles/{vehicle:slug}/book', [VehicleController::class, 'book']);
    Route::post('/divertissement/events/{event:slug}/book', [EventController::class, 'book']);
    Route::post('/marketplace/products/{product:slug}/book', [ProductController::class, 'book']);
    Route::post('/fret/offers/{offer:slug}/book', [FreightOfferController::class, 'book']);
});
