<?php

use App\Http\Controllers\Admin\AttractionParkController as AdminAttractionParkController;
use App\Http\Controllers\Admin\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\BusinessController as AdminBusinessController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FlightBookingController as AdminFlightBookingController;
use App\Http\Controllers\Admin\FlightFareController as AdminFlightFareController;
use App\Http\Controllers\Admin\GatewayController as AdminGatewayController;
use App\Http\Controllers\Admin\ModerationController as AdminModerationController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PayoutController as AdminPayoutController;
use App\Http\Controllers\Admin\TravelOfferController as AdminTravelOfferController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Divertissement\EventController;
use App\Http\Controllers\Fret\BookingController as FretBookingController;
use App\Http\Controllers\Fret\FreightOfferController;
use App\Http\Controllers\Fret\ShipmentController;
use App\Http\Controllers\Guide\BusinessController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Logement\BookingController as LogementBookingController;
use App\Http\Controllers\Logement\LodgingListingController;
use App\Http\Controllers\Loisirs\AttractionParkController;
use App\Http\Controllers\Marketplace\ProductController;
use App\Http\Controllers\Partner\AuthenticatedSessionController as PartnerAuthenticatedSessionController;
use App\Http\Controllers\Partner\DashboardController as PartnerDashboardController;
use App\Http\Controllers\Partner\EventController as PartnerEventController;
use App\Http\Controllers\Partner\FreightOfferController as PartnerFreightOfferController;
use App\Http\Controllers\Partner\LodgingListingController as PartnerLodgingListingController;
use App\Http\Controllers\Partner\ProductController as PartnerProductController;
use App\Http\Controllers\Partner\VehicleController as PartnerVehicleController;
use App\Http\Controllers\Voiture\VehicleController;
use App\Http\Controllers\Voyage\BookingController as VoyageBookingController;
use App\Http\Controllers\Voyage\FlightController;
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

// TEMPORARY debug route - remove once the Mixed Content issue is diagnosed.
Route::get('/__debug-scheme', function () {
    return response()->json([
        'config_app_url' => config('app.url'),
        'request_getScheme' => request()->getScheme(),
        'request_isSecure' => request()->isSecure(),
        'request_root' => request()->root(),
        'url_to_slash' => url('/'),
        'asset_test' => asset('build/test.js'),
        'route_home' => route('home'),
        'server_HTTPS' => $_SERVER['HTTPS'] ?? null,
        'server_X_Forwarded_Proto' => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null,
        'server_APP_URL' => $_SERVER['APP_URL'] ?? null,
        'getenv_APP_URL' => getenv('APP_URL'),
    ]);
});

// --- VOYAGE (pilot module - full CRUD + booking + checkout) ---
Route::prefix('voyage')->name('voyage.')->group(function () {
    Route::get('/', [TravelOfferController::class, 'index'])->name('index');

    // --- VOLS : billetterie aerienne (cahier des charges Billetterie) ---
    // Declare avant le joker {travelOffer:slug} pour que "vols" ne soit pas lu comme un slug.
    Route::prefix('vols')->name('vols.')->group(function () {
        Route::get('/', fn () => redirect('/voyage?type=vol'))->name('home');
        Route::get('/aeroports', [FlightController::class, 'airports'])->middleware('throttle:60,1')->name('airports');
        Route::get('/horaires', [FlightController::class, 'schedules'])->middleware('throttle:30,1')->name('schedules');
        Route::get('/recherche', [FlightController::class, 'search'])->middleware('throttle:20,1')->name('search');
        Route::get('/resultats/{uuid}', [FlightController::class, 'results'])->name('results');

        Route::middleware('auth')->group(function () {
            Route::get('/offres/{flightOffer}', [FlightController::class, 'offer'])->name('offer');
            Route::post('/offres/{flightOffer}/reserver', [FlightController::class, 'book'])->middleware('throttle:10,1')->name('book');
            Route::get('/mes-billets', [FlightController::class, 'index'])->name('dossiers');
            Route::get('/dossiers/{flightBooking}', [FlightController::class, 'show'])->name('dossier');
            Route::post('/dossiers/{flightBooking}/confirmer-prix', [FlightController::class, 'confirmPrice'])->name('confirm-price');
            Route::post('/dossiers/{flightBooking}/annuler', [FlightController::class, 'cancel'])->name('cancel');
            Route::post('/dossiers/{flightBooking}/apres-vente', [FlightController::class, 'afterSale'])->middleware('throttle:5,1')->name('after-sale');
            Route::get('/dossiers/{flightBooking}/document/{type}', [FlightController::class, 'document'])
                ->whereIn('type', ['recu', 'confirmation', 'billet'])->name('document');
        });
    });
    Route::get('/{travelOffer:slug}', [TravelOfferController::class, 'show'])->name('show');

    Route::middleware('auth')->group(function () {
        Route::post('/{travelOffer:slug}/reserver', [VoyageBookingController::class, 'store'])->name('book');
    });
});

// --- LOGEMENT / VOITURE / DIVERTISSEMENT / MARKETPLACE / FRET (skeletons) ---
Route::prefix('logement')->name('logement.')->group(function () {
    Route::get('/', [LodgingListingController::class, 'index'])->name('index');
    Route::get('/{lodgingListing:slug}', [LodgingListingController::class, 'show'])->name('show');

    Route::middleware('auth')->group(function () {
        Route::post('/{lodgingListing:slug}/reserver', [LogementBookingController::class, 'store'])->name('book');
    });
});

Route::prefix('voiture')->name('voiture.')->group(function () {
    Route::get('/', [VehicleController::class, 'index'])->name('index');
    Route::get('/{vehicle:slug}', [VehicleController::class, 'show'])->name('show');
});

Route::prefix('divertissement')->name('divertissement.')->group(function () {
    Route::get('/', [EventController::class, 'index'])->name('index');

    // Parcs d'attractions, parcs aquatiques, zoos, sites naturels du Congo
    // (anciennement rubrique "Loisirs") - avant le joker {event:slug}.
    Route::get('/parcs/{attractionPark:slug}', [AttractionParkController::class, 'show'])->name('parks.show');
    Route::middleware('auth')->post('/parcs/{attractionPark:slug}/reserver', [AttractionParkController::class, 'book'])->name('parks.book');

    Route::get('/{event:slug}', [EventController::class, 'show'])->name('show');
});

// TOURISME = rubrique Divertissement renommee (adresses /divertissement conservees).
Route::get('/tourisme', fn (\Illuminate\Http\Request $r) => redirect('/divertissement'.($r->getQueryString() ? '?'.$r->getQueryString() : '')))->name('tourisme');

// Anciennes adresses "Loisirs" : redirigees vers Tourisme > Parcs.
Route::prefix('loisirs')->name('loisirs.')->group(function () {
    Route::get('/', fn () => redirect('/divertissement?tab=parcs', 301))->name('index');
    Route::get('/{slug}', fn (string $slug) => redirect('/divertissement/parcs/'.$slug, 301))->name('show');
    Route::middleware('auth')->post('/{attractionPark:slug}/reserver', [AttractionParkController::class, 'book'])->name('book');
});

// --- Fiche d'une societe partenaire (hotel, compagnie, taxi, restaurant...),
// listee dans la rubrique de son activite (Logement, Voyage, Voiture...) ---
Route::get('/partenaires/{business:slug}', [BusinessController::class, 'show'])->name('partners.show');

// --- MARKETPLACE : petites annonces achat / vente (Leboncoin, Facebook Marketplace) ---
Route::prefix('marketplace')->name('marketplace.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');

    // Avant le joker {product:slug}.
    Route::middleware('auth')->group(function () {
        Route::get('/deposer', [ProductController::class, 'create'])->name('create');
        Route::post('/deposer', [ProductController::class, 'store'])->middleware('throttle:10,1')->name('store');
        Route::get('/mes-annonces', [ProductController::class, 'mine'])->name('mine');
        Route::get('/annonce/{product}/modifier', [ProductController::class, 'edit'])->name('edit');
        Route::put('/annonce/{product}', [ProductController::class, 'update'])->name('update');
        Route::post('/annonce/{product}/vendu', [ProductController::class, 'markSold'])->name('sold');
        Route::delete('/annonce/{product}', [ProductController::class, 'destroy'])->name('destroy');
    });

    Route::get('/{product:slug}', [ProductController::class, 'show'])->name('show');
});

Route::prefix('fret')->name('fret.')->group(function () {
    Route::get('/', [FreightOfferController::class, 'index'])->name('index');

    // Public, no-login tracking lookup by code (DHL-style) - must be
    // registered before the {freightOffer:slug} wildcard below so "suivi"
    // isn't parsed as a slug.
    Route::get('/suivi', [ShipmentController::class, 'trackPublic'])->name('track');
    Route::get('/suivi/{code}', [ShipmentController::class, 'trackPublic'])->name('track.show');

    // Customer's own shipment tracking - must be registered before the
    // {freightOffer:slug} wildcard below so "mes-envois" isn't parsed as a
    // slug.
    Route::middleware('auth')->prefix('mes-envois')->name('shipments.')->group(function () {
        Route::get('/', [ShipmentController::class, 'index'])->name('index');
        Route::get('/{shipment}', [ShipmentController::class, 'show'])->name('show');
    });

    Route::get('/{freightOffer:slug}', [FreightOfferController::class, 'show'])->name('show');

    Route::middleware('auth')->post('/{freightOffer:slug}/demande', [FretBookingController::class, 'store'])->name('book');
});

// --- Shared checkout (Mobile Money multi-passerelles), any module ---
Route::middleware('auth')->group(function () {
    Route::get('/checkout/{booking}', [CheckoutController::class, 'show'])->name('checkout.show');
    // Mobile Money multi-passerelles (Peex, pawaPay, MaishaPay, Digitwace) :
    // plus de verification Peex prealable obligatoire, chaque passerelle
    // valide le numero elle-meme.
    Route::post('/checkout/{booking}/payer', [CheckoutController::class, 'pay'])->name('checkout.pay');
    Route::get('/checkout/{booking}/statut', [CheckoutController::class, 'status'])->name('checkout.status');
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

    // Billetterie aerienne : dossiers, tarifs negocies, fournisseurs.
    Route::get('/vols', [AdminFlightBookingController::class, 'index'])->name('vols.index');
    Route::get('/vols/tarifs', [AdminFlightFareController::class, 'index'])->name('vols.fares.index');
    Route::get('/vols/tarifs/creer', [AdminFlightFareController::class, 'create'])->name('vols.fares.create');
    Route::post('/vols/tarifs', [AdminFlightFareController::class, 'store'])->name('vols.fares.store');
    Route::get('/vols/tarifs/{fare}/editer', [AdminFlightFareController::class, 'edit'])->name('vols.fares.edit');
    Route::put('/vols/tarifs/{fare}', [AdminFlightFareController::class, 'update'])->name('vols.fares.update');
    Route::delete('/vols/tarifs/{fare}', [AdminFlightFareController::class, 'destroy'])->name('vols.fares.destroy');
    Route::post('/vols/fournisseurs/{supplier}/basculer', [AdminFlightFareController::class, 'toggleSupplier'])->name('vols.suppliers.toggle');
    Route::put('/vols/apres-vente/{afterSale}', [AdminFlightBookingController::class, 'updateAfterSale'])->name('vols.after-sale.update');
    Route::get('/vols/apres-vente/{afterSale}/piece/{index}', [AdminFlightBookingController::class, 'attachment'])->whereNumber('index')->name('vols.after-sale.attachment');
    Route::get('/vols/{flightBooking}', [AdminFlightBookingController::class, 'show'])->name('vols.show');
    Route::post('/vols/{flightBooking}/traiter', [AdminFlightBookingController::class, 'process'])->name('vols.process');
    Route::post('/vols/{flightBooking}/verifier', [AdminFlightBookingController::class, 'reconcile'])->name('vols.reconcile');
    Route::post('/vols/{flightBooking}/pnr', [AdminFlightBookingController::class, 'recordPnr'])->name('vols.pnr');
    Route::post('/vols/{flightBooking}/billets', [AdminFlightBookingController::class, 'recordTickets'])->name('vols.tickets');
    Route::post('/vols/{flightBooking}/echec', [AdminFlightBookingController::class, 'markFailed'])->name('vols.fail');
    Route::post('/vols/{flightBooking}/annuler', [AdminFlightBookingController::class, 'cancel'])->name('vols.cancel');
    Route::post('/vols/{flightBooking}/remboursement', [AdminFlightBookingController::class, 'startRefund'])->name('vols.refund.start');
    Route::post('/vols/{flightBooking}/rembourse', [AdminFlightBookingController::class, 'confirmRefund'])->name('vols.refund.confirm');
    Route::post('/vols/{flightBooking}/alerte-levee', [AdminFlightBookingController::class, 'resolveAttention'])->name('vols.resolve');

    // Parcs d'attractions (rubrique Divertissement).
    Route::get('/loisirs', [AdminAttractionParkController::class, 'index'])->name('loisirs.index');
    Route::get('/loisirs/creer', [AdminAttractionParkController::class, 'create'])->name('loisirs.create');
    Route::post('/loisirs', [AdminAttractionParkController::class, 'store'])->name('loisirs.store');
    Route::get('/loisirs/{park}/editer', [AdminAttractionParkController::class, 'edit'])->name('loisirs.edit');
    Route::put('/loisirs/{park}', [AdminAttractionParkController::class, 'update'])->name('loisirs.update');
    Route::delete('/loisirs/{park}', [AdminAttractionParkController::class, 'destroy'])->name('loisirs.destroy');

    Route::get('/partenaires', [AdminBusinessController::class, 'index'])->name('partners.index');
    Route::get('/partenaires/creer', [AdminBusinessController::class, 'create'])->name('partners.create');
    Route::post('/partenaires', [AdminBusinessController::class, 'store'])->name('partners.store');
    Route::get('/partenaires/{business}/editer', [AdminBusinessController::class, 'edit'])->name('partners.edit');
    Route::put('/partenaires/{business}', [AdminBusinessController::class, 'update'])->name('partners.update');
    Route::delete('/partenaires/{business}', [AdminBusinessController::class, 'destroy'])->name('partners.destroy');

    Route::get('/reservations', [AdminBookingController::class, 'index'])->name('bookings.index');

    Route::get('/paiements', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::post('/paiements/{payment}/rembourser', [AdminPaymentController::class, 'refund'])->name('payments.refund');
    Route::post('/paiements/{payment}/verifier', [AdminPaymentController::class, 'refresh'])->name('payments.refresh');
    Route::post('/paiements/{payment}/confirmer', [AdminPaymentController::class, 'markPaid'])->name('payments.mark-paid');
    Route::post('/paiements/{payment}/echec', [AdminPaymentController::class, 'markFailed'])->name('payments.mark-failed');

    // Decaissements Mobile Money (PAY-OUT) et reglages des passerelles.
    Route::get('/decaissements', [AdminPayoutController::class, 'index'])->name('payouts.index');
    Route::post('/decaissements', [AdminPayoutController::class, 'store'])->name('payouts.store');
    Route::post('/decaissements/{payout}/verifier', [AdminPayoutController::class, 'refresh'])->name('payouts.refresh');
    Route::get('/passerelles', [AdminGatewayController::class, 'index'])->name('gateways.index');
    Route::put('/passerelles/{gateway}', [AdminGatewayController::class, 'update'])->name('gateways.update');

    Route::get('/moderation', [AdminModerationController::class, 'index'])->name('moderation.index');
    Route::post('/moderation/{module}/{id}/approuver', [AdminModerationController::class, 'approve'])->name('moderation.approve');
    Route::post('/moderation/{module}/{id}/rejeter', [AdminModerationController::class, 'reject'])->name('moderation.reject');

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

    Route::get('/fret', [PartnerFreightOfferController::class, 'index'])->name('fret.index');
    Route::get('/fret/creer', [PartnerFreightOfferController::class, 'create'])->name('fret.create');
    Route::post('/fret', [PartnerFreightOfferController::class, 'store'])->name('fret.store');
    Route::get('/fret/{offer}/editer', [PartnerFreightOfferController::class, 'edit'])->name('fret.edit');
    Route::put('/fret/{offer}', [PartnerFreightOfferController::class, 'update'])->name('fret.update');
    Route::delete('/fret/{offer}', [PartnerFreightOfferController::class, 'destroy'])->name('fret.destroy');
});

// --- Auth (Breeze-style, Inertia) ---
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // "Continuer avec Google / Facebook / Instagram" - session login,
    // see SocialLoginController docblock (web counterpart of
    // Api\SocialAuthController, which the mobile app uses instead).
    Route::get('/auth/{provider}/redirect', [SocialLoginController::class, 'redirect'])
        ->whereIn('provider', ['google', 'facebook', 'instagram'])
        ->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialLoginController::class, 'callback'])
        ->whereIn('provider', ['google', 'facebook', 'instagram'])
        ->name('social.callback');
});

Route::middleware('auth')->post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
