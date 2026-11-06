# Integración monitor de recursos en Crimson

Copiar `recursos.php` a la raíz de Crimson y `nucleo/componentes/cabecera.html` a su ruta homónima, guardando copia de seguridad del archivo existente.

Acceso: iniciar sesión como administrador y pulsar «Recursos del sistema» en la cabecera.

El archivo CSV debe existir en `/home/josevicente/log/registros.csv` y ser legible por el usuario de PHP/Apache (`www-data` habitualmente). Comprobar permisos del archivo y de los directorios padre. La ruta coincide con el PHP facilitado; si el recolector escribe en `/home/josevicente/logs/registros.csv`, ajustar la variable `$archivo`.

Se requiere acceso del navegador a CDN de Chart.js. La información «actual» corresponde a la última muestra CSV, no a una lectura instantánea de psutil. No se realizan migraciones ni se modifica JocarsaBBDD.

Si el navegador conserva la cabecera anterior, limpiar caché o actualizar la versión del `data-include` en `index.html`.
