#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"
echo "[JocarsaBBDD] Compilando e instalando terminal + conector Python"
make -j"$(nproc)"
sudo make install
