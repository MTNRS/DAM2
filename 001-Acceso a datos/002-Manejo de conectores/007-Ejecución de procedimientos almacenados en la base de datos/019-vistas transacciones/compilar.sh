#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"
echo "[JocarsaBBDD] Compilando proyecto completo"
make -j"$(nproc)"
