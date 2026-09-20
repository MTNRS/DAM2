# Reglas para los trabajos de DAM2

Estas instrucciones se aplican a cualquier trabajo solicitado para segundo de
DAM. Las instrucciones concretas del enunciado de cada tarea tienen prioridad.

## Un repositorio por trabajo

- Cada trabajo debe vivir en una carpeta nueva y en un repositorio de GitHub
  independiente.
- El repositorio será privado salvo que el usuario indique expresamente otra
  cosa.
- Antes de crearlo, se debe identificar la asignatura, la unidad didáctica, el
  Resultado de Aprendizaje (RA) y los criterios de evaluación aplicables.
- No se debe desarrollar una entrega dentro de `DAM2`. Este repositorio sirve
  como temario, referencia y registro de las normas.

## Ajuste al temario

- El proyecto debe responder al enunciado del RA correspondiente.
- Solo debe cubrir los criterios de evaluación trabajados mediante ejercicios
  prácticos en clase.
- La solución debe utilizar técnicas, estructura y dificultad coherentes con
  los ejemplos y ejercicios del profesor.
- La extensión debe ser proporcional a la unidad. No se añadirán frameworks,
  patrones, servicios ni funciones que el enunciado no necesite.
- Antes de programar, se deben revisar en `DAM2` los materiales y ejercicios de
  la unidad correspondiente.

## Estilo jocarsa

- Código sencillo, directo y didáctico.
- Nombres claros y estructura fácil de recorrer.
- Pasos visibles y comprensibles antes que abstracciones prematuras.
- Comentarios breves cuando expliquen una decisión o una parte no evidente.
- Interfaz y documentación sobrias, con el mismo nivel de complejidad que los
  ejemplos vistos en clase.
- Evitar arquitectura empresarial, capas innecesarias y mejoras ajenas al
  alcance de la unidad.

## Uso responsable de IA

- La IA puede explicar conceptos, revisar código, detectar errores, proponer
  alternativas y ayudar a documentar el proceso.
- El alumno debe comprender y poder explicar todo el código entregado.
- Las decisiones funcionales y técnicas importantes deben quedar razonadas por
  el alumno.
- No se debe presentar una aplicación generada íntegramente por IA como trabajo
  propio ni ocultar su uso.
- Cada repositorio incluirá una sección `Uso de IA` en su README o informe con:
  qué ayuda se utilizó, qué decisiones tomó el alumno y qué partes revisó o
  modificó personalmente.
- Cuando se trabaje con un agente, se favorecerá un proceso didáctico: explicar
  cada bloque, mantener cambios pequeños y dejar trazabilidad en los commits.

## Informe de entrega

- La entrega final es un informe Markdown generado con la herramienta oficial
  `jocarsa | documentacion` (`jocarsa-documentacion.py`).
- Antes de generar el informe se comprobará la sintaxis exacta disponible en el
  entorno; no se inventarán parámetros.
- El informe debe mostrar la estructura y el contenido relevante del proyecto.
- Debe excluir dependencias, entornos, cachés y resultados generados, incluyendo
  como mínimo `node_modules`, `.venv`, `venv`, `__pycache__`, `vendor`, `dist`,
  `build`, `bin`, `obj`, `.git`, carpetas del IDE y equivalentes.
- La configuración `CARPETAS_EXCLUIDAS` de la herramienta debe incluir las
  exclusiones que correspondan a la tecnología empleada.
- El informe generado se guardará dentro del repositorio con un nombre claro,
  preferentemente `INFORME.md`.
- Si la herramienta oficial no está instalada ni disponible en los materiales,
  se dejará el proyecto preparado y se solicitará su ubicación antes de cerrar
  la entrega. No se sustituirá silenciosamente por otro generador.

## Comprobaciones antes de entregar

1. El proyecto funciona y puede ejecutarse siguiendo el README.
2. El alcance corresponde al RA y a los criterios trabajados en clase.
3. El alumno puede explicar el código y las decisiones tomadas.
4. No hay credenciales, datos personales, dependencias ni archivos generados en
   Git.
5. Las pruebas apropiadas para la unidad han sido ejecutadas.
6. `INFORME.md` ha sido generado con `jocarsa | documentacion` y revisado.
7. El README contiene la sección `Uso de IA`.
8. El repositorio independiente está actualizado y la entrega apunta al commit
   correcto.
