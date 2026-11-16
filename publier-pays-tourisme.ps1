# Publie la page Tourisme "Choisissez un pays" (avant la page des sorties).
# Le site en ligne (Railway) est construit depuis le sous-dossier backend-mokili/.
# Lancer depuis D:\MOKILI-TOURS\backend-mokili :
#   powershell -ExecutionPolicy Bypass -File .\publier-pays-tourisme.ps1

$fichiers = @(
  'backend-mokili/app/Support/TourismeCountries.php',
  'backend-mokili/app/Http/Controllers/Tourisme/CountryController.php',
  'backend-mokili/resources/js/Pages/Tourisme/Pays.vue',
  'backend-mokili/resources/js/Pages/Tourisme/Monde.vue',
  'backend-mokili/resources/js/Pages/Divertissement/Index.vue',
  'backend-mokili/resources/js/Pages/Home.vue',
  'backend-mokili/resources/js/Layouts/MainLayout.vue',
  'backend-mokili/routes/web.php',
  'backend-mokili/routes/api.php',
  'backend-mokili/tests/Feature/TourismeCountryTest.php',
  'publier-pays-tourisme.ps1'
)

Write-Host "Fichiers modifies :" -ForegroundColor Cyan
git status --short -- $fichiers
git add -A -- $fichiers
if ($LASTEXITCODE -ne 0) { Write-Host "git add a echoue" -ForegroundColor Red; exit 1 }

git commit -m "Tourisme : page de choix du pays avant les sorties, page par pays (villes et sites)"
if ($LASTEXITCODE -ne 0) { Write-Host "Rien a publier (deja envoye ?)" -ForegroundColor Yellow }

git push origin main
if ($LASTEXITCODE -ne 0) {
  Write-Host "`nLe push a echoue (identification GitHub)." -ForegroundColor Red
  Write-Host "VS Code : icone Comptes (en bas a gauche) > Se connecter avec GitHub, puis relancez ce script." -ForegroundColor Yellow
} else {
  Write-Host "`nPublie. Railway redeploie automatiquement." -ForegroundColor Green
}
