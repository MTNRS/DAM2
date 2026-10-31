#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$(readlink -f "$0")")"
if [[ "${EUID}" -ne 0 ]]; then echo "Ejecuta: sudo ./install-system.sh"; exit 1; fi
export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y build-essential make pkg-config libjson-c-dev python3-dev php-dev php-cli python3
make clean
make all python php
install -d -m 755 /usr/local/bin /usr/local/lib /usr/local/include /usr/local/share/jocarsaorm/php /etc/jocarsa /var/lib/jocarsa/basededatos
install -m 755 jocarsabbdd /usr/local/bin/jocarsabbdd
install -m 755 libjocarsaorm.so /usr/local/lib/libjocarsaorm.so
install -m 644 include/JocarsaBBDD.hpp include/JocarsaORM.hpp /usr/local/include/
install -m 644 php/JocarsaORM.php /usr/local/share/jocarsaorm/php/JocarsaORM.php
PYDIR="$(python3 - <<'PYTHON'
import sysconfig
print(sysconfig.get_path('platlib',scheme='posix_local'))
PYTHON
)"
install -d -m 755 "$PYDIR"
install -m 644 python/jocarsaorm.py "$PYDIR/jocarsaorm.py"
install -m 755 jocarsabbdd$(python3-config --extension-suffix) "$PYDIR/"
PHPEXT="$(php-config --extension-dir)"
install -d -m 755 "$PHPEXT"
install -m 755 jocarsabbdd.so "$PHPEXT/jocarsabbdd.so"
PHPVER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
install -d -m 755 "/etc/php/$PHPVER/mods-available"
printf 'extension=jocarsabbdd.so\n' > "/etc/php/$PHPVER/mods-available/jocarsabbdd.ini"
phpenmod -v "$PHPVER" jocarsabbdd
if [[ ! -f /etc/jocarsa/config.json ]]; then
  printf '{\n  "instalacion": "/var/lib/jocarsa/basededatos/",\n  "tamanoRegistro": 512\n}\n' > /etc/jocarsa/config.json
fi
chmod 755 /var/lib/jocarsa /var/lib/jocarsa/basededatos
ldconfig
if systemctl is-active --quiet apache2; then systemctl restart apache2; fi
php -m | grep -ix jocarsabbdd
python3 -c 'import jocarsabbdd, jocarsaorm; print("Python OK")'
echo 'Instalacion completada. Configura permisos de /var/lib/jocarsa/basededatos para el usuario de tus aplicaciones.'
