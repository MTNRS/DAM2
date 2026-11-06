# Versión 14 — Plantillas HTML para impresión

En Personalizar → Acciones personalizadas → Imprimir ficha, elegir «HTML personalizado» en «Formato del documento» y escribir el código en el campo de plantilla.

- Admite HTML con cabeceras, tablas, imágenes y estilos en línea.
- Variables `{{campo}}` y `{{param.nombre}}` se sustituyen con valores escapados.
- Se mantiene el modo Texto para compatibilidad con acciones existentes.
- El HTML se sanea antes de insertarlo en la ventana de impresión: no se ejecutan scripts ni manejadores de eventos.
- La configuración sigue almacenándose en jocarsa-bbdd por la API de Crimson.

Limitación: esta versión admite estilos CSS en línea; no incluye un editor WYSIWYG ni una vista previa dentro del personalizador.
