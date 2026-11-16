# Publie Concessionnaires : prise de rendez-vous d achat (paiement sur place).
# Bouton Prendre rendez-vous sur la fiche de chaque concessionnaire / vendeur.
# Lancer depuis D:\MOKILI-TOURS\backend-mokili :
#   powershell -ExecutionPolicy Bypass -File .\publier-rdv-concessionnaires.ps1

$fichiers = @(
  'backend-mokili/config/catalogue.php',
  'backend-mokili/app/Services/Voiture/GarageSearch.php',
  'backend-mokili/app/Http/Controllers/Guide/VisitRequestController.php',
  'backend-mokili/resources/js/Pages/Partners/Show.vue',
  'backend-mokili/tests/Feature/VoitureParkingTest.php',
  'publier-rdv-concessionnaires.ps1'
)

Write-Host "Fichiers modifies :" -ForegroundColor Cyan
git status --short -- $fichiers
git add -A -- $fichiers
if ($LASTEXITCODE -ne 0) { Write-Host "git add a echoue" -ForegroundColor Red; exit 1 }

git commit -m "Concessionnaires : prise de rendez-vous d achat, paiement sur place"
if ($LASTEXITCODE -ne 0) { Write-Host "Rien a publier (deja envoye ?)" -ForegroundColor Yellow }

git push origin main
if ($LASTEXITCODE -ne 0) {
  Write-Host "`nLe push a echoue (identification GitHub)." -ForegroundColor Red
  Write-Host "VS Code : icone Comptes (en bas a gauche) > Se connecter avec GitHub, puis relancez ce script." -ForegroundColor Yellow
} else {
  Write-Host "`nPublie. Railway redeploie automatiquement." -ForegroundColor Green
}
