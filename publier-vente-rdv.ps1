# Publie Voiture > Vente : rendez-vous (voir, essayer, acheter), paiement sur place.
# Plus de reservation ni de paiement en ligne pour la vente de vehicules.
# Lancer depuis D:\MOKILI-TOURS\backend-mokili :
#   powershell -ExecutionPolicy Bypass -File .\publier-vente-rdv.ps1

$fichiers = @(
  'backend-mokili/database/migrations/2026_11_16_000002_add_vehicle_to_visit_requests.php',
  'backend-mokili/app/Models/Guide/VisitRequest.php',
  'backend-mokili/app/Http/Controllers/Voiture/SaleController.php',
  'backend-mokili/app/Http/Controllers/Voiture/VehicleController.php',
  'backend-mokili/app/Http/Controllers/Api/VehicleController.php',
  'backend-mokili/app/Http/Controllers/CheckoutController.php',
  'backend-mokili/app/Http/Controllers/Admin/VisitRequestController.php',
  'backend-mokili/app/Http/Controllers/Partner/BookingController.php',
  'backend-mokili/resources/js/Pages/Voiture/Show.vue',
  'backend-mokili/resources/js/Pages/Admin/Visites/Index.vue',
  'backend-mokili/resources/js/Pages/Partner/Bookings/Index.vue',
  'backend-mokili/routes/web.php',
  'backend-mokili/routes/api.php',
  'backend-mokili/tests/Feature/VehicleRentalTest.php',
  'publier-vente-rdv.ps1'
)

Write-Host "Fichiers modifies :" -ForegroundColor Cyan
git status --short -- $fichiers
git add -A -- $fichiers
if ($LASTEXITCODE -ne 0) { Write-Host "git add a echoue" -ForegroundColor Red; exit 1 }

git commit -m "Voiture vente : rendez-vous et paiement sur place, plus de paiement en ligne"
if ($LASTEXITCODE -ne 0) { Write-Host "Rien a publier (deja envoye ?)" -ForegroundColor Yellow }

git push origin main
if ($LASTEXITCODE -ne 0) {
  Write-Host "`nLe push a echoue (identification GitHub)." -ForegroundColor Red
  Write-Host "VS Code : icone Comptes (en bas a gauche) > Se connecter avec GitHub, puis relancez ce script." -ForegroundColor Yellow
} else {
  Write-Host "`nPublie. Railway redeploie automatiquement." -ForegroundColor Green
}
