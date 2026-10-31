# jocarsa | ERP integrado

Integración inicial basada en los reportes de código suministrados.

## Arquitectura

- `frontend/`: interfaz jocarsa-iu-rediseñada, conectada a la API JSON de Crimson.
- `backend/`: código recuperado de Crimson, incluido `nucleo/JocarsaDB.php` para el motor jocarsa-bbdd externo.

## Despliegue

1. Descomprimir en un directorio servido por Apache con PHP.
2. Mantener instalada y configurada la dependencia PHP de jocarsa-bbdd según las instrucciones originales de Crimson.
3. Usar una instalación existente y compatible de Crimson/JocarsaBBDD; no se incluye ni se modifica la base de datos real.
4. Abrir `frontend/login.html` o `frontend/index.html`.
5. Comprobar `backend/auth.php?action=status` y `backend/api.php?bloque=estado`.

## Funcionalidad conectada

- Inicio y cierre de sesión, comprobación de usuario.
- Departamentos y tablas desde Crimson, sujetos a permisos.
- Listado de registros, búsqueda, creación, edición y eliminación.
- Selectores de claves foráneas mediante `fk_opciones` cuando el campo se detecta como FK.
- Conservación del código de administración y reporting del backend para su integración visual posterior.

## Limitaciones y comprobaciones pendientes

- Los dos insumos son reportes Markdown, no copias completas verificadas de los proyectos. Se reconstruyeron los archivos con bloques de código disponibles; puede haber recursos omitidos por el generador de reportes. No se ha realizado prueba de extremo a extremo con la instalación real de jocarsa-bbdd.
- Los controles especializados de la interfaz, las pantallas administrativas y el reporting de Crimson no están todavía conectados a la nueva experiencia visual; sus archivos originales se conservan cuando aparecen en los reportes.
- La detección de claves foráneas en formularios es heurística; confirmar contra el esquema real y corregir según los tipos de columna devueltos por Crimson.
- Verificar los selectores de apertura del drawer con el CSS real y comprobar los permisos en todas las operaciones.
- Revisar los archivos reconstruidos y dependencias antes de utilizar en producción. No exponer endpoints de diagnóstico ni errores internos públicamente.
