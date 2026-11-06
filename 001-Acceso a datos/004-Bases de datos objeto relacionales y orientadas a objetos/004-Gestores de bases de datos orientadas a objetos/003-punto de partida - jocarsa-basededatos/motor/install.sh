#!/usr/bin/env bash
set -euo pipefail
sudo apt-get update
sudo apt-get install -y build-essential libjson-c-dev python3-dev php-dev
make
make python php
mkdir -p datos
printf 'Compilacion terminada. Ejecuta ./jocarsabbdd\n'
