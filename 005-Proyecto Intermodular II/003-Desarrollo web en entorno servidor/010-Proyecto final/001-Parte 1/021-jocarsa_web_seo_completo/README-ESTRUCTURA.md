# Organización

- `js/`: scripts del navegador, incluidas configuraciones de endpoints JSON locales.
- `css/`: hojas de estilo.
- `api/`: JSON públicos, algunos sincronizados desde PHP.
- `config.php` y `sync.php`: integración interna con ERP (bloqueados por .htaccess).
- `index.php`: entrada principal y sincronización.
- `admin.html`: interfaz demo.
- `SEO-AUDITORIA.md`: análisis y prioridades SEO.

Se mantienen las claves de prueba en config.php. La carpeta debe servirse mediante Apache/PHP con .htaccess habilitado. No publicar archivos de configuración en servidores que no ejecuten PHP.
