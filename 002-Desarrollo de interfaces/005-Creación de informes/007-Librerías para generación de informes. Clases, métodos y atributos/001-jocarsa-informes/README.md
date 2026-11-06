# jocarsa | informes

Aplicación PHP 8.1+ / SQLite con panel jocarsa-iu y motor declarativo de informes JSON/SVG.

## Instalación

1. Copia la carpeta en Apache con PHP 8.1+ y extensiones `pdo_sqlite`, `sqlite3`, `mbstring` (en Ubuntu: `sudo apt install php-sqlite3 php-mbstring`).
2. Da permisos de escritura **solo** a `data/` para el usuario de PHP (por ejemplo `sudo chown -R www-data:www-data data && sudo chmod 750 data`).
3. **Importante**: bloquea el acceso web a `data/` también en la configuración del servidor (la carpeta contiene `.htaccess`, que requiere AllowOverride adecuado). Idealmente mueve la base de datos fuera de DocumentRoot y cambia la ruta en `inc/core.php`.
4. Abre `login.php`. En una base vacía se crea `jocarsa` / `jocarsa` con rol superadmin. **Cambia inmediatamente la contraseña desde «Mi contraseña» antes de publicar la aplicación.**
5. En «Cuentas», crea una cuenta; en «Usuarios», crea usuarios y asígnalos a esa cuenta; cada miembro podrá crear APIs para sus cuentas.
6. En «Mis APIs», crea una API con identificador URL único. Guarda la clave generada (solo se muestra una vez).
7. Invoca `api.php?ruta=identificador` con `POST`, `Content-Type: application/json`, cabecera `X-API-Key` y cuerpo del documento JSON. Devuelve HTML autónomo con CSS embebido.

## Prueba

```bash
curl -X POST 'http://localhost/ruta/jocarsa-informes/api.php?ruta=ventas-mensuales' \
  -H 'Content-Type: application/json' \
  -H 'X-API-Key: jca_TU_CLAVE' \
  --data-binary @ejemplos/ejemplo-completo.json \
  -o informe.html
```

## Paneles

- **Superadmin**: creación de cuentas y usuarios; consulta global de APIs y estadísticas.
- **Usuario/admin**: creación, activación/desactivación y consumo de APIs en cuentas asignadas; estadísticas limitadas a dichas cuentas.
- **Estadísticas**: solicitudes por API, éxitos, errores, latencia media, última llamada, tendencia de 30 días. Se registran llamadas autenticadas y fallos de clave en APIs existentes, pero **no** el contenido JSON ni las claves.

## Seguridad y limitaciones

- Claves API aleatorias de 256 bits, almacenadas solo como SHA-256. Contraseñas mediante `password_hash`. Sesiones con cookie HttpOnly y SameSite, formularios con CSRF, consultas parametrizadas y controles de pertenencia a cuenta.
- El sistema permite desactivar claves, pero **todavía no regenerarlas**. Crear otra API con slug nuevo para cambiar clave.
- Los errores de autenticación se contabilizan para APIs existentes; los slugs inexistentes no pueden asociarse a una API.
- Los administradores de cuenta actualmente comparten las mismas operaciones sobre APIs que los usuarios de esa cuenta. La gestión de usuarios está reservada al superadmin.
- Las estadísticas no guardan IP ni cuerpos de solicitudes.
- No incluye limitación de tasa, cuotas, colas ni exportación PDF. Antes de exponerlo a Internet configura HTTPS, rate limiting, copias de seguridad y protección de `data/`.
- El motor de documentos admite los componentes descritos en `ejemplos/DOCUMENTACION.md` y `ejemplos/GRAFICAS.md`. La estructura de clases está en `motor/src/`.
- El diseño reutiliza los recursos `jocarsa-iu` extraídos del informe de código proporcionado, con una hoja `assets/app.css` específica de la aplicación.
