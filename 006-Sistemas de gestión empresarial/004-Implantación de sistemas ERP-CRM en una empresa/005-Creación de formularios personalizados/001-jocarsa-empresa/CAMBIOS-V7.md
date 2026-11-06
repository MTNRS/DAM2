# jocarsa-erp-integrado v7 — Personalización de vistas (fase 1)

Esta versión conserva el backend PHP, la interfaz jocarsa-iu y la integración jocarsa-bbdd de v6.

## Nuevas funciones
- Botón **Personalizar** por entidad: mostrar/ocultar/reordenar columnas, filtro declarativo y reglas de color por fila.
- Ordenar pulsando la cabecera de una columna.
- Estrellas de favoritos por registro.
- Guardar y recuperar vistas con nombre.
- Reglas de color con operadores conocidos: igual, distinto, contiene, mayor, menor, vacío y no vacío.
- Configuración independiente para cada tabla.

## Alcance de la persistencia
**Importante:** en esta primera fase las configuraciones y favoritos se guardan en localStorage del navegador (por origen y tabla), NO en jocarsa-bbdd. No son compartidos entre usuarios ni dispositivos. Se ha evitado modificar la base de datos y la API de producción sin conocer el esquema de configuración y permisos de cada instalación.

## Próxima fase
Migrar configuraciones a colecciones de jocarsa-bbdd con ámbitos global/rol/usuario, ACL y versionado; después motor de acciones seguras, campos virtuales, dashboards y plantillas de impresión.

## Instalación
Sustituir el proyecto v6 por el contenido del ZIP. Mantener copia de seguridad y recargar sin caché.
