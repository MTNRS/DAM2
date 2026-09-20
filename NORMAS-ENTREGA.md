# Normas de entrega de trabajos

Cada unidad didáctica termina con un proyecto práctico que demuestra los
conocimientos adquiridos durante la unidad y sus subunidades.

## Requisitos del proyecto

- Cada trabajo tendrá su propia carpeta y su propio repositorio público.
- El proyecto respetará el Resultado de Aprendizaje correspondiente.
- Se alineará con los criterios de evaluación trabajados en las clases mediante
  ejercicios prácticos.
- Mantendrá relación con los desarrollos, proyectos y ejercicios realizados en
  clase.
- Su extensión será proporcional al trabajo desarrollado durante la unidad.
- Seguirá un estilo sencillo y didáctico, coherente con los ejemplos de jocarsa.

## Documentación

La entrega será un informe Markdown generado con la herramienta
`jocarsa | documentacion`. El informe incluirá el contenido relevante del
proyecto y excluirá dependencias y archivos generados, como:

- `node_modules`
- `.venv` y `venv`
- `__pycache__`
- `vendor`
- `dist` y `build`
- `bin` y `obj`
- `.git`

La lista se ampliará cuando la tecnología utilizada genere otras carpetas que
no formen parte del código de la entrega.

## Uso responsable de Inteligencia Artificial

La IA se utilizará para potenciar el trabajo intelectual del alumno: explicar,
revisar, comparar alternativas, detectar errores y ayudar a documentar.

El alumno debe comprender, revisar y poder defender todo lo entregado. El uso
de IA no debe sustituir su razonamiento ni la realización personal del
proyecto. Cada entrega explicará brevemente cómo se utilizó la IA y qué trabajo
y decisiones realizó el alumno.

## Aplicación en Integra Tech Consulting

Siempre que sea coherente con la unidad, el proyecto partirá de un caso de uso,
una necesidad o un componente de Integra Tech Consulting. La memoria explicará
qué relación tiene con el proyecto empresarial y qué utilidad puede aportar.

Cuando el resultado sea útil para el producto real, se preparará una integración
separada y segura en el repositorio correspondiente de `IntTecCon`. El proyecto
académico conservará su repositorio independiente.

La integración empresarial debe cumplir estas condiciones:

- Usar una rama específica y conservar intacta la rama principal.
- Respetar la arquitectura y las instrucciones del repositorio real.
- Utilizar datos ficticios o anonimizados en la entrega académica.
- No copiar credenciales, información interna ni datos de clientes.
- Ejecutar las pruebas y comprobaciones existentes.
- Revisar dependencias, migraciones, configuración y seguridad.
- Validar primero en un entorno aislado o de pruebas cuando esté disponible.
- Preparar una forma de volver al estado anterior antes de un despliegue.
- No desplegar en producción hasta que la integración haya sido revisada y
  verificada expresamente.

Si el proyecto no puede integrarse sin riesgo o no encaja con la arquitectura
actual, se conservará como prototipo y se documentará una propuesta de
integración futura.

## Proceso para cada trabajo

1. Identificar asignatura, unidad, RA y criterios de evaluación aplicables.
2. Revisar los ejercicios y ejemplos correspondientes en `DAM2`.
3. Definir un alcance similar al practicado en clase.
4. Crear una carpeta y un repositorio independientes.
5. Desarrollar el proyecto en pasos pequeños y comprensibles.
6. Probar el funcionamiento y revisar que no haya secretos ni dependencias.
7. Generar `INFORME.md` con `jocarsa | documentacion`.
8. Documentar el uso de IA, confirmar el commit final y entregar el repositorio.
9. Explicar la relación con Integra Tech Consulting y, cuando corresponda,
   preparar y verificar su integración segura.
