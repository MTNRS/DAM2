# jocarsa | móvil — estética jocarsa-iu

Adaptación móvil de la identidad de jocarsa-iu a partir del proyecto de referencia facilitado.

- Variables HSL originales (`--tono`, `--saturacion`, `--brillo`, `--escalon`) y paleta derivada.
- Tipografía Ubuntu, chaflanes geométricos, tarjetas blancas, formularios y tablas coherentes.
- Cabecera compacta y navegación inferior con objetivos táctiles; sin columnas laterales redimensionables propias de escritorio.
- Carrusel de supertarjetas horizontal conservado, sin fundidos ni superposición.
- Vistas, templates y controladores sin caché de fetch; localStorage de productos intacto.

Servir con Apache o `python3 -m http.server 8000`. Abrir `http://localhost:8000/`.

No se han trasladado los modales ni drawers de escritorio a las vistas porque la app actual no los utiliza.
