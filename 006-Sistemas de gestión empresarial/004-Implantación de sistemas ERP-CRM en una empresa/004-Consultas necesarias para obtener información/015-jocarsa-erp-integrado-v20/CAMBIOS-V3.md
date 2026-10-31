# Integración visual V3

- Menús de departamentos y entidades usan fichas, etiquetas, `.activo` y geometría de jocarsa-iu, sin crimson.
- Elemento seleccionado se extiende hacia la columna siguiente.
- Separadores arrastrables con Pointer Events y teclado, ancho persistente en localStorage.
- Botones de minimizar/expandir recuperados.
- Formularios de creación/edición usan `ju-card`, `ju-form-grid`, `ju-form-full`, `ju-btn`, y controles HTML nativos del sistema jocarsa-iu.
- Componentes v5 de jocarsa-iu incluidos en la página; el ERP no ejecuta la aplicación de demostración para no sustituir datos reales.
- API, autenticación y motor de persistencia conservados sin modificaciones.

## Instalación
Sustituir el directorio anterior por el nuevo, sin borrar la base de datos externa. Abrir `frontend/index.html`. Si el navegador conserva CSS anterior, recargar con Ctrl+Mayús+R.

## Limitaciones
Los módulos de IA y las vistas avanzadas originales necesitan sus propios servicios para ser funcionales; esta entrega no simula esos servicios.
