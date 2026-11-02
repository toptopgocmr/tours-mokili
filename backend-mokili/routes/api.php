<?php

use App\Http\Controllers\Api\AttractionParkController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\FlightController;
use App\Http\Controllers\Api\FreightOfferController;
use App\Http\Controllers\Api\LodgingListingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentWebhookController;
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

// Moyens de paiement disponibles (pays, operateurs, passerelles) - public.
Route::get('/payments/methods', [PaymentController::class, 'methods']);

// Callbacks des agregateurs de paiement (sans authentification : la
// verification se fait dans chaque passerelle).
Route::match(['get', 'post'], '/webhooks/payments/{gateway}', PaymentWebhookController::class)
    ->whereIn('gateway', ['peex', 'pawapay', 'maishapay', 'digitwace']);
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
Route::get('/voyage/transport-modes', [TravelOfferController::class, 'modes']);
Route::get('/voyage/offers/{travelOffer:slug}', [TravelOfferController::class, 'show']);

// Billetterie aerienne (moteur central, meme logique que le site).
Route::get('/flights/airports', [FlightController::class, 'airports'])->middleware('throttle:60,1');
Route::post('/flights/search', [FlightController::class, 'search'])->middleware('throttle:20,1');
Route::get('/flights/searches/{uuid}', [FlightController::class, 'offers']);
Route::get('/flights/offers/{flightOffer}', [FlightController::class, 'offer']);

// LOGEMENT (style Booking.com), VOITURE, DIVERTISSEMENT, MARKETPLACE
// (style Amazon) and FRET (style DHL) catalogues - public browsing like
// Voyage, "book" actions require login (see auth:sanctum group below).
Route::get('/logement/listings', [LodgingListingController::class, 'index']);
Route::get('/logement/listings/{listing:slug}', [LodgingListingController::class, 'show']);

Route::get('/voiture/vehicles', [VehicleController::class, 'index']);
Route::get('/voiture/vehicles/{vehicle:slug}', [VehicleController::class, 'show']);

Route::get('/divertissement/events', [EventController::class, 'index']);
Route::get('/divertissement/events/{event:slug}', [EventController::class, 'show']);

// LOISIRS - parcs d'attractions du Congo (public).
Route::get('/loisirs/categories', [AttractionParkController::class, 'categories']);
Route::get('/loisirs/parks', [AttractionParkController::class, 'index']);
Route::get('/loisirs/parks/{attractionPark:slug}', [AttractionParkController::class, 'show']);

// Societes partenaires par rubrique (?module=logement|voyage|voiture|divertissement|marketplace).
Route::get('/partners', [BusinessController::class, 'index']);
Route::get('/partners/{business:slug}', [BusinessController::class, 'show']);

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
    // Paiement Mobile Money multi-passerelles (MaishaPay, Digitwace, Peex, pawaPay).
    Route::post('/bookings/{booking}/pay', [PaymentController::class, 'pay']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);

    Route::post('/logement/listings/{listing:slug}/book', [LodgingListingController::class, 'book']);
    Route::post('/voiture/vehicles/{vehicle:slug}/book', [VehicleController::class, 'book']);
    Route::post('/divertissement/events/{event:slug}/book', [EventController::class, 'book']);
    Route::post('/marketplace/products/{product:slug}/book', [ProductController::class, 'book']);
    Route::post('/loisirs/parks/{attractionPark:slug}/book', [AttractionParkController::class, 'book']);
    Route::post('/fret/offers/{offer:slug}/book', [FreightOfferController::class, 'book']);

    Route::post('/flights/offers/{flightOffer}/book', [FlightController::class, 'book'])->middleware('throttle:10,1');
    Route::get('/flights/bookings', [FlightController::class, 'index']);
    Route::get('/flights/bookings/{flightBooking}', [FlightController::class, 'show']);
    Route::post('/flights/bookings/{flightBooking}/confirm-price', [FlightController::class, 'confirmPrice']);
    Route::post('/flights/bookings/{flightBooking}/after-sales', [FlightController::class, 'afterSale']);
});
