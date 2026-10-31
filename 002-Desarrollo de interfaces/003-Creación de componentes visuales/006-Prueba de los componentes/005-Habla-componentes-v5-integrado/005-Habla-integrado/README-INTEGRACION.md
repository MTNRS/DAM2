# Integración de componentes v5 en 005-Habla

Abrir `index.html` desde un servidor HTTP local. Los enlaces **Entradas v5** y **Reporting v5** abren pantallas con el patrón visual de 005-Habla.

Se mantienen los archivos originales de ambos proyectos; solo se añaden dos enlaces a `index.html` y reglas CSS de esos enlaces. La librería 013 está en `componentes-v5/`, con sus JS/CSS y su demo original. Las demos nuevas usan un iframe de mismo origen para aislar los selectores CSS globales y evitar que el inicializador de controles cambie formularios existentes de 005. Los controles mantienen sus inputs HTML y serialización FormData.

Iniciar: `python3 -m http.server 8000` y abrir `http://localhost:8000/`.

Archivos nuevos: `componentes-formulario.html`, `componentes-reporting.html`, `componentes-v5/demo-formulario.html`, `componentes-v5/demo-reporting.html`.

Nota: el archivo Markdown de origen es un reporte de código; no incluye binarios como `jocarsa.png`. La página de login puede necesitar esa imagen original. `ollama.php` requiere PHP y un servidor configurado.
