<?php

use App\Http\Controllers\Admin\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\TravelOfferController as AdminTravelOfferController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Divertissement\EventController;
use App\Http\Controllers\Fret\ShipmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Logement\LodgingListingController;
use App\Http\Controllers\Marketplace\ProductController;
use App\Http\Controllers\Partner\AuthenticatedSessionController as PartnerAuthenticatedSessionController;
use App\Http\Controllers\Partner\DashboardController as PartnerDashboardController;
use App\Http\Controllers\Partner\EventController as PartnerEventController;
use App\Http\Controllers\Partner\LodgingListingController as PartnerLodgingListingController;
use App\Http\Controllers\Partner\ProductController as PartnerProductController;
use App\Http\Controllers\Partner\VehicleController as PartnerVehicleController;
use App\Http\Controllers\Voiture\VehicleController;
use App\Http\Controllers\Voyage\BookingController as VoyageBookingController;
use App\Http\Controllers\Voyage\TravelOfferController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes (Inertia + Vue 3)
|--------------------------------------------------------------------------
| Public marketing site + authenticated booking/checkout flow for the
| 6 MOKILI TOUR services: Voyage, Logement, Voiture, Divertissement,
| Marketplace, Fret.
*/

Route::get('/', HomeController::class)->name('home');

// --- VOYAGE (pilot module - full CRUD + booking + checkout) ---
Route::prefix('voyage')->name('voyage.')->group(function () {
    Route::get('/', [TravelOfferController::class, 'index'])->name('index');
    Route::get('/{travelOffer:slug}', [TravelOfferController::class, 'show'])->name('show');

    Route::middleware('auth')->group(function () {
        Route::post('/{travelOffer:slug}/reserver', [VoyageBookingController::class, 'store'])->name('book');
    });
});

// --- LOGEMENT / VOITURE / DIVERTISSEMENT / MARKETPLACE / FRET (skeletons) ---
Route::prefix('logement')->name('logement.')->group(function () {
    Route::get('/', [LodgingListingController::class, 'index'])->name('index');
    Route::get('/{lodgingListing:slug}', [LodgingListingController::class, 'show'])->name('show');
});

Route::prefix('voiture')->name('voiture.')->group(function () {
    Route::get('/', [VehicleController::class, 'index'])->name('index');
    Route::get('/{vehicle:slug}', [VehicleController::class, 'show'])->name('show');
});

Route::prefix('divertissement')->name('divertissement.')->group(function () {
    Route::get('/', [EventController::class, 'index'])->name('index');
    Route::get('/{event:slug}', [EventController::class, 'show'])->name('show');
});

Route::prefix('marketplace')->name('marketplace.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/{product:slug}', [ProductController::class, 'show'])->name('show');
});

Route::middleware('auth')->prefix('fret')->name('fret.')->group(function () {
    Route::get('/', [ShipmentController::class, 'index'])->name('index');
    Route::get('/{shipment}', [ShipmentController::class, 'show'])->name('show');
});

// --- Shared checkout (Peex wallet verification -> payment), any module ---
Route::middleware('auth')->group(function () {
    Route::get('/checkout/{booking}', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/{booking}/payer', [CheckoutController::class, 'pay'])
        ->middleware('peex.verified')
        ->name('checkout.pay');
});

// --- Separate, non-public login entry points for staff/partners ---
// (distinct from the client-facing /login below; not linked from the
// main site nav - the URL is handed directly to staff/partners).
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AdminAuthenticatedSessionController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthenticatedSessionController::class, 'store']);

    Route::get('/partner/login', [PartnerAuthenticatedSessionController::class, 'create'])->name('partner.login');
    Route::post('/partner/login', [PartnerAuthenticatedSessionController::class, 'store']);
});

// --- ADMIN back-office (staff: admin + agent) ---
Route::middleware(['auth', 'role:admin,agent'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('/voyage', [AdminTravelOfferController::class, 'index'])->name('voyage.index');
    Route::get('/voyage/creer', [AdminTravelOfferController::class, 'create'])->name('voyage.create');
    Route::post('/voyage', [AdminTravelOfferController::class, 'store'])->name('voyage.store');
    Route::get('/voyage/{travelOffer}/editer', [AdminTravelOfferController::class, 'edit'])->name('voyage.edit');
    Route::put('/voyage/{travelOffer}', [AdminTravelOfferController::class, 'update'])->name('voyage.update');
    Route::delete('/voyage/{travelOffer}', [AdminTravelOfferController::class, 'destroy'])->name('voyage.destroy');

    Route::get('/reservations', [AdminBookingController::class, 'index'])->name('bookings.index');

    // User/agent/partner account management - creating agent or admin
    // accounts is intentionally left possible only to existing admins.
    Route::get('/utilisateurs', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/utilisateurs/creer', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/utilisateurs', [AdminUserController::class, 'store'])->name('users.store');
});

// --- PARTNER space (role: partner) - manage own listings ---
Route::middleware(['auth', 'role:partner'])->prefix('partner')->name('partner.')->group(function () {
    Route::get('/', PartnerDashboardController::class)->name('dashboard');

    Route::get('/logement', [PartnerLodgingListingController::class, 'index'])->name('logement.index');
    Route::get('/logement/creer', [PartnerLodgingListingController::class, 'create'])->name('logement.create');
    Route::post('/logement', [PartnerLodgingListingController::class, 'store'])->name('logement.store');
    Route::get('/logement/{listing}/editer', [PartnerLodgingListingController::class, 'edit'])->name('logement.edit');
    Route::put('/logement/{listing}', [PartnerLodgingListingController::class, 'update'])->name('logement.update');
    Route::delete('/logement/{listing}', [PartnerLodgingListingController::class, 'destroy'])->name('logement.destroy');

    Route::get('/voiture', [PartnerVehicleController::class, 'index'])->name('voiture.index');
    Route::get('/voiture/creer', [PartnerVehicleController::class, 'create'])->name('voiture.create');
    Route::post('/voiture', [PartnerVehicleController::class, 'store'])->name('voiture.store');
    Route::get('/voiture/{vehicle}/editer', [PartnerVehicleController::class, 'edit'])->name('voiture.edit');
    Route::put('/voiture/{vehicle}', [PartnerVehicleController::class, 'update'])->name('voiture.update');
    Route::delete('/voiture/{vehicle}', [PartnerVehicleController::class, 'destroy'])->name('voiture.destroy');

    Route::get('/divertissement', [PartnerEventController::class, 'index'])->name('divertissement.index');
    Route::get('/divertissement/creer', [PartnerEventController::class, 'create'])->name('divertissement.create');
    Route::post('/divertissement', [PartnerEventController::class, 'store'])->name('divertissement.store');
    Route::get('/divertissement/{event}/editer', [PartnerEventController::class, 'edit'])->name('divertissement.edit');
    Route::put('/divertissement/{event}', [PartnerEventController::class, 'update'])->name('divertissement.update');
    Route::delete('/divertissement/{event}', [PartnerEventController::class, 'destroy'])->name('divertissement.destroy');

    Route::get('/marketplace', [PartnerProductController::class, 'index'])->name('marketplace.index');
    Route::get('/marketplace/creer', [PartnerProductController::class, 'create'])->name('marketplace.create');
    Route::post('/marketplace', [PartnerProductController::class, 'store'])->name('marketplace.store');
    Route::get('/marketplace/{product}/editer', [PartnerProductController::class, 'edit'])->name('marketplace.edit');
    Route::put('/marketplace/{product}', [PartnerProductController::class, 'update'])->name('marketplace.update');
    Route::delete('/marketplace/{product}', [PartnerProductController::class, 'destroy'])->name('marketplace.destroy');
});

// --- Auth (Breeze-style, Inertia) ---
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
