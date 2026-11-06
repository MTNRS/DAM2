#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"
g++ -std=c++17 -O2 -Wall -Wextra -pedantic jocarsa_documental.cpp -o jocarsa-documental-bin $(pkg-config --cflags --libs sqlite3 json-c)
echo 'Compilado: ./jocarsa-documental-bin'
if [[ "${1:-}" == "--instalar" ]]; then
  install -Dm755 jocarsa-documental-bin /usr/local/bin/jocarsa-documental
  echo 'Instalado: /usr/local/bin/jocarsa-documental'
fi
