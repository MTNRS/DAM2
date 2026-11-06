# Checklist maestro de identidad corporativa — JOCARSA

Versión de trabajo: 2026.4

Este checklist sirve para dos objetivos: auditar el manual actual de identidad corporativa de JOCARSA y definir todo lo que debería contener una versión completa, coherente y aplicable a la web, aplicaciones, documentación, productos y comunicación comercial.

Utilizaré tres estados:

- Definido: existe una decisión de marca suficientemente clara.
- Pendiente: falta definir, documentar, verificar o normalizar el criterio.

Un elemento puede estar presente en la web y seguir pendiente de documentación formal. La distinción es importante: una práctica visual existente no equivale automáticamente a una norma corporativa.

## 1. Fundamentos de marca

- Nombre comercial: JOCARSA.
- Actividad principal: desarrollo de software y soluciones tecnológicas.
- Lema principal: «Tecnología útil. Resultados reales.»
- Orientación de marca: soluciones prácticas, funcionales y orientadas a resultados.
- Identidad vinculada a un ecosistema de productos tecnológicos.
- Redactar una misión corporativa oficial.
- Redactar una visión corporativa oficial.
- Establecer entre cuatro y seis valores de marca.
- Definir el propósito corporativo más allá de la actividad comercial.
- Identificar el público objetivo prioritario y sus segmentos.
- Definir la propuesta única de valor frente a competidores.
- Establecer la personalidad de marca mediante atributos concretos.
- Redactar una descripción corporativa corta, media y larga.
- Definir la historia de marca y su evolución.
- Establecer los principios que nunca deben vulnerarse en la comunicación.

## 2. Logotipo e identidad gráfica

- Existe una identidad gráfica corporativa JOCARSA.
- Existe un sistema de identificación de productos bajo la marca JOCARSA.
- Incorporar el logotipo maestro en formato vectorial.
- Definir las variantes horizontal, vertical y compacta.
- Definir versiones positivas, negativas y monocromáticas.
- Documentar la construcción geométrica del logotipo.
- Establecer el área mínima de seguridad.
- Establecer tamaños mínimos para impresión y pantalla.
- Definir el uso sobre fondos claros, oscuros, fotográficos y degradados.
- Documentar las proporciones que deben mantenerse.
- Definir usos incorrectos: deformación, rotación, sombras y recoloreado.
- Establecer la relación entre logotipo y lema.
- Normalizar el favicon, icono de aplicación y avatar corporativo.
- Preparar archivos oficiales SVG, PNG y PDF.

## 3. Paleta cromática

- Morado oscuro como color estructural de la identidad.
- Violeta como color secundario destacado.
- Naranja como acento de llamadas a la acción.
- Blanco y grises suaves como superficies de contenido.
- Uso de degradados morados en zonas de alto impacto.
- Extraer los colores exactos de las hojas CSS de producción.
- Definir códigos HEX, RGB, HSL y CMYK.
- Establecer colores primarios, secundarios y terciarios.
- Definir colores de fondo, superficie, texto y borde.
- Establecer proporciones recomendadas de uso cromático.
- Documentar combinaciones permitidas y prohibidas.
- Establecer variantes para modo claro y modo oscuro.
- Definir colores semánticos: éxito, error, advertencia e información.
- Verificar contrastes WCAG AA y, donde corresponda, AAA.
- Definir los degradados corporativos mediante valores reproducibles.
- Documentar los colores reservados para marcas de productos.

## 4. Geometría corporativa: chaflanes

Geometría con chaflanes

Geometría redondeada que se debe evitar

- Los chaflanes son un elemento distintivo de la identidad JOCARSA.
- Los cortes diagonales sustituyen a los redondeos como criterio general.
- Los chaflanes deben aplicarse coherentemente a componentes digitales.
- El manual debe mostrar ejemplos correctos e incorrectos.
- Definir un ángulo corporativo preferente para los cortes.
- Definir tamaños de chaflán según las dimensiones del componente.
- Determinar qué esquinas se cortan y con qué criterio.
- Establecer variantes para botones, tarjetas, imágenes y contenedores.
- Definir chaflanes para componentes pequeños.
- Definir el tratamiento de bordes, sombras y estados interactivos.
- Determinar las excepciones justificadas al sistema de chaflanes.
- Crear tokens CSS oficiales para la geometría.
- Documentar ejemplos mediante `clip-path` y otras técnicas CSS.
- Preparar componentes reutilizables para los proyectos JOCARSA.

## 5. Tipografía

- La web utiliza una jerarquía tipográfica con titulares destacados y textos compactos.
- Identificar y documentar las familias tipográficas exactas.
- Establecer tipografía principal y secundaria.
- Definir alternativas de sistema y fuentes de respaldo.
- Normalizar pesos tipográficos.
- Definir tamaños para H1, H2, H3, H4, H5, H6 y párrafos.
- Establecer interlineado y espaciado entre letras.
- Definir el uso de mayúsculas, minúsculas y versalitas.
- Definir ancho máximo de línea y longitud recomendada de párrafo.
- Establecer escalas tipográficas responsive.
- Documentar tipografía para tablas, formularios y código.
- Definir reglas de accesibilidad tipográfica.
- Verificar licencias y disponibilidad de las fuentes.

## 6. Sistema de composición y maquetación

- Composiciones modulares basadas en secciones.
- Uso de tarjetas para agrupar información.
- Alternancia de superficies claras y oscuras.
- Titulares amplios con elementos de acento.
- Organización de contenidos mediante retículas.
- Definir ancho máximo del contenido.
- Establecer márgenes horizontales y verticales.
- Normalizar el sistema de espaciado.
- Definir retículas de 4, 8 o 12 columnas según contexto.
- Documentar proporciones y alineaciones.
- Establecer breakpoints responsive.
- Definir composición de cabeceras, héroes y pies de página.
- Establecer criterios para densidad informativa.
- Definir jerarquías visuales y puntos focales.
- Documentar ejemplos de páginas completas.

## 7. Componentes de interfaz

- La web emplea botones de acción destacados.
- Se utilizan tarjetas para presentar servicios, soluciones y productos.
- Existen formularios de contacto.
- Se utilizan etiquetas, enlaces y elementos de navegación.
- Normalizar botones primarios, secundarios y terciarios.
- Definir estados hover, focus, active, disabled y loading.
- Normalizar inputs, selectores, áreas de texto y checkboxes.
- Definir menús, pestañas y navegación lateral.
- Documentar tablas, listas y paneles.
- Definir alertas, notificaciones y mensajes de error.
- Normalizar modales, desplegables y tooltips.
- Establecer patrones de navegación mediante teclado.
- Definir estados vacíos y pantallas de carga.
- Incorporar ejemplos accesibles de cada componente.
- Crear una biblioteca de componentes CSS reutilizables.
- Establecer una relación formal entre el manual corporativo y `jocarsa-iu`.

## 8. Iconografía e ilustración

- La web utiliza iconos para identificar capacidades y soluciones.
- Los productos pueden disponer de identificadores gráficos propios.
- Definir la familia oficial de iconos.
- Establecer grosor de trazo y tamaño.
- Definir iconos rellenos frente a iconos de contorno.
- Establecer colores permitidos.
- Definir el uso de iconos dentro de chaflanes.
- Normalizar ilustraciones técnicas y diagramas.
- Definir reglas para iconos de productos.
- Documentar cuándo utilizar iconos y cuándo utilizar texto.
- Crear un repositorio oficial de recursos gráficos.

## 9. Fotografía, vídeo y recursos audiovisuales

- La portada de la web utiliza una composición tecnológica oscura.
- Las capturas de productos forman parte del lenguaje visual.
- Definir el estilo fotográfico corporativo.
- Establecer criterios de iluminación, color y encuadre.
- Definir el tratamiento de capturas de software.
- Normalizar imágenes de portada y banners.
- Definir proporciones de imagen recomendadas.
- Establecer tratamiento de imágenes con superposición de texto.
- Documentar miniaturas de vídeos y demostraciones.
- Definir estilo de animaciones y transiciones.
- Establecer criterios de derechos de autor y licencias.
- Definir pautas para imágenes generadas con IA.

## 10. Arquitectura de marcas y productos

- JOCARSA funciona como marca corporativa principal.
- Existe un catálogo de soluciones y productos diferenciados.
- Los productos comparten una identidad de origen.
- Definir formalmente la nomenclatura `jocarsa | producto`.
- Establecer las reglas de capitalización y separación.
- Definir la relación visual entre marca madre y submarcas.
- Determinar cuándo un producto puede tener color propio.
- Establecer reglas de logotipos e iconos de producto.
- Definir la nomenclatura de versiones y ediciones.
- Normalizar portadas y fichas comerciales.
- Establecer criterios de denominación de nuevos productos.
- Definir el tratamiento de productos descontinuados.
- Crear una plantilla para incorporar nuevas marcas al ecosistema.

## 11. Identidad verbal y tono de comunicación

- Mensaje principal: «Tecnología útil. Resultados reales.»
- Comunicación centrada en soluciones y beneficios.
- Presentación de servicios, capacidades y productos mediante textos breves.
- Definir tono institucional y comercial.
- Establecer reglas de tratamiento: tú, vosotros, usted.
- Definir el nivel de tecnicismo según audiencia.
- Normalizar titulares y subtítulos comerciales.
- Establecer pautas para llamadas a la acción.
- Crear un glosario de términos corporativos.
- Definir términos recomendados y expresiones que deben evitarse.
- Establecer reglas para comunicación de IA, privacidad y automatización.
- Normalizar los textos de errores y notificaciones.
- Definir estilo de documentación técnica y manuales de usuario.
- Establecer normas de traducción y comunicación multilingüe.
- Crear ejemplos de comunicación correcta e incorrecta.

## 12. Identidad comercial y marketing

- Comunicación orientada a tecnología aplicada y resultados.
- Presentación de capacidades y soluciones.
- Existencia de contenidos de blog y testimonios en la web.
- Formulario de contacto comercial.
- Definir mensajes comerciales por segmento de cliente.
- Crear plantillas de folletos de producto.
- Definir estructura de fichas comerciales.
- Normalizar propuestas y presupuestos.
- Crear plantillas de presentaciones corporativas.
- Definir reglas de publicaciones para LinkedIn y otras redes.
- Establecer plantillas para artículos del blog.
- Definir estilo de campañas publicitarias.
- Normalizar banners y materiales promocionales.
- Definir reglas de uso de testimonios y casos de éxito.
- Establecer criterios para medir la consistencia de las campañas.

## 13. Documentación corporativa

- Crear plantilla de documento A4.
- Definir portada y contraportada.
- Normalizar cabeceras y pies de página.
- Establecer tipografía y márgenes para documentos largos.
- Crear plantilla de informe técnico.
- Crear plantilla de manual de usuario.
- Crear plantilla de propuesta comercial.
- Crear plantilla de presupuesto y factura.
- Definir firma corporativa de correo electrónico.
- Crear plantillas de certificados y diplomas.
- Establecer reglas para documentos PDF.
- Normalizar metadatos, nombres de archivo y versiones.

## 14. Accesibilidad y diseño inclusivo

- Establecer WCAG 2.2 AA como objetivo mínimo de accesibilidad digital.
- Verificar contraste de textos y componentes.
- Garantizar navegación completa mediante teclado.
- Definir estados de foco claramente visibles.
- Documentar escalado hasta el 200 % y reflujo de contenidos.
- Definir tamaños mínimos de objetivos interactivos.
- Establecer reglas de formularios accesibles.
- Definir alternativas textuales para imágenes.
- Normalizar subtítulos y transcripciones audiovisuales.
- Evitar transmitir información exclusivamente mediante color.
- Definir soporte para movimiento reducido.
- Incorporar pruebas de accesibilidad a la revisión de marca.

## 15. Aplicaciones y entornos de uso

- Aplicación principal en la web corporativa.
- Aplicación de la identidad en productos digitales.
- Definir versiones para aplicaciones de escritorio.
- Normalizar interfaces móviles.
- Definir iconos y pantallas de inicio.
- Crear plantillas para repositorios de código.
- Establecer identidad en documentación de API.
- Normalizar correos transaccionales.
- Definir identidad para vídeos y tutoriales.
- Crear plantillas de publicaciones sociales.
- Definir material impreso y merchandising.
- Establecer criterios de co-branding con clientes y colaboradores.

## 16. Gobierno y mantenimiento de marca

- Designar responsable del manual.
- Establecer un número de versión oficial.
- Definir un procedimiento para solicitar excepciones.
- Establecer criterios de aprobación de nuevos recursos.
- Mantener un historial de cambios.
- Crear un repositorio único de recursos oficiales.
- Definir un proceso de auditoría de aplicaciones y webs.
- Establecer una periodicidad de revisión.
- Documentar compatibilidad con versiones anteriores.
- Definir el procedimiento para incorporar nuevos productos.
- Crear una lista de verificación previa a publicación.
- Establecer responsables de actualización de los tokens y componentes.

## 17. Prioridades para completar el manual de JOCARSA

A partir de la identidad existente, propongo este orden de trabajo:

| Prioridad   | Elemento         | Objetivo                                        |
| ----------- | ---------------- | ----------------------------------------------- |
| 1 — Crítica | Chaflanes        | Definir geometría exacta y reglas de aplicación |
| 2 — Crítica | Colores          | Obtener valores oficiales del CSS               |
| 3 — Crítica | Tipografía       | Normalizar familias, pesos y tamaños            |
| 4 — Alta    | Logotipos        | Documentar variantes y usos                     |
| 5 — Alta    | Componentes UI   | Unificar el diseño de todas las aplicaciones    |
| 6 — Alta    | Productos        | Normalizar la identidad de las submarcas        |
| 7 — Alta    | Composición      | Definir retículas, espacios y proporciones      |
| 8 — Media   | Identidad verbal | Unificar mensajes y tono                        |
| 9 — Media   | Documentación    | Crear plantillas corporativas                   |
| 10 — Media  | Marketing        | Unificar publicaciones y materiales comerciales |

## 18. Criterio de finalización

El manual de JOCARSA debería considerarse completo cuando una persona que no haya participado en su diseño pueda utilizarlo para crear una nueva página web, aplicación, folleto o documento sin tener que improvisar decisiones de identidad corporativa.

Eso exige que cada norma importante incluya su definición, valores concretos, ejemplos visuales, usos correctos, usos incorrectos y, cuando corresponda, código CSS reutilizable.

La prioridad es convertir la identidad de JOCARSA en un sistema de diseño reproducible, no solamente en una colección de recomendaciones estéticas.