# Auditoría SEO — JOCARSA web ERP

## Hallazgos comprobados en el código

- **Presente:** `html lang="es"`, charset UTF-8, viewport responsive, título genérico, favicon, un H1, encabezados H2 por sección, etiquetas semánticas `header`, `main`, `section`, `footer`, textos alternativos en logos.
- **Ausente:** meta description, URL canonical, Open Graph (`og:title`, `og:description`, `og:image`, `og:url`), Twitter Card, datos estructurados JSON-LD, sitemap.xml, páginas individuales de productos y artículos, URLs específicas para contenido.
- **Título poco específico:** `jocarsa | soluciones digitales`; conviene incluir términos de búsqueda pertinentes.
- **Blog:** los artículos se crean con JavaScript desde JSON, sin URLs propias, metaetiquetas por artículo, marcado Article ni HTML renderizado en servidor.
- **Catálogo:** se crea dinámicamente desde JSON; sin fichas con URLs indexables ni datos estructurados SoftwareApplication.
- **Rendimiento:** vídeo de portada y numerosas imágenes externas; medir LCP/CLS y optimizar recursos.
- **Confianza:** aparecen marcas y testimonios ficticios claramente identificados; no publicarlos como reseñas reales ni como pruebas de clientes.
- **Administración:** admin.html es demo; conviene `noindex` y protegerlo si se hace operativo.
- **Contenido:** la página principal tiene bastante texto pero el contenido relevante del catálogo y blog depende de JavaScript.

## Prioridades recomendadas

1. Incorporar description, canonical y OG/Twitter cuando se confirme la URL de producción.
2. Crear páginas de artículo y producto con URLs propias y renderizado PHP en servidor.
3. Generar sitemap.xml con las URLs públicas indexables y configurar robots.txt con el dominio real.
4. Añadir JSON-LD Organization y WebSite con datos verificados; Article y SoftwareApplication en sus páginas específicas.
5. Medir Core Web Vitals y optimizar vídeo, imágenes y CSS.
6. Evitar indexación de páginas de administración y pruebas.

**Nota:** no se ha realizado rastreo en producción ni medición de posicionamiento. Las conclusiones se basan en el ZIP facilitado.
