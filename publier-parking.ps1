# Publie Voiture > Parking (parkings, gardiennage, lavage) - retires de Mecanique.
# Le site en ligne (Railway) est construit depuis le sous-dossier backend-mokili/.
# Lancer depuis D:\MOKILI-TOURS\backend-mokili :
#   powershell -ExecutionPolicy Bypass -File .\publier-parking.ps1

$fichiers = @(
  'backend-mokili/config/catalogue.php',
  'backend-mokili/config/partner_photos.php',
  'backend-mokili/app/Models/Guide/Business.php',
  'backend-mokili/app/Models/Voiture/Vehicle.php',
  'backend-mokili/app/Services/Voiture/GarageSearch.php',
  'backend-mokili/app/Http/Controllers/Voiture/VehicleController.php',
  'backend-mokili/app/Http/Controllers/Api/VehicleController.php',
  'backend-mokili/app/Http/Controllers/Api/BusinessController.php',
  'backend-mokili/app/Http/Controllers/Guide/BusinessController.php',
  'backend-mokili/app/Http/Controllers/Guide/VisitRequestController.php',
  'backend-mokili/database/migrations/2026_11_16_000001_add_voiture_parking.php',
  'backend-mokili/resources/js/Pages/Voiture/Index.vue',
  'backend-mokili/resources/js/Pages/Voiture/Show.vue',
  'backend-mokili/resources/js/Pages/Partners/Show.vue',
  'backend-mokili/routes/api.php',
  'backend-mokili/tests/Feature/VoitureParkingTest.php',
  'publier-parking.ps1'
)

Write-Host "Fichiers modifies :" -ForegroundColor Cyan
git status --short -- $fichiers
git add -A -- $fichiers
if ($LASTEXITCODE -ne 0) { Write-Host "git add a echoue" -ForegroundColor Red; exit 1 }

git commit -m "Voiture : onglet Parking (parking, gardiennage, lavage), retires de Mecanique"
if ($LASTEXITCODE -ne 0) { Write-Host "Rien a publier (deja envoye ?)" -ForegroundColor Yellow }

git push origin main
if ($LASTEXITCODE -ne 0) {
  Write-Host "`nLe push a echoue (identification GitHub)." -ForegroundColor Red
  Write-Host "VS Code : icone Comptes (en bas a gauche) > Se connecter avec GitHub, puis relancez ce script." -ForegroundColor Yellow
} else {
  Write-Host "`nPublie. Railway redeploie automatiquement." -ForegroundColor Green
}
