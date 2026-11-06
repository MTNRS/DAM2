# Formulario de contacto → ERP oportunidades

- `index.php` crea un token CSRF y carga el formulario.
- `js/contacto.js` hace POST a `contacto/enviar`.
- `.htaccess` reescribe la ruta hacia `contacto-handler.php`, cuyo acceso directo está bloqueado.
- `config.php` contiene la URL y clave de oportunidades. Bloqueado por `.htaccess`.
- PHP envía JSON con `nombre y apellidos`, `empresa`, `tipodeproyecto`, `mensaje`.
- El campo `email` se valida en el navegador y PHP, pero **no se transmite ni almacena en el ERP** porque la captura muestra que no está compartido. Activa email en el endpoint para poder recogerlo.
- Requiere PHP con extensiones curl, mbstring y sesiones, Apache mod_rewrite y AllowOverride.
- Revisa el nombre exacto de campo `nombre y apellidos` y el formato esperado por tu endpoint: la captura muestra etiquetas, no el contrato JSON exacto. Si el backend espera otra clave o formulario en lugar de JSON, ajusta `$payload`/Content-Type.
- Seguridad: en producción mover config fuera de DocumentRoot, HTTPS, límites por IP en servidor y política de privacidad.
