# Publie sur GitHub la mise a jour MOKILI TOUR du 2 novembre 2026 :
#  1. Billetterie aerienne (onglet Voyage > Vols) selon le cahier des charges
#  2. Parcs d'attractions ranges dans Divertissement (tuile Loisirs retiree)
#  3. Circuits par bus, bateau, train, voiture/4x4
# Le site en ligne (Railway) est construit depuis le sous-dossier backend-mokili/.
# Lancer depuis D:\MOKILI-TOURS\backend-mokili :
#   powershell -ExecutionPolicy Bypass -File .\publier-billetterie.ps1

# Ancienne page "Loisirs" (remplacee par Divertissement > Parcs)
Remove-Item -Force -ErrorAction SilentlyContinue backend-mokili/resources/js/Pages/Loisirs/Index.vue

$fichiers = @(
  'backend-mokili/app/Http/Controllers/Admin/TravelOfferController.php',
  'backend-mokili/app/Http/Controllers/Api/BookingController.php',
  'backend-mokili/app/Http/Controllers/Api/TravelOfferController.php',
  'backend-mokili/app/Http/Controllers/CheckoutController.php',
  'backend-mokili/app/Http/Controllers/Divertissement/EventController.php',
  'backend-mokili/app/Http/Controllers/Loisirs/AttractionParkController.php',
  'backend-mokili/app/Http/Controllers/Voyage/BookingController.php',
  'backend-mokili/app/Http/Controllers/Voyage/TravelOfferController.php',
  'backend-mokili/app/Models/Guide/Business.php',
  'backend-mokili/app/Models/Voyage/TravelOffer.php',
  'backend-mokili/app/Services/Payments/PaymentService.php',
  'backend-mokili/database/migrations/2026_01_02_000000_add_agent_role_to_users_table.php',
  'backend-mokili/database/seeders/ServiceCategorySeeder.php',
  'backend-mokili/resources/js/Components/GuideCover.vue',
  'backend-mokili/resources/js/Components/PartnersSection.vue',
  'backend-mokili/resources/js/Layouts/AdminLayout.vue',
  'backend-mokili/resources/js/Layouts/MainLayout.vue',
  'backend-mokili/resources/js/Pages/Admin/Voyage/Form.vue',
  'backend-mokili/resources/js/Pages/Admin/Voyage/Index.vue',
  'backend-mokili/resources/js/Pages/Checkout.vue',
  'backend-mokili/resources/js/Pages/Divertissement/Index.vue',
  'backend-mokili/resources/js/Pages/Home.vue',
  'backend-mokili/resources/js/Pages/Loisirs/Show.vue',
  'backend-mokili/resources/js/Pages/Voyage/Index.vue',
  'backend-mokili/resources/js/Pages/Voyage/Show.vue',
  'backend-mokili/routes/api.php',
  'backend-mokili/routes/console.php',
  'backend-mokili/routes/web.php',
  'backend-mokili/tailwind.config.js',
  'backend-mokili/app/Http/Controllers/Admin/FlightBookingController.php',
  'backend-mokili/app/Http/Controllers/Admin/FlightFareController.php',
  'backend-mokili/app/Http/Controllers/Api/FlightController.php',
  'backend-mokili/app/Http/Controllers/Voyage/FlightController.php',
  'backend-mokili/app/Http/Requests/FlightSearchRequest.php',
  'backend-mokili/app/Http/Requests/StoreFlightBookingRequest.php',
  'backend-mokili/app/Models/AuditLog.php',
  'backend-mokili/app/Models/Flights/Airport.php',
  'backend-mokili/app/Models/Flights/FlightAfterSale.php',
  'backend-mokili/app/Models/Flights/FlightBooking.php',
  'backend-mokili/app/Models/Flights/FlightFare.php',
  'backend-mokili/app/Models/Flights/FlightIdempotencyKey.php',
  'backend-mokili/app/Models/Flights/FlightOffer.php',
  'backend-mokili/app/Models/Flights/FlightPassenger.php',
  'backend-mokili/app/Models/Flights/FlightSearch.php',
  'backend-mokili/app/Models/Flights/FlightStatusHistory.php',
  'backend-mokili/app/Models/Flights/FlightSupplier.php',
  'backend-mokili/app/Models/Flights/FlightSupplierLog.php',
  'backend-mokili/app/Models/Flights/FlightTicket.php',
  'backend-mokili/app/Notifications/FlightOpsAlert.php',
  'backend-mokili/app/Notifications/FlightStatusChanged.php',
  'backend-mokili/app/Services/Flights/Contracts/FlightSupplier.php',
  'backend-mokili/app/Services/Flights/Data/OfferData.php',
  'backend-mokili/app/Services/Flights/Data/SearchCriteria.php',
  'backend-mokili/app/Services/Flights/Data/SupplierResult.php',
  'backend-mokili/app/Services/Flights/FlightBookingService.php',
  'backend-mokili/app/Services/Flights/FlightException.php',
  'backend-mokili/app/Services/Flights/FlightFulfillmentService.php',
  'backend-mokili/app/Services/Flights/FlightNotifier.php',
  'backend-mokili/app/Services/Flights/FlightPaymentHooks.php',
  'backend-mokili/app/Services/Flights/FlightPricing.php',
  'backend-mokili/app/Services/Flights/FlightSearchService.php',
  'backend-mokili/app/Services/Flights/FlightStateMachine.php',
  'backend-mokili/app/Services/Flights/FlightStatus.php',
  'backend-mokili/app/Services/Flights/FlightSupplierManager.php',
  'backend-mokili/app/Services/Flights/InvalidFlightTransition.php',
  'backend-mokili/app/Services/Flights/Suppliers/AbstractSupplier.php',
  'backend-mokili/app/Services/Flights/Suppliers/ManualFareSupplier.php',
  'backend-mokili/app/Services/Flights/Suppliers/SandboxSupplier.php',
  'backend-mokili/config/flights.php',
  'backend-mokili/database/migrations/2026_11_03_000001_create_flight_ticketing_tables.php',
  'backend-mokili/database/migrations/2026_11_03_000002_seed_airports_and_flight_suppliers.php',
  'backend-mokili/database/migrations/2026_11_03_000003_add_transport_mode_to_travel_offers.php',
  'backend-mokili/database/migrations/2026_11_03_000004_merge_loisirs_into_divertissement.php',
  'backend-mokili/phpunit.xml',
  'backend-mokili/resources/js/Components/AirportInput.vue',
  'backend-mokili/resources/js/Components/FlightOfferCard.vue',
  'backend-mokili/resources/js/Components/FlightSearchForm.vue',
  'backend-mokili/resources/js/Components/Paginator.vue',
  'backend-mokili/resources/js/Components/ParkCard.vue',
  'backend-mokili/resources/js/Pages/Admin/Vols/FareForm.vue',
  'backend-mokili/resources/js/Pages/Admin/Vols/Fares.vue',
  'backend-mokili/resources/js/Pages/Admin/Vols/Index.vue',
  'backend-mokili/resources/js/Pages/Admin/Vols/Show.vue',
  'backend-mokili/resources/js/Pages/Voyage/Vols/Book.vue',
  'backend-mokili/resources/js/Pages/Voyage/Vols/Document.vue',
  'backend-mokili/resources/js/Pages/Voyage/Vols/Dossier.vue',
  'backend-mokili/resources/js/Pages/Voyage/Vols/Dossiers.vue',
  'backend-mokili/resources/js/Pages/Voyage/Vols/Results.vue',
  'backend-mokili/resources/js/utils/flights.js',
  'backend-mokili/tests/Feature/DivertissementAndCircuitsTest.php',
  'backend-mokili/tests/Feature/FlightTicketingTest.php',
  'backend-mokili/tests/TestCase.php',
  'backend-mokili/resources/js/Pages/Loisirs/Index.vue',
  'publier-billetterie.ps1'
)

Write-Host "Fichiers envoyes :" -ForegroundColor Cyan
git status --short -- $fichiers
git add -A -- $fichiers
if ($LASTEXITCODE -ne 0) { Write-Host "git add a echoue" -ForegroundColor Red; exit 1 }

git commit -m "Billetterie aerienne (vols), parcs dans Divertissement, circuits bus/bateau/train"
if ($LASTEXITCODE -ne 0) { Write-Host "Rien a publier (deja envoye ?)" -ForegroundColor Yellow }

git push origin main
if ($LASTEXITCODE -ne 0) {
  Write-Host "`nLe push a echoue (identification GitHub)." -ForegroundColor Red
  Write-Host "VS Code : icone Comptes (en bas a gauche) > Se connecter avec GitHub, puis relancez ce script." -ForegroundColor Yellow
} else {
  Write-Host "`nPublie. Railway redeploie automatiquement (les migrations creent les tables de billetterie)." -ForegroundColor Green
}
