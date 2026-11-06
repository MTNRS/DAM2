# jocarsa | informes — Imagen y temas

- **Imagen:** `{"tipo":"imagen","src":"https://servidor/imagen.png","alt":"Descripción","pie":"Pie de imagen","ancho":640}`. También admite imágenes PNG/JPEG/GIF/WebP embebidas en base64. Se rechazan SVG y URLs javascript.
- **Temas:** cada subcarpeta `motor/temas/<slug>/` debe contener `tema.json` (`nombre`, `descripcion`) y `tema.css`. Descubrimiento automático al abrir Temas.
- **Asignación:** desde Mis APIs, crear con tema o cambiar el tema de una API existente. La respuesta HTML lleva CSS base y tema incrustados.
- **Migración:** al abrir el panel, la base de datos existente añade automáticamente `apis.tema` con valor `clasico`. No se pierden APIs ni usuarios.
- **Seguridad:** solo el administrador del servidor debe poder escribir CSS en motor/temas. No se permite subir CSS desde el navegador. Las imágenes remotas se cargarán en el navegador del receptor.
- **Despliegue:** sustituye archivos conservando `data/informes.sqlite` y tus claves. Necesitas `pdo_sqlite`.
