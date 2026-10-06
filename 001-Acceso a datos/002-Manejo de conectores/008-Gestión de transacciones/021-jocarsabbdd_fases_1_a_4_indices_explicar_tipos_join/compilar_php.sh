#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"
CXX="${CXX:-g++}"
PHP_CONFIG="${PHP_CONFIG:-php-config}"
if ! command -v "$PHP_CONFIG" >/dev/null 2>&1; then
  echo "ERROR: php-config no está instalado."
  echo "Ubuntu/Debian: sudo apt install php-dev"
  exit 1
fi
INCLUDES="$($PHP_CONFIG --includes)"
EXT_DIR="$($PHP_CONFIG --extension-dir)"
echo "[JocarsaBBDD] Compilando extensión PHP"
echo "[JocarsaBBDD] PHP extension-dir: $EXT_DIR"
$CXX -std=c++20 -O2 -Wall -Wextra -pedantic -fPIC -shared \
  -DCOMPILE_DL_JOCARSABBDD=1 $INCLUDES \
  conector_php.cpp JocarsaBBDD.cpp -o jocarsabbdd.so
echo "[JocarsaBBDD] Generado: $(pwd)/jocarsabbdd.so"
echo "Prueba: php -d extension=$(pwd)/jocarsabbdd.so doc/php/01_basico.php"
