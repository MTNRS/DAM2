#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"
PYTHON="${PYTHON:-python3}"
CXX="${CXX:-g++}"
EXT="$($PYTHON-config --extension-suffix)"
INCLUDES="$($PYTHON-config --includes)"
echo "[JocarsaBBDD] Python: $($PYTHON --version)"
echo "[JocarsaBBDD] Compilando jocarsabbdd${EXT}"
$CXX -std=c++20 -O2 -Wall -Wextra -pedantic -fPIC -shared $INCLUDES conector_python.cpp JocarsaBBDD.cpp -o "jocarsabbdd${EXT}"
echo "[JocarsaBBDD] Generado: jocarsabbdd${EXT}"
