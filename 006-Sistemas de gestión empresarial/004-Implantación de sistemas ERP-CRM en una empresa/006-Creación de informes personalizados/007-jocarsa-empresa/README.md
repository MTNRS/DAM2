# Adaptación de jocarsa-empresa a jocarsa-basededatos V2

Se entrega un **parche**, no un proyecto completo: el ERP aportado era un reporte Markdown, no un ZIP de fuentes. Copiar `backend/nucleo/JocarsaDB.php` y `backend/instalar.php` a sus rutas originales, haciendo copia de seguridad del adaptador.

## Ejecutar

```bash
php -m | grep -i jocarsabbdd
export CRIMSON_ADMIN_PASSWORD='UnaClaveLargaYUnicaDe12OMas'
php backend/instalar.php --usuario=admin --nombre='Administrador' --email=admin@example.com
unset CRIMSON_ADMIN_PASSWORD
```

El instalador es solo CLI, idempotente en las tablas, no borra datos y no sobrescribe usuarios. Crea las tablas de sistema y auxiliares, configuración predeterminada, administrador si no existe ninguno, y verifica CRUD. **No importa automáticamente tablas de negocio**: el modelo comercial debe aportarse/seleccionarse explícitamente; no se deben inventar tablas empresariales. Los módulos actuales proceden de colecciones del motor.

## Límites y precauciones

- No se ha ejecutado contra un servidor con extensión V2 instalada; el `php -l` solo valida sintaxis.
- La enumeración de tipos del adaptador se ha ampliado; el conector PHP V2 aún puede rechazar ciertos tipos. Se conservan nombres de tipo, pero no se transfieren parámetros como `DECIMAL(12,2)` porque la API del conector expone tipos simples.
- El adaptador sigue convirtiendo valores a cadenas; hay que probar tipos binarios, JSON, fechas y nulos en el motor real.
- La tabla `crimson_v2_test` se crea y borra durante la prueba; si ya existe, el instalador se detiene sin tocarla.
- El motor V2 adjunto declara pendientes pruebas de concurrencia, recuperación y del conector PHP. No está certificado para producción.
- Revisar también que la extensión esté cargada en Apache (no basta con PHP CLI).
