# Jocarsa BBDD — instalación global Ubuntu

Ejecuta `sudo ./install-system.sh` en Ubuntu 24.04. Recompila los conectores nativos para las versiones de Python y PHP del equipo, instala CLI, biblioteca, cabeceras y wrappers. **Haz copia de seguridad** antes de actualizar un motor ya instalado. El instalador no migra datos ni configura permisos de escritura de aplicaciones.

## Verificar

```bash
which jocarsabbdd
php -m | grep -i jocarsabbdd
python3 -c 'import jocarsabbdd, jocarsaorm; print("OK")'
ldconfig -p | grep libjocarsaorm
```

## API nativa

Python: `jocarsabbdd.crear_coleccion('marketing')`, `jocarsabbdd.listar_colecciones()`, `jocarsabbdd.agregar_tabla_coleccion('productos','marketing')` (seleccionar antes la base).

PHP: `jocarsabbdd_crear_coleccion('marketing')`, `jocarsabbdd_listar_colecciones()`, `jocarsabbdd_agregar_tabla_coleccion('productos','marketing')` (seleccionar antes la base).

El ORM Python y PHP conserva su API previa. PHP ORM utiliza FFI; activar FFI explícitamente según el contexto de ejecución si se necesita.

## Precauciones

- Las extensiones PHP/Python se compilan para las versiones locales; reinstala tras cambios de ABI.
- Los datos del motor y metadatos de colecciones se comparten; **no** abras escrituras simultáneas de múltiples procesos sin protección adicional.
- El motor original usa registros de tamaño fijo y el mecanismo de colecciones no garantiza rollback transaccional ni bloqueo entre procesos.
- No abras permisos `777` en el directorio de datos. Usa un grupo de sistema y ACL o permisos específicos.
- Si el sistema ya contiene `/etc/jocarsa/config.json`, se conserva intacto para no cambiar la ruta de datos existente.
