# Cliente de prueba de jocarsa | informes

## Archivos
- `probar-api.php`: cliente HTTP PHP/cURL que llama al endpoint y muestra el HTML devuelto.
- `ejemplo-completo.json`: showcase basado en el JSON adjunto (12 páginas, 54 componentes).

## Uso
1. Copia los dos archivos juntos a una carpeta servida por PHP.
2. Edita `probar-api.php` y cambia `$ruta = 'ventas-mensuales'` por el identificador **exacto** de la API creada en el panel «Mis APIs».
3. Abre `probar-api.php` desde el navegador, por ejemplo `http://localhost/cliente-informes/probar-api.php`.
4. Si la respuesta es correcta, se mostrará el informe HTML. Si no, verás el código HTTP y la respuesta del servidor.

## Requisitos
`php-curl` y acceso desde PHP al endpoint local. La URL de la página de documentación `?tab=documentacion` no es el endpoint: el endpoint es `api.php?ruta=...` en el mismo directorio.

## Seguridad
El archivo contiene una clave de API privada. **No lo publiques en una carpeta accesible por terceros**. En producción mueve la clave a una variable de entorno y utiliza HTTPS. Si ya has expuesto la clave, rótala desde el panel.
