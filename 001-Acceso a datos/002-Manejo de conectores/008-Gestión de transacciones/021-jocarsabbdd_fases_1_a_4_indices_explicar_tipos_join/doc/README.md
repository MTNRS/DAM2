# Ejemplos de conectores JocarsaBBDD

La carpeta contiene ejemplos equivalentes para los conectores nativos de Python y PHP.

## Python

Compilar:

```bash
./compilar_python.sh
```

Ejecutar, usando el módulo generado en la raíz:

```bash
PYTHONPATH=. python3 doc/python/01_basico.py
PYTHONPATH=. python3 doc/python/02_transacciones_vistas.py
PYTHONPATH=. python3 doc/python/03_subconsultas.py
```

## PHP

En Ubuntu/Debian se necesitan las cabeceras de desarrollo de PHP:

```bash
sudo apt install php-dev
```

Compilar:

```bash
./compilar_php.sh
```

No es necesario instalar la extensión para probarla:

```bash
php -d extension=./jocarsabbdd.so doc/php/01_basico.php
php -d extension=./jocarsabbdd.so doc/php/02_transacciones_vistas.php
php -d extension=./jocarsabbdd.so doc/php/03_subconsultas.php
```

Instalación opcional del módulo:

```bash
./instalar_php.sh
```

Después de habilitar `extension=jocarsabbdd.so`, los scripts pueden ejecutarse simplemente con `php archivo.php`.

## Correspondencia básica

| Python | PHP |
|---|---|
| `jocarsabbdd.crear_base()` | `jocarsabbdd_crear_base()` |
| `jocarsabbdd.usar_base()` | `jocarsabbdd_usar_base()` |
| `jocarsabbdd.insertar()` | `jocarsabbdd_insertar()` |
| `jocarsabbdd.consultar()` | `jocarsabbdd_consultar()` |
| `jocarsabbdd.iniciar_transaccion()` | `jocarsabbdd_iniciar_transaccion()` |
| `jocarsabbdd.confirmar()` | `jocarsabbdd_confirmar()` |
| `jocarsabbdd.deshacer()` | `jocarsabbdd_deshacer()` |
| `jocarsabbdd.crear_vista()` | `jocarsabbdd_crear_vista()` |
