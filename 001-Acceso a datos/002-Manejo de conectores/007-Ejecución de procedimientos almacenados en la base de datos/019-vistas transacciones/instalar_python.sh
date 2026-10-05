#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"
./compilar_python.sh
PYTHON="${PYTHON:-python3}"
EXT="$($PYTHON-config --extension-suffix)"
SITE="$($PYTHON -c "import sysconfig; print(sysconfig.get_paths()['platlib'])")"
echo "[JocarsaBBDD] Instalando conector en: $SITE"
sudo install -Dm755 "jocarsabbdd${EXT}" "$SITE/jocarsabbdd${EXT}"
$PYTHON -c 'import jocarsabbdd; print("Conector Python importado correctamente")'
