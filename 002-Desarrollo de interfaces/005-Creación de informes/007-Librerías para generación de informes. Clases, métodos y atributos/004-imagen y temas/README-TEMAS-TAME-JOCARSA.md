# Temas TAME y JOCARSA — jocarsa | informes

Se añaden `motor/temas/tame/` y `motor/temas/jocarsa/`, cada uno con `tema.json` y `tema.css`. El descubrimiento automático ya está implementado en `inc/temas.php`; no es necesario modificar la base SQLite.

## Instalación rápida

Para una instalación existente, copia únicamente las dos carpetas de temas dentro de `motor/temas/` y actualiza `assets/app.css` si quieres sus miniaturas en la pantalla Temas. No sustituyas la base de datos SQLite existente. En `Mis APIs`, elige el nuevo tema para cada endpoint y guarda. La siguiente llamada a la API devuelve HTML con el CSS incrustado.

## JOCARSA

Basado en el manual de identidad corporativa 2026.1 aportado: índigo `#4B0082`, naranja `#FF7900`, negro `#111111`, blanco cálido `#F7F5F2`; Ubuntu para lectura. La tipografía corporativa JOCARSA es personalizada y no se incluye porque no se ha suministrado su archivo. Se utiliza Ubuntu en los títulos como alternativa legible.

## TAME

Interpretación inspirada en la comunicación pública de institutotame.com. La paleta azul/naranja y la combinación Montserrat/Open Sans son elecciones de diseño propuestas; **no se ha verificado que sean los valores exactos del CSS o las fuentes originales del sitio**. Se puede ajustar con una copia del CSS oficial o capturas detalladas.

## Dependencias de fuentes

Ambos temas usan `@import` de Google Fonts con alternativas del sistema; para uso totalmente offline o impresión reproducible, hospeda las fuentes bajo tus propias licencias y sustituye las reglas `@import` por `@font-face`. No se incluyen logos ni archivos de fuentes de terceros.
