<?php

use App\Http\Controllers\Admin\AttractionParkController as AdminAttractionParkController;
use App\Http\Controllers\Admin\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\BusinessController as AdminBusinessController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FlightOrderController as AdminFlightOrderController;
use App\Http\Controllers\Admin\GatewayController as AdminGatewayController;
use App\Http\Controllers\Admin\ModerationController as AdminModerationController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PayoutController as AdminPayoutController;
use App\Http\Controllers\Admin\TravelOfferController as AdminTravelOfferController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
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
use App\Http\Controllers\Voyage\TravelOfferController;
use App\Http\Controllers\Vols\FlightController;
use App\Http\Controllers\Admin\FreightFinanceController as AdminFreightFinanceController;
use App\Http\Controllers\Admin\FreightOrderController as AdminFreightOrderController;
use App\Http\Controllers\Admin\FreightPartnerController as AdminFreightPartnerController;
use App\Http\Controllers\Fret\FreightDocumentController;
use App\Http\Controllers\Fret\OrderController as FretOrderController;
use App\Http\Controllers\Fret\QuoteController as FretQuoteController;
use App\Http\Controllers\Partner\FreightOrderController as PartnerFreightOrderController;
use App\Http\Controllers\Partner\FreightProfileController as PartnerFreightProfileController;
use App\Http\Controllers\Partner\FreightQuoteController as PartnerFreightQuoteController;
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

// --- VOLS : billetterie aerienne (recherche, dossier, paiement via checkout commun) ---
Route::prefix('vols')->name('vols.')->group(function () {
    Route::get('/', [FlightController::class, 'index'])->middleware('throttle:30,1')->name('index');

    Route::middleware('auth')->group(function () {
        Route::get('/mes-vols', [FlightController::class, 'mine'])->name('mine');
        Route::get('/dossier/{order:reference}', [FlightController::class, 'show'])->name('show');
        Route::post('/dossier/{order:reference}/annuler', [FlightController::class, 'cancel'])->name('cancel');
        Route::post('/dossier/{order:reference}/remboursement', [FlightController::class, 'refund'])->name('refund');
        Route::get('/offres/{offer}/passagers', [FlightController::class, 'passengers'])->name('passengers');
        Route::post('/offres/{offer}/commander', [FlightController::class, 'store'])->middleware('throttle:10,1')->name('order');
    });
});

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
    Route::get('/{event:slug}', [EventController::class, 'show'])->name('show');
});

// --- LOISIRS : parcs d'attractions, parcs aquatiques, zoos, sites naturels du Congo ---
Route::prefix('loisirs')->name('loisirs.')->group(function () {
    Route::get('/', [AttractionParkController::class, 'index'])->name('index');
    Route::get('/{attractionPark:slug}', [AttractionParkController::class, 'show'])->name('show');

    Route::middleware('auth')->post('/{attractionPark:slug}/reserver', [AttractionParkController::class, 'book'])->name('book');
});

// --- Fiche d'une societe partenaire (hotel, compagnie, taxi, restaurant...),
// listee dans la rubrique de son activite (Logement, Voyage, Voiture...) ---
Route::get('/partenaires/{business:slug}', [BusinessController::class, 'show'])->name('partners.show');

Route::prefix('marketplace')->name('marketplace.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
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

    // --- Marketplace : demandes de devis, commandes, reclamations, pieces (avant le wildcard {freightOffer:slug}) ---
    Route::middleware('auth')->group(function () {
        Route::get('/demandes', [FretQuoteController::class, 'index'])->name('requests.index');
        Route::get('/demandes/creer', [FretQuoteController::class, 'create'])->name('requests.create');
        Route::post('/demandes', [FretQuoteController::class, 'store'])->middleware('throttle:20,1')->name('requests.store');
        Route::get('/demandes/{quoteRequest:reference}', [FretQuoteController::class, 'show'])->name('requests.show');
        Route::post('/devis/{quote:reference}/accepter', [FretQuoteController::class, 'accept'])->name('quotes.accept');
        Route::post('/devis/{quote:reference}/refuser', [FretQuoteController::class, 'refuse'])->name('quotes.refuse');

        Route::get('/commandes', [FretOrderController::class, 'index'])->name('orders.index');
        Route::get('/commandes/{order:reference}', [FretOrderController::class, 'show'])->name('orders.show');
        Route::post('/commandes/{order:reference}/annuler', [FretOrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/commandes/{order:reference}/reclamation', [FretOrderController::class, 'claim'])->name('orders.claim');

        Route::post('/documents', [FreightDocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/{document}/telecharger', [FreightDocumentController::class, 'download'])->name('documents.download');
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

    // Vols : dossiers, anomalies, relance, remboursements (journal d'audit en lecture seule).
    Route::get('/vols', [AdminFlightOrderController::class, 'index'])->name('flights.index');
    Route::get('/vols/{order:reference}', [AdminFlightOrderController::class, 'show'])->name('flights.show');
    Route::post('/vols/{order:reference}/relancer', [AdminFlightOrderController::class, 'retry'])->name('flights.retry');
    Route::post('/vols/{order:reference}/remboursement', [AdminFlightOrderController::class, 'refund'])->name('flights.refund');
    Route::post('/vols/remboursements/{refund}/rembourser', [AdminFlightOrderController::class, 'markRefunded'])->name('flights.refunded');
    Route::post('/vols/remboursements/{refund}/rejeter', [AdminFlightOrderController::class, 'rejectRefund'])->name('flights.refund.reject');

    // Fret & Logistique : partenaires, commandes, reclamations, finance, indicateurs, audit.
    Route::get('/fret/partenaires', [AdminFreightPartnerController::class, 'index'])->name('fret.partners.index');
    Route::get('/fret/partenaires/{profile}', [AdminFreightPartnerController::class, 'show'])->name('fret.partners.show');
    Route::post('/fret/partenaires/{profile}/decision', [AdminFreightPartnerController::class, 'decide'])->name('fret.partners.decide');
    Route::get('/fret/commandes', [AdminFreightOrderController::class, 'index'])->name('fret.orders.index');
    Route::get('/fret/commandes/{order:reference}', [AdminFreightOrderController::class, 'show'])->name('fret.orders.show');
    Route::post('/fret/commandes/{order:reference}/annuler', [AdminFreightOrderController::class, 'cancel'])->name('fret.orders.cancel');
    Route::post('/fret/commandes/{order:reference}/rembourser', [AdminFreightOrderController::class, 'refund'])->name('fret.orders.refund');
    Route::post('/fret/commandes/{order:reference}/suivi', [AdminFreightOrderController::class, 'addEvent'])->name('fret.orders.event');
    Route::get('/fret/reclamations', [AdminFreightOrderController::class, 'claims'])->name('fret.claims.index');
    Route::get('/fret/reclamations/{claim}', [AdminFreightOrderController::class, 'claim'])->name('fret.claims.show');
    Route::post('/fret/reclamations/{claim}/traiter', [AdminFreightOrderController::class, 'resolveClaim'])->name('fret.claims.resolve');
    Route::get('/fret/finance', [AdminFreightFinanceController::class, 'index'])->name('fret.finance');
    Route::post('/fret/finance/reverser', [AdminFreightFinanceController::class, 'settle'])->name('fret.finance.settle');
    Route::get('/fret/indicateurs', [AdminFreightFinanceController::class, 'kpis'])->name('fret.kpis');
    Route::get('/fret/audit', [AdminFreightFinanceController::class, 'audit'])->name('fret.audit');
    Route::get('/fret/documents/{document}', [FreightDocumentController::class, 'download'])->name('fret.documents.download');

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

    // Fret & Logistique : candidature, devis, commandes, suivi, reclamations, finances (isolation stricte par partenaire).
    Route::get('/fret/profil', [PartnerFreightProfileController::class, 'show'])->name('fret.profile');
    Route::post('/fret/profil', [PartnerFreightProfileController::class, 'store'])->name('fret.profile.store');
    Route::get('/fret/demandes', [PartnerFreightQuoteController::class, 'index'])->name('fret.requests.index');
    Route::get('/fret/demandes/{quoteRequest:reference}', [PartnerFreightQuoteController::class, 'show'])->name('fret.requests.show');
    Route::post('/fret/demandes/{quoteRequest:reference}/devis', [PartnerFreightQuoteController::class, 'respond'])->name('fret.requests.respond');
    Route::get('/fret/commandes', [PartnerFreightOrderController::class, 'index'])->name('fret.orders.index');
    Route::get('/fret/commandes/{order:reference}', [PartnerFreightOrderController::class, 'show'])->name('fret.orders.show');
    Route::post('/fret/commandes/{order:reference}/confirmer', [PartnerFreightOrderController::class, 'confirm'])->name('fret.orders.confirm');
    Route::post('/fret/commandes/{order:reference}/suivi', [PartnerFreightOrderController::class, 'addEvent'])->name('fret.orders.event');
    Route::get('/fret/reclamations', [PartnerFreightOrderController::class, 'claimsIndex'])->name('fret.claims');
    Route::post('/fret/reclamations/{claim}/message', [PartnerFreightOrderController::class, 'claimMessage'])->name('fret.claims.message');
    Route::get('/fret/finances', [PartnerFreightOrderController::class, 'finances'])->name('fret.finances');

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
});

Route::middleware('auth')->post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
