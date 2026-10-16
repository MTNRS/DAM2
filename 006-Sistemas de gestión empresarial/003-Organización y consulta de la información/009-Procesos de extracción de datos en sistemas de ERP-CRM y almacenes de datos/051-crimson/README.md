Integración visual de Recursos del sistema en jocarsa | crimson

Copiar el contenido del ZIP a la raíz del proyecto respetando carpetas y haciendo antes copia de seguridad de index.html y nucleo/componentes/cabecera.html.

El monitor se abre dentro de <main id="aplicacion">, manteniendo la cabecera, las columnas laterales y el pie del ERP. No utiliza página independiente. Acceso reservado al administrador mediante api/auth_common.php.

Requiere /home/josevicente/log/registros.csv legible por Apache/PHP y acceso a CDN Chart.js. Los valores actuales corresponden al último registro CSV. Se muestran como máximo las últimas 1500 muestras. No se altera JocarsaBBDD.

Esta entrega contiene los archivos modificados o añadidos, no todo el ERP. Si hay cambios posteriores en index.html o cabecera.html, fusionarlos antes de sobrescribir.
