# Publie sur GitHub UNIQUEMENT les changements MOKILI TOUR (Loisirs, societes
# partenaires dans chaque rubrique, paiements multi-passerelles, ecrans
# d'ouverture) - sans les fichiers Dossiers/Devis/Factures ni les
# suppressions du sous-dossier backend-mokili/.
# Lancer depuis D:\MOKILI-TOURS\backend-mokili :
#   powershell -ExecutionPolicy Bypass -File .\publier-mokili.ps1

# 1. Fichiers d'une version precedente devenus inutiles (module "Guide" separe)
Remove-Item -Recurse -Force -ErrorAction SilentlyContinue `
  resources/js/Pages/Guide, resources/js/Pages/Admin/Guide, `
  database/migrations/2026_11_02_000002_add_guide_service_and_seed_businesses.php

# 2. Ajout des fichiers MOKILI TOUR uniquement
$fichiers = @(
  '.env.example',
  'app/Http/Controllers/Admin/AttractionParkController.php',
  'app/Http/Controllers/Admin/BusinessController.php',
  'app/Http/Controllers/Admin/GatewayController.php',
  'app/Http/Controllers/Admin/PaymentController.php',
  'app/Http/Controllers/Admin/PayoutController.php',
  'app/Http/Controllers/Api/AttractionParkController.php',
  'app/Http/Controllers/Api/BookingController.php',
  'app/Http/Controllers/Api/BusinessController.php',
  'app/Http/Controllers/Api/PaymentController.php',
  'app/Http/Controllers/Api/PaymentWebhookController.php',
  'app/Http/Controllers/CheckoutController.php',
  'app/Http/Controllers/Guide',
  'app/Http/Controllers/Loisirs',
  'app/Http/Requests/PayBookingRequest.php',
  'app/Http/Requests/StoreParkBookingRequest.php',
  'app/Models/GatewaySetting.php',
  'app/Models/Guide',
  'app/Models/Loisirs',
  'app/Models/Payment.php',
  'app/Models/Payout.php',
  'app/Services/Loisirs',
  'app/Services/Payments',
  'config/payments.php',
  'database/migrations/2026_11_01_000001_create_attraction_parks_table.php',
  'database/migrations/2026_11_01_000002_add_loisirs_service_and_seed_parks.php',
  'database/migrations/2026_11_01_000010_add_multi_gateway_fields_to_payments_table.php',
  'database/migrations/2026_11_01_000011_create_payouts_table.php',
  'database/migrations/2026_11_01_000012_create_gateway_settings_table.php',
  'database/migrations/2026_11_02_000001_create_businesses_table.php',
  'database/migrations/2026_11_02_000002_seed_partner_businesses.php',
  'database/seeders/AttractionParkSeeder.php',
  'database/seeders/BusinessSeeder.php',
  'database/seeders/DatabaseSeeder.php',
  'database/seeders/ServiceCategorySeeder.php',
  'public/images/loisirs',
  'resources/js/Components',
  'resources/js/Layouts',
  'resources/js/Pages',
  'routes/api.php',
  'routes/web.php',
  'tailwind.config.js',
  'publier-mokili.ps1'
)
git add -A -- $fichiers
if ($LASTEXITCODE -ne 0) { Write-Host "git add a echoue" -ForegroundColor Red; exit 1 }

Write-Host "`nFichiers qui vont partir sur GitHub :" -ForegroundColor Cyan
git status --short --cached

git commit -m "Loisirs & parcs, societes partenaires, paiements (Congo: MaishaPay > Digitwace > Peex), ecrans d ouverture"
if ($LASTEXITCODE -ne 0) { Write-Host "Rien a commiter ou commit refuse." -ForegroundColor Yellow }

git push origin main
if ($LASTEXITCODE -ne 0) {
  Write-Host "`nLe push a echoue (identification GitHub)." -ForegroundColor Red
  Write-Host "Connectez-vous puis relancez 'git push origin main' :" -ForegroundColor Yellow
  Write-Host "  - VS Code : icone Comptes (en bas a gauche) > Se connecter avec GitHub" -ForegroundColor Yellow
  Write-Host "  - ou : git config --global credential.helper manager  puis git push (une fenetre de connexion s'ouvre)" -ForegroundColor Yellow
}
