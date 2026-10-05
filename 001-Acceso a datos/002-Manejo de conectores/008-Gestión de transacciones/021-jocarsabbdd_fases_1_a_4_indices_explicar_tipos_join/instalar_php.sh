#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"
./compilar_php.sh
PHP_CONFIG="${PHP_CONFIG:-php-config}"
EXT_DIR="$($PHP_CONFIG --extension-dir)"
INI_DIR="$($PHP_CONFIG --ini-dir 2>/dev/null || true)"
sudo install -Dm755 jocarsabbdd.so "$EXT_DIR/jocarsabbdd.so"
echo "[JocarsaBBDD] Instalado en $EXT_DIR/jocarsabbdd.so"
echo "Actívalo añadiendo: extension=jocarsabbdd.so"
echo "Después comprueba: php -m | grep -i jocarsabbdd"
