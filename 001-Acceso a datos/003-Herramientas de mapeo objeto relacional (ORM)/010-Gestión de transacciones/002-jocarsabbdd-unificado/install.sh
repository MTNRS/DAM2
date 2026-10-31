#!/usr/bin/env bash
set -Eeuo pipefail
cd "$(dirname "$(readlink -f "$0")")"
trap 'echo "ERROR en línea $LINENO: $BASH_COMMAND" >&2' ERR

if [[ $EUID -eq 0 ]]; then echo 'Ejecuta como usuario normal (el script usa sudo).'; exit 1; fi
for f in Makefile src/JocarsaBBDD.cpp src/conector_python.cpp src/conector_php.cpp python/jocarsaorm.py php/JocarsaORM.php config.json; do
  [[ -f "$f" ]] || { echo "Falta $f: ejecuta desde el proyecto unificado" >&2; exit 1; }
done

sudo apt-get update
sudo apt-get install -y build-essential make libjson-c-dev python3-dev php-dev

# -B obliga a compilar incluso si existen directorios python/ y php/.
make -B jocarsabbdd libjocarsaorm.so
make -B python
make -B php

PY_SUFFIX="$(python3-config --extension-suffix)"
PY_MODULE="jocarsabbdd${PY_SUFFIX}"
[[ -f "$PY_MODULE" ]] || { echo "No se generó $PY_MODULE" >&2; exit 1; }
[[ -f jocarsabbdd.so && -f libjocarsaorm.so ]] || { echo 'Faltan bibliotecas compiladas' >&2; exit 1; }

sudo install -m 755 jocarsabbdd /usr/local/bin/jocarsabbdd
sudo install -m 755 libjocarsaorm.so /usr/local/lib/libjocarsaorm.so
sudo ldconfig

# Python: instalar en dist-packages del Python del sistema Ubuntu.
PY_DEST="$(python3 - <<'PY'
import sysconfig
print(sysconfig.get_path('platlib', scheme='posix_local'))
PY
)"
sudo install -d "$PY_DEST"
sudo install -m 644 python/jocarsaorm.py "$PY_DEST/jocarsaorm.py"
sudo install -m 755 "$PY_MODULE" "$PY_DEST/$PY_MODULE"

# PHP: instalar extensión SQL, sin activarla globalmente sin permiso explícito.
PHP_EXT_DIR="$(php-config --extension-dir)"
sudo install -d "$PHP_EXT_DIR"
sudo install -m 755 jocarsabbdd.so "$PHP_EXT_DIR/jocarsabbdd.so"
sudo install -d /usr/local/share/jocarsaorm/php
sudo install -m 644 php/JocarsaORM.php /usr/local/share/jocarsaorm/php/JocarsaORM.php

# Configuración de ejemplo accesible desde cualquier directorio.
sudo install -d /etc/jocarsa
if [[ ! -f /etc/jocarsa/config.json ]]; then
  sudo install -m 644 config.json /etc/jocarsa/config.json
fi

printf '\n=== VERIFICACIONES ===\n'
command -v jocarsabbdd
ldconfig -p | grep libjocarsaorm || true
python3 -c 'import jocarsaorm; print("Python ORM: OK")'
python3 -c 'import jocarsabbdd; print("Python SQL: OK")'
php -d "extension=$PHP_EXT_DIR/jocarsabbdd.so" -r 'echo "PHP SQL: ", extension_loaded("jocarsabbdd") ? "OK\n" : "ERROR\n";'
php -l /usr/local/share/jocarsaorm/php/JocarsaORM.php
printf '\nInstalación terminada.\n'
printf 'PHP SQL: ejecutar con php -d extension=%s/jocarsabbdd.so script.php\n' "$PHP_EXT_DIR"
printf 'PHP ORM: requiere FFI habilitado; usa /usr/local/share/jocarsaorm/php/JocarsaORM.php\n'
printf 'Configuración: /etc/jocarsa/config.json (pásala explícitamente a JocarsaORM)\n'
