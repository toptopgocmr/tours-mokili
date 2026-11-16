# Publie les nouvelles icones des tuiles : Voiture, Fret, Tourisme (grande roue).
# Lancer depuis D:\MOKILI-TOURS\backend-mokili :
#   powershell -ExecutionPolicy Bypass -File .\publier-icones.ps1

$fichiers = @(
  'backend-mokili/resources/js/Components/ServiceIcon.vue',
  'publier-icones.ps1'
)

Write-Host "Fichiers modifies :" -ForegroundColor Cyan
git status --short -- $fichiers
git add -A -- $fichiers
if ($LASTEXITCODE -ne 0) { Write-Host "git add a echoue" -ForegroundColor Red; exit 1 }

git commit -m "Icones : voiture, camion de fret, grande roue pour Tourisme"
if ($LASTEXITCODE -ne 0) { Write-Host "Rien a publier (deja envoye ?)" -ForegroundColor Yellow }

git push origin main
if ($LASTEXITCODE -ne 0) {
  Write-Host "`nLe push a echoue (identification GitHub)." -ForegroundColor Red
  Write-Host "VS Code : icone Comptes (en bas a gauche) > Se connecter avec GitHub, puis relancez ce script." -ForegroundColor Yellow
} else {
  Write-Host "`nPublie. Railway redeploie automatiquement." -ForegroundColor Green
}
