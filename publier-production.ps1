# Railway deploie le SOUS-DOSSIER backend-mokili/ du depot GitHub
# (D:\MOKILI-TOURS\backend-mokili\backend-mokili). Ce script publie les
# nouveautes MOKILI TOUR dans ce sous-dossier uniquement.
# Lancer depuis D:\MOKILI-TOURS\backend-mokili :
#   powershell -ExecutionPolicy Bypass -File .\publier-production.ps1
git add -A -- backend-mokili
if ($LASTEXITCODE -ne 0) { Write-Host "git add a echoue" -ForegroundColor Red; exit 1 }
Write-Host "`nFichiers qui vont partir sur GitHub :" -ForegroundColor Cyan
git status --short --cached
git commit -m "Production (backend-mokili/) : Loisirs & parcs, societes partenaires, paiements MaishaPay > Digitwace > Peex, ecrans d ouverture"
git push origin main
if ($LASTEXITCODE -ne 0) { Write-Host "`nPush interrompu : relancez 'git push origin main'." -ForegroundColor Yellow }
