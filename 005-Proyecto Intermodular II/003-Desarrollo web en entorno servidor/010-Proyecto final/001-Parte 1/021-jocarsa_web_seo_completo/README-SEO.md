# Implementación SEO

- `index.php`: portada con meta description, canonical, Open Graph, Twitter y Organization JSON-LD.
- `productos.php`: catálogo HTML SSR y ficha individual por slug, SoftwareApplication JSON-LD.
- `blog.php`: listado HTML SSR y artículo individual por slug, BlogPosting JSON-LD.
- `includes/`: navegación, pie, metadatos, carga de datos y plantillas compartidas.
- `sitemap.php`: sitemap XML generado a partir de JSON sincronizados.
- `.htaccess`: URLs limpias, protección de PHP interno y DirectoryIndex.

## Antes de publicar

1. Verificar `AllowOverride` y `mod_rewrite` de Apache.
2. Confirmar que la sincronización obtiene el formato de productos y blog esperado; si falla, revisar `api/*.json`.
3. Configurar el dominio real, HTTPS, imágenes sociales públicas y robots.txt/Sitemap en producción.
4. Asegurar que los slugs son únicos. Actualmente se basan en el título; dos títulos iguales colisionan.
5. Revisar el contenido real de los artículos y añadir imagen, autor y fecha modificada si el ERP los ofrece.
6. Mover `config.php` fuera del DocumentRoot en producción.
7. Añadir `Sitemap: https://DOMINIO/sitemap.php` a robots.txt al desplegar.

**Nota:** El sitio principal conserva sus secciones dinámicas y los nuevos listados tienen HTML indexable sin depender de JavaScript.
