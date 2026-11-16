# Publie l'API de la page pays Tourisme utilisee par l'app mobile
# (GET /api/tourisme/pays/{slug}?ville=...).
# Lancer depuis D:\MOKILI-TOURS\backend-mokili :
#   powershell -ExecutionPolicy Bypass -File .\publier-api-mobile-tourisme.ps1

$fichiers = @(
  'backend-mokili/app/Http/Controllers/Tourisme/CountryController.php',
  'backend-mokili/routes/api.php',
  'backend-mokili/tests/Feature/TourismeCountryTest.php',
  'backend-mokili/resources/js/Components/ServiceIcon.vue',
  'publier-api-mobile-tourisme.ps1'
)

Write-Host "Fichiers modifies :" -ForegroundColor Cyan
git status --short -- $fichiers
git add -A -- $fichiers
if ($LASTEXITCODE -ne 0) { Write-Host "git add a echoue" -ForegroundColor Red; exit 1 }

git commit -m "API mobile : page pays Tourisme (villes et sites) ; icone Voiture"
if ($LASTEXITCODE -ne 0) { Write-Host "Rien a publier (deja envoye ?)" -ForegroundColor Yellow }

git push origin main
if ($LASTEXITCODE -ne 0) {
  Write-Host "`nLe push a echoue (identification GitHub)." -ForegroundColor Red
  Write-Host "VS Code : icone Comptes (en bas a gauche) > Se connecter avec GitHub, puis relancez ce script." -ForegroundColor Yellow
} else {
  Write-Host "`nPublie. Railway redeploie automatiquement." -ForegroundColor Green
}
