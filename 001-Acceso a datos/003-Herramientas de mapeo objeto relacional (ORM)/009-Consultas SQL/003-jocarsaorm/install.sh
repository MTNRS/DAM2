#!/usr/bin/env bash
set -euo pipefail
sudo apt-get update
sudo apt-get install -y build-essential libsqlite3-dev nlohmann-json3-dev
make -j"$(nproc)"
sudo make install
sudo ldconfig
printf '\nInstalado. Prueba: jocarsaorm prueba.db crear\n'
