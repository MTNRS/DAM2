$ErrorActionPreference = 'Stop'

$repositoryRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location -LiteralPath $repositoryRoot

if (git status --porcelain) {
    throw 'Hay cambios locales sin confirmar. Confírmalos o guárdalos antes de actualizar.'
}

git fetch upstream --prune
if ($LASTEXITCODE -ne 0) {
    throw 'No se pudo descargar el repositorio del profesor.'
}

git switch main
if ($LASTEXITCODE -ne 0) {
    throw 'No se pudo cambiar a la rama main.'
}

git merge --no-edit upstream/main
if ($LASTEXITCODE -ne 0) {
    throw 'La actualización produjo conflictos. Resuélvelos antes de continuar.'
}

git push origin main
if ($LASTEXITCODE -ne 0) {
    throw 'La actualización local se completó, pero no se pudo subir a origin.'
}

Write-Host 'DAM2 está actualizado con upstream/main.'
