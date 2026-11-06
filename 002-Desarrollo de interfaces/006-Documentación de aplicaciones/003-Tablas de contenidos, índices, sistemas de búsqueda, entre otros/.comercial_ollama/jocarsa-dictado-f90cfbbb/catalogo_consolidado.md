# Catálogo funcional: jocarsa-dictado

### Actualización sin pérdida de datos
### Resumen Funcional: Actualización sin pérdida de datos

- **Proceso de actualización seguro**: Permite a los usuarios actualizar la aplicación sin perder los mensajes propios del usuario (verificada en README.md, fragmento 1). 

**Nota:** La evidencia proporcionada no incluye detalles específicos sobre cómo se realiza esta funcionalidad.

### Base de datos
### Resumen Funcional Comprobable: Base de Datos

- **Mantenimiento de la base de datos**: No se modifican los datos existentes durante las actualizaciones (No se modifica la base de datos existente. [README-v11.md, fragmento 1]).
- **Preservación de mensajes demostrativos**: Se mantienen los mensajes demostrativos en la base de datos para uso de desarrolladores y usuarios finales (seeded demonstration messages [README-v9.md, fragmento 1]).

### Base de datos SQLite
### Resumen Funcional Comprobable: Base de Datos SQLite

- **Conservación de la base de datos existente**: Permite mantener la base de datos SQLite actual durante el proceso de actualización, asegurando que los datos no se pierdan (fragmento del README.md) (Fragmento 1).

Nota: La evidencia proporcionada indica que esta funcionalidad está verificada pero no especifica detalles adicionales sobre cómo o dónde se ha comprobado.

### Buscador
### Resumen Funcional del Módulo Buscador

- **Búsqueda de mensajes guardados**: Permite al usuario buscar entre los mensajes que ha guardado previamente. (Evidencia: `search-input`, `search-button`; Archivo: `templates/index.html`)

### Búsqueda de contenido
### Resumen Funcional Comprobable: Búsqueda de Contenido

- **Buscar contenido**: Permite al usuario buscar contenido en la interfaz (evidencia: `$('search-input').oninput=search; $('search-button').onclick=search;` en `static/app.js`, fragmento 2).

### Chat
### Resumen Funcional del Módulo Chat

- **Dictado de voz a texto**: Permite transcribir el habla del usuario en formato de texto (Ollama) ([README-v9.md](README-v9.md), fragmento: 1).
- **Generación de respuesta**: Permite generar respuestas basadas en el texto transcribido (APIs `/api/chat` y `/api/generate`) ([README-v9.md](README-v9.md), fragmento: 1).

**Nota:** Las capacidades mencionadas como "inferidas" se basan en evidencias indirectas o contextualizadas.

### Compartir contenido
### Resumen Funcional del Módulo "Compartir contenido"

- **Copiar texto**: Permite al usuario copiar el texto actual en su portapapeles (verificada) ($('botoncopiar').onclick=()=>navigator.clipboard.writeText($('resultado').innerText).then(()=>status('Texto copiado'));, archivo: static/app.js, fragmento: 2)
- **Copiar asunto**: Permite al usuario copiar el asunto actual en su portapapeles (verificada) ($('botoncopiarasunto').onclick=()=>navigator.clipboard.writeText($('asuntoresultado').innerText).then(()=>status('Asunto copiado'));, archivo: static/app.js, fragmento: 2)

### Correo
### Resumen Funcional del Módulo Correo

- **Verificación de cuerpo del correo**: Permite verificar el contenido del correo mediante un inventario de palabras normalizado, descartando cambios en singular/plural o verbos. (Evidencia: El cuerpo del correo se verifica mediante un inventario de palabras normalizado.)

- **Manejo de tildes y mayúsculas**: Permite cambios en tildes y mayúsculas durante la verificación, conservando el texto original. (Evidencia: La comparación permite cambios de tildes y mayúsculas; conserva el texto original en SQLite.)

- **Control literal del dictado**: Permite usar una maquetación literal del dictado sin invenciones si Ollama cambia palabras, singular/plural o verbos. (Evidencia: Si Ollama cambia palabras, singular/plural o verbos, se descarta su cuerpo y se usa una maquetación literal del dictado, sin invenciones.)

- **Almacenamiento de texto original**: Conserva el texto original en SQLite durante la verificación. (Evidencia: La comparación permite cambios de tildes y mayúsculas; conserva el texto original en SQLite.)

- **Verificación limitada a español**: Aplica el control literal solo a salidas en español, no permitiendo este control en traducciones a otros idiomas. (Evidencia: La verificación se aplica a salidas en español; las traducciones a otros idiomas no admiten este control literal.)

### Diagnóstico
### Resumen Funcional del Módulo Diagnóstico

- **Listado de modelos Ollama**: Permite a los desarrolladores ver la lista completa de modelos disponibles en Ollama (`ollama list`).
- **Estado de procesos Ollama**: Los desarrolladores pueden verificar el estado actual de los procesos relacionados con Ollama (`ollama ps`).
- **Prueba de API tags**: Ofrece a los desarrolladores una herramienta para probar la funcionalidad de la API de etiquetas de Ollama (`curl http://127.0.0.1:11434/api/tags`).
- **Configuración de modelo Ollama**: Permite a los desarrolladores establecer el modelo específico que se utilizará en Ollama mediante una variable de entorno (`OLLAMA_MODEL=your-model-name python3 app.py`).
- **Registro de errores**: Registra y muestra cualquier error que ocurra durante la ejecución del sistema tanto en la consola de Flask como en la interfaz de usuario (Errors are logged in the Flask console and surfaced in the UI).

### Diseño de interfaz
### Resumen Funcional: Diseño de Interfaz

- **Organización de tarjetas horizontales**: Permite la visualización de tres tarjetas en disposición horizontal dentro del panel derecho (evidencia: `.editor-layout`).
- **Ajuste responsivo**: Ajusta automáticamente el diseño para adaptarse a diferentes tamaños de pantalla (evidencia: `@media(max-width:1450px), @media(max-width:1080px)`).
- **Estilo de marca**: Aplica un estilo único con colores específicos que representan la identidad visual de la marca (evidencia: `#audio-visual, #mensajes article.activo`).

### Edición de texto
### Resumen Funcional del Módulo "Edición de texto"

- **Limpiar contenido**: Permite al usuario eliminar todo el contenido actual para empezar desde cero (ver evidencia en `static/app.js`, fragmento 2).
- **Formatear contenido**: Ofrece la opción al usuario de formatear el contenido existente, mejorando su apariencia y estructura (ver evidencia en `static/app.js`, fragmento 2).
- **Guardar contenido**: Proporciona una función para que los usuarios guarden sus cambios actuales, asegurando que no se pierda ningún trabajo (ver evidencia en `static/app.js`, fragmento 2).
- **Eliminar contenido**: Permite a los usuarios eliminar el contenido actual completamente, dando la opción de empezar un nuevo documento o proyecto (ver evidencia en `static/app.js`, fragmento 2).

### Editor de dictado
### Resumen Funcional del Módulo "Editor de Dictado"

- **Grabación de audio**: Permite al usuario grabar su voz para posteriormente transcribirla (botonvoz).
- **Transcripción automática**: Transcribe el audio grabado utilizando Whisper local, aunque esta funcionalidad es inferida (Whisper large-v3 local).
- **Formateo de texto**: Transforma la transcripción en un mensaje listo para enviar con ayuda de Ollama, aunque esta funcionalidad también es inferida (Ollama).
- **Edición de transcripción**: Permite al usuario revisar y corregir la transcripción antes de su formateo (textarea id="entrada").
- **Guardado de mensajes**: Permite al usuario guardar el mensaje final para futuras referencias o envíos posteriores (botonguardar).
- **Eliminación de mensajes**: Ofrece a los usuarios la opción de eliminar el mensaje actual si es necesario (botoneliminar).
- **Envío de mensajes**: Facilita que los usuarios puedan enviar el mensaje final a través de correo electrónico o documentos (botonenviarcorreo, botonenviardocumentos).

### Estado del sistema
### Resumen Funcional: Estado del sistema

- **Mostrar el estado actual del sistema**: Informa al usuario sobre las operaciones en curso a través de mensajes de estado en la interfaz (evidencia: `status()` en `static/app.js`, fragmento 1).

### Estructura del editor
### Resumen Funcional: Estructura del Editor

- **Conservación de la estructura horizontal**: Permite a los usuarios mantener la disposición horizontal del editor en la interfaz, mejorando así su experiencia visual y de navegación (verificada) (README.md: fragmento 1).

### Flask
### Resumen Funcional del Módulo Flask

- **Desarrollo de aplicaciones web**: Permite a los desarrolladores crear y ejecutar rápidamente aplicaciones web (verificada por la línea `Flask>=3.0,<4` en el archivo `requirements.txt`).

### Formateo de texto
### Resumen Funcional: Formateo de Texto

- **Formatar el texto transcribido**: Convierte el texto transcribido en un formato específico, como un correo electrónico, utilizando la tecnología Ollama (format() en static/app.js) [fragmento 1]

### Formato de texto
### Resumen Funcional: Formato de texto

- **Deducción de párrafos**: La aplicación intenta separar el texto en párrafos basándose en las pausas y la puntuación generadas por Whisper (Evidencia: README-v11.md, fragmento 1). Sin embargo, si Whisper no introduce puntos ni separaciones claras (como en un saludo), la aplicación puede tener dificultades para deducir los párrafos con precisión (confianza inferida).

### Gestión de mensajes
### Resumen Funcional del Módulo "Gestión de mensajes"

- **Guardar mensajes**: Permite almacenar tanto los mensajes formateados como sus versiones originales en la base de datos. (save() en static/app.js, fragmento 1)
- **Eliminar mensajes**: Ofrece la capacidad de borrar mensajes específicos desde la base de datos. (remove() en static/app.js, fragmento 1)
- **Abrir y editar mensajes**: Facilita la apertura y edición de mensajes que ya han sido guardados previamente. (openRecord() en static/app.js, fragmento 1)
- **Buscar mensajes**: Permite a los usuarios encontrar mensajes específicos dentro de una lista utilizando un término de búsqueda. (search() en static/app.js, fragmento 1)

### Grabación de audio
### Resumen Funcional del Módulo "Grabación de Audio"

- **Reproductor con waveform**: Incluye un reproductor que muestra una representación visual de la onda de sonido (waveform) durante la reproducción, permitiendo play/pausa, saltos de 5 segundos y búsqueda sobre la onda. (README-v10.md: fragmento 1)

- **Mostrar waveform en directo**: Muestra la representación visual de la onda de sonido (waveform) mientras se graba el audio. (README-v10.md: fragmento 1)

- **Control de volumen**: Permite ajustar el volumen durante la reproducción del audio. (README-v10.md: fragmento 1)

- **Búsqueda en waveform**: Ofrece la capacidad de buscar en la representación visual de la onda de sonido (waveform) mientras se reproduce el audio. (README-v10.md: fragmento 1)

- **Mantener audios en memoria**: Los audios grabados se mantienen en memoria del navegador mientras el registro esté abierto, sin guardarlos en SQLite. (README-v10.md: fragmento 1)

- **Iniciar y detener la grabación de audio**: Permite al usuario iniciar y detener la grabación de su voz a través del micrófono. (static/app.js: fragmento 1)

- **Iniciar/Parar grabación**: Ofrece la funcionalidad para que el usuario pueda iniciar o detener la grabación de audio en cualquier momento. (static/app.js: fragmento 2)

### Incorporación de ejemplos
### Resumen Funcional: Incorporación de Ejemplos

- **Adición de mensajes demostrativos a la base existente**: Permite añadir 16 ejemplos ficticios a la base de datos sin duplicar entradas existentes. (python3 seed_demo.py) (README.md, fragmento 1)

Nota: Todas las capacidades mencionadas están basadas en evidencias proporcionadas y no se han inferido ni supuesto información adicional.

### Indicadores de procesamiento
### Resumen Funcional Comprobable: Indicadores de Procesamiento

- **Mostrar indicadores de trabajo asíncrono**: Permite a los usuarios visualizar indicadores que muestran el estado del procesamiento asíncrono en tiempo real (ver `.processing-indicator, .inline-spinner` en `static/style.css`, fragmento 2).

### Instalación
### Módulo: Instalación

- **Actualización del software**: Permite actualizar la aplicación desde la versión 10 a la versión 11. (Instalación: respalda tu SQLite y sustituye `app.py` de v10 por el de v11; reinicia Flask.) ([README-v11.md](README-v11.md), fragmento 1)

### Instalación nueva
### Resumen Funcional: Instalación Nueva

- **Instalación para nuevos usuarios**: Permite a los nuevos usuarios realizar una instalación inicial que incluye una base de datos SQLite ya poblada con ejemplos ficticios (README.md, fragmento 1).

Nota: No se proporcionó evidencia específica para este módulo. La información proviene del archivo README.md mencionado en la descripción general.

### Integración de Ollama
### Resumen Funcional: Integración de Ollama

- **Uso de Ollama**: Permite a todos los usuarios utilizar la herramienta Ollama dentro de la aplicación (verificada por el fragmento 1 en README.md).

### Integración de Whisper
### Resumen Funcional: Integración de Whisper

- **Uso local de Whisper**: Permite a todos los usuarios utilizar la tecnología Whisper directamente en la aplicación sin necesidad de conexión remota (verificada).

### Interfaz de usuario
### Resumen Funcional del Módulo Interfaz de Usuario

- **Indicadores de carga animados**: Muestra indicadores visuales para mostrar el progreso del procesamiento de la transcripción y la generación de respuestas (Whisper, Ollama).
- **Tema oscuro**: Mantén un tema oscuro en la interfaz de usuario (Preserves darkblue theme).
- **Diseño y disposición**: Mantiene el diseño y la disposición de la interfaz de usuario.
- **Estilo global**: Define estilos globales para la aplicación, incluyendo colores y tipografía (:root).
- **Barra superior**: Muestra una barra superior con opciones de búsqueda y herramientas (#topbar).
- **Panel lateral**: Muestra un panel lateral con opciones de navegación (#bandejas).
- **Panel de mensajes**: Muestra un panel de mensajes con opciones de visualización (#mensajes).
- **Contenido principal**: Muestra el contenido principal de la aplicación (#contenido).
- **Botones estilizados**: Define diferentes tipos de botones con estilos personalizados (.ju-btn).
- **Tarjetas**: Define estilos para tarjetas utilizadas en la interfaz (.ju-card).
- **Formularios administrativos**: Define estilos para formularios utilizados por los administradores (.form-admin).
- **Tabla de datos**: Define estilos para tablas utilizadas en la interfaz (.ju-table).
- **Notificaciones y errores**: Define estilos para notificaciones y mensajes de error (.notice, .error).
- **Editor adaptativo**: Define estilos para un editor adaptable con múltiples etapas (.editor-layout).
- **Responsive design**: Define estilos para adaptar la interfaz a diferentes tamaños de pantalla (@media (max-width:1200px), @media (max-width:900px)).

### Logo y favicon
### Resumen Funcional: Logo y Favicon

- **Visualización de logo personalizado**: Permite mostrar un logo específico en la interfaz del usuario (https://static.jocarsa.com/logos/darkblue.svg) ([README.md](README.md), fragmento 1).
- **Visualización de favicon personalizado**: Permite mostrar un favicon específico en la interfaz del usuario (https://static.jocarsa.com/logos/darkblue.svg) ([README.md](README.md), fragmento 1).

### Mensajes demostrativos
### Resumen Funcional: Mensajes demostrativos

- **Incorporación de mensajes ficticios**: Permite añadir hasta 16 mensajes en español para una demostración pública (verificada) ([README.md](README.md), fragmento 1).
- **Etiquetado de mensajes demostrativos**: Facilita la identificación de los mensajes ficticios con etiquetas específicas como `modelo=demo` y `estado=demostracion`, lo que ayuda a distinguirlos fácilmente (verificada) ([README.md](README.md), fragmento 1).

### Modelo de procesamiento
### Resumen Funcional: Modelo de Procesamiento

- **Actualización secuencial del modelo GPU**: Permite a los desarrolladores y usuarios finales actualizar el modelo GPU en una secuencia ordenada (sequential GPU model release) ([README-v9.md](README-v9.md), fragmento 1).

### Paleta de colores
### Resumen Funcional del Módulo "Paleta de colores"

- **Aplicación de paleta darkblue**: Permite a todos los usuarios aplicar una paleta de colores específica (darkblue) en las tres columnas y el editor. (Evidencia: README.md, fragmento 1)

### Panel de administración
```markdown
## Resumen Funcional del Módulo: Panel de administración

- **Mostrar y ocultar el panel de administración**: Permite al usuario cambiar entre la vista de edición y el panel de administración (showAdmin(), showEditor() en `static/app.js`).
- **Visualizar mensajes en la tabla de administración**: Muestra una lista de mensajes con opciones para abrir y eliminar dichos mensajes (renderAdmin() en `static/app.js`).
```

### Panel de administrador
### Resumen Funcional del Módulo "Panel de administrador"

- **Visualización de mensajes guardados**: Permite al administrador ver una lista completa de todos los mensajes almacenados (evidencia: `admin-table`).
- **Creación de nuevos mensajes**: Ofrece la capacidad para que el administrador cree un nuevo mensaje desde cero (evidencia: `admin-new`).
- **Eliminación de mensajes guardados**: Proporciona a los administradores la opción de eliminar cualquier mensaje previamente guardado (evidencia: `data-admin-delete`).
- **Abrir mensajes guardados**: Facilita que el administrador pueda abrir y revisar un mensaje específico ya guardado (evidencia: `data-admin-open`).

### Procesamiento
### Resumen Funcional del Módulo Procesamiento

- **Indicador de procesamiento**: Muestra un indicador visual durante las operaciones de transcripción y formateo para informar al usuario sobre el estado actual (verificada: processing() en static/app.js, fragmento 1).

### Proyecto completo
### Resumen Funcional del Módulo: Proyecto completo

- **Descarga del proyecto**: Permite a los usuarios descargar todo el contenido del proyecto en un archivo ZIP, incluyendo todos sus componentes (El ZIP también contiene el proyecto completo) ([README-v11.md](README-v11.md), fragmento 1).

### Recarga de la interfaz
### Resumen Funcional: Recarga de la Interfaz

- **Recarga manual de la página**: Permite a los usuarios recargar la interfaz del usuario utilizando las teclas Ctrl+Shift+R (verificada) ([README.md](README.md), fragmento 1).

### Reproducción de audio
### Resumen Funcional del Módulo: Reproducción de Audio

- **Reproducir/Pausar audio**: Permite al usuario iniciar o detener la reproducción del audio grabado. (Evidencia: `static/app.js`, fragmento 2)
- **Avanzar/Retroceder en el audio**: Permite al usuario avanzar o retroceder 5 segundos en la reproducción del audio. (Evidencia: `static/app.js`, fragmento 2)
- **Controlar volumen del audio**: Permite al usuario ajustar el nivel de volumen del audio durante su reproducción. (Evidencia: `static/app.js`, fragmento 2)

### Reproductor
### Resumen Funcional del Módulo Reproductor

- **Reproducción de onda sonora**: Permite la visualización y reproducción de audio en formato waveform, manteniendo las características presentes en la versión v10 (Se conserva el reproductor waveform de v10. [README-v11.md: Fragmento 1])

### Reproductor de audio
### Resumen Funcional del Módulo Reproductor de Audio

- **Mostrar reproductor de onda de audio**: Permite visualizar un reproductor que muestra la grabación real en forma de onda (evidencia: `.audio-player`, archivo: `static/style.css`).
- **Control de volumen**: Ofrece la capacidad para ajustar el nivel del sonido del reproductor (evidencia: `#audio-volume`, archivo: `static/style.css`).
- **Botones de control**: Proporciona botones interactivos que permiten controlar las funciones básicas del reproductor, como play, pause y stop (evidencia: `.audio-player-controls button`, archivo: `static/style.css`).
- **Posición del audio**: Muestra la ubicación actual en el tiempo de la reproducción del audio (evidencia: `#audio-position`, archivo: `static/style.css`).
- **Onda de audio interactiva**: Permite a los usuarios interactuar con la representación gráfica de la onda sonora, posiblemente para navegar o seleccionar partes específicas del audio (evidencia: `#recorded-wave`, archivo: `static/style.css`).

### Resumen
### Resumen

- **Resumen del asunto**: Permite crear un resumen conciso del tema principal de la transcripción (El asunto sí puede resumirse. [README-v11.md: Fragmento 1]).

*Nota*: La capacidad para inferir y generar resúmenes se basa en evidencia conceptual, aunque no está explícitamente probada en todos los casos.

### Tarjeta de transcripción
### Resumen Funcional: Tarjeta de Transcripción

- **Entrada de texto**: Permite a los usuarios ingresar texto directamente en la tarjeta de transcripción para registrar o editar información (evidencia: `.transcription-card #entrada`, archivo: `static/style.css`, fragmento: 2).

### Tarjeta final
### Resumen Funcional: Tarjeta Final

- **Resultado de procesamiento**: Permite que los usuarios vean el resultado final del proceso en la tarjeta final (`.final-card #resultado`).
- **Asunto del resultado**: Muestra el asunto o tema relacionado con el resultado en la tarjeta final (`.final-card #asuntoresultado`).

### Transcripción
### Resumen Funcional del Módulo Transcripción

- **Dictado a texto**: Permite convertir el habla en texto utilizando Whisper (Evidencia: Ollama propone asunto y estructura de párrafos, pero el cuerpo final se reconstruye exclusivamente desde las palabras de la transcripción original) ([README-v11.md](README-v11.md), Fragmento 1)

### Transcripción de audio
### Resumen Funcional del Módulo: Transcripción de Audio

- **Transcribir audio grabado**: Convierte el audio grabado en texto utilizando la tecnología Whisper (evidencia: finish(), archivo: static/app.js, fragmento: 1).

Nota: Todas las capacidades mencionadas están basadas en evidencias proporcionadas. No se han inferido ni añadido funcionalidades adicionales sin soporte documental claro.

### Visualización de audio
### Resumen Funcional: Visualización de Audio

- **Visualización en tiempo real del audio grabado**: Muestra una representación gráfica actualizada mientras se captura el sonido (wave()).
- **Representación visual de la onda sonora**: Proporciona una imagen detallada de la onda sonora del audio recién grabado, permitiendo a los usuarios ver cómo suena en forma de gráficos (showRecordedAudio(blob), drawRecordedWave(), seekRecorded(ev)).

### app.py
### Resumen Funcional de `app.py`

- **Transcripción de audio a texto**: Permite transcribir archivos de audio en formato WAV a texto utilizando el modelo Whisper. (evidencia: transcribe_local)

- **Generación de asunto y cuerpo de correo electrónico**: Utiliza el modelo Ollama para generar un asunto y cuerpo de correo electrónico basado en una transcripción proporcionada. (evidencia: ollama)

- **Almacenamiento de mensajes en base de datos**: Guarda transcripciones, asuntos y cuerpos de correo electrónicos en una base de datos SQLite. (evidencia: init_db)

- **Manejo de modelos Ollama**: Carga y descarga el modelo Ollama para generar respuestas basadas en prompts. (evidencia: ollama, unload_ollama)

- **Manejo de modelos Whisper**: Carga y libera el modelo Whisper para transcribir audio a texto. (evidencia: transcribe_local, release_whisper)

- **Comparación de tokens lexicales**: Compara los tokens lexicales de una transcripción original y su versión procesada para asegurar la fidelidad del texto. (evidencia: lexical_tokens, faithful_body)

- **Formateo literal de texto**: Ajusta el formato del texto transcribido para mantener la estructura original sin cambiar las palabras. (evidencia: literal_layout)

- **Configuración de entorno**: Permite configurar variables de entorno para ajustar el comportamiento del sistema, como la URL del modelo Ollama y el dispositivo utilizado por Whisper. (evidencia: variables de entorno)

- **Manejo de errores y logging**: Registra errores y advertencias durante el procesamiento de solicitudes para facilitar la depuración. (evidencia: app.logger.warning)

- **Control de concurrencia**: Utiliza un bloqueo para serializar solicitudes y evitar que múltiples procesos utilicen la GPU simultáneamente. (evidencia: _gpu_lock)

- **Manejo de memoria**: Libera recursos y vacía la caché de GPU para evitar problemas de memoria insuficiente. (evidencia: release_whisper, torch.cuda.empty_cache)

- **Configuración de límites de contenido**: Establece un límite máximo para el tamaño del contenido que se puede procesar en una sola solicitud. (evidencia: app.config['MAX_CONTENT_LENGTH'])

- **Transcripción de audio**: Permite transcribir grabaciones de audio a texto. (evidencia: app.post('/api/transcribe'))

- **Formateo de texto**: Permite formatear y estructurar textos según diferentes tipos (email, WhatsApp, social media, documento). (evidencia: app.post('/api/format'))

- **Procesamiento combinado de audio y texto**: Permite procesar una grabación de audio para obtener su transcripción y luego formatearla. (evidencia: app.post('/api/process'))

- **Guardado de mensajes**: Permite guardar mensajes con sus versiones originales, formateadas y otros detalles. (evidencia: app.post('/api/save'))

- **Consulta de mensajes**: Permite obtener una lista de los últimos mensajes almacenados. (evidencia: app.get('/api/messages'))

- **Edición manual de mensajes**: Permite editar manualmente los detalles de un mensaje existente. (evidencia: app.post('/api/manual'))

- **Eliminación de mensajes**: Permite eliminar un mensaje específico del sistema. (evidencia: app.post('/api/delete/<int:msgid>')

- **Manejo de errores**: Permite manejar y registrar errores que ocurran en el sistema. (evidencia: @app.errorhandler(Exception))

- **Almacenamiento de mensajes**: Permite almacenar mensajes con detalles como el asunto, el cuerpo y las emociones asociadas. (evidencia: store())

- **Procesamiento de texto**: Permite procesar textos para obtener su formato y estructura adecuados. (evidencia: process_text())

- **Generación de propuesta de diseño de párrafos**: Permite generar una propuesta de diseño de párrafos basada en el texto original y propuesto. (evidencia: project_layout())

- **Parseo de resultados**: Permite parsear los resultados obtenidos para extraer el asunto y el cuerpo del mensaje. (evidencia: parse_result())

- **Manejo de excepciones HTTP**: Devuelve una respuesta JSON con un estado 'ok' falso y el mensaje de error correspondiente al código de excepción HTTP. (evidencia: if isinstance(e,HTTPException):return jsonify(ok=False,error=e.description),e.code)

- **Registro de errores**: Registra una excepción en el logger de la aplicación cuando se produce un error. (evidencia: app.logger.exception('Request failed'))

### app.py
### Resumen Funcional de `app.py`

- **Manejo de excepciones generales**: Devuelve una respuesta JSON con un estado 'ok' falso y el mensaje de error correspondiente a cualquier excepción no HTTP. (return jsonify(ok=False,error=str(e)),500)

- **Abrir Chrome cuando el servidor está listo**: Lanza Google Chrome en una nueva ventana con la URL del servidor Flask una vez que este esté aceptando conexiones. (def open_chrome_when_ready(host, port))

- **Verificación de disponibilidad del servidor Flask**: Intenta conectarse al servidor Flask hasta 100 veces con un intervalo de 0.1 segundos entre cada intento. (for _ in range(100): try: with socket.create_connection((host, port), timeout=0.3): break except OSError: time.sleep(0.1))

- **Registro de advertencia si Flask no está disponible**: Registra una advertencia en el logger si Flask no está disponible después de 100 intentos de conexión. (app.logger.warning('Flask no está disponible; Chrome no se abrirá'))

- **Detección de navegador Chrome**: Busca la ubicación del ejecutable de Google Chrome o Chromium en el sistema. (chrome = shutil.which('google-chrome') or shutil.which('google-chrome-stable') or shutil.which('chromium'))

- **Abrir navegador Chrome**: Lanza Google Chrome en una nueva ventana con la URL del servidor Flask si se encuentra el ejecutable. (subprocess.Popen([chrome, '--new-window', url], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, start_new_session=True))

- **Abrir navegador web predeterminado si Chrome no está disponible**: Abre el navegador web predeterminado con la URL del servidor Flask si Google Chrome o Chromium no están disponibles. (webbrowser.open(url))

- **Configuración de host y puerto desde variables de entorno**: Obtiene el host y el puerto del servidor Flask desde las variables de entorno, utilizando valores predeterminados si no están definidas. (host = os.getenv('HOST', '127.0.0.1') port = int(os.getenv('PORT', '5000')))

- **Inicio del servidor Flask en un hilo separado**: Inicia el servidor Flask en un hilo separado para permitir que se abra Chrome al mismo tiempo. (threading.Thread(target=open_chrome_when_ready, args=(host, port), daemon=True).start())

- **Ejecución del servidor Flask**: Inicia el servidor Flask con las configuraciones especificadas. (app.run(host=host, port=port, debug=False, use_reloader=False))

### faster-whisper
### Resumen Funcional Comprobable para el Módulo `faster-whisper`

- **Transcripción de Audio a Texto**: Permite convertir audio en texto de manera rápida y eficiente, siendo útil para desarrolladores que trabajan con aplicaciones de procesamiento de voz o ingenieros de inteligencia artificial (verificada por `faster-whisper>=1.1` en el archivo `requirements.txt`, fragmento 1).

### mensajes
```markdown
### Resumen Funcional del Módulo "mensajes"

- **Almacenamiento de Mensajes**: Permite guardar los detalles de los mensajes en una base de datos SQLite, incluyendo campos como título y contenido original/formateado. (CREATE TABLE IF NOT EXISTS mensajes)

- **Gestión de Columnas Dinámica**: Añade nuevas columnas a la tabla de mensajes si no existen, asegurando que los datos se mantengan integros. (ALTER TABLE mensajes ADD COLUMN)

- **Inserción de Ejemplos**: Inserta una serie de ejemplos de mensajes en la base de datos para pruebas y demostraciones. (EXAMPLES = [('Planificación de la evaluación inicial', 'email', ...), ...])

- **Soporte para Múltiples Canales**: Almacena mensajes desde diferentes canales como email, WhatsApp y redes sociales. (c.execute("CREATE TABLE IF NOT EXISTS mensajes (...)\"))

- **Gestión de Estados**: Permite gestionar los estados de los mensajes, tales como 'borrador'. (estado TEXT NOT NULL DEFAULT 'borrador')

- **Soporte para Múltiples Idiomas**: Almacena mensajes en diferentes idiomas con un campo específico para el idioma de salida. (idioma_salida TEXT NOT NULL DEFAULT 'es')

- **Almacenamiento de Emociones**: Guarda información sobre las emociones asociadas a los mensajes en formato JSON. (emociones_json TEXT NOT NULL DEFAULT '')

- **Almacenamiento de Tipo de Texto**: Almacena el tipo de texto del mensaje, como 'email', 'whatsapp' o 'social'. (tipo_texto TEXT NOT NULL DEFAULT 'email')

- **Almacenamiento de Dictado**: Permite almacenar el texto dictado en un campo específico. (dictado TEXT NOT NULL DEFAULT '')

- **Gestión de Fechas**: Almacena y gestiona las fechas de creación y actualización de los mensajes. (created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)

- **Almacenamiento de Asunto**: Guarda el asunto del mensaje en un campo específico. (asunto TEXT NOT NULL DEFAULT '')

- **Verificar Existencia de Mensaje**: Permite verificar si un mensaje con un título específico ya existe en la base de datos. (c.execute("SELECT 1 FROM mensajes WHERE modelo='demo' AND asunto=? LIMIT 1", (title,)).fetchone())

- **Insertar Mensaje de Demostración**: Inserta un nuevo mensaje de demostración en la base de datos. (c.execute("INSERT INTO mensajes(titulo,original,formateado,modelo,estado,asunto,emociones_json,tipo_texto,idioma_salida,dictado) VALUES(?,?,?,'demo','demostracion',?,'{}',?,'es',?)", (title, raw, body, title, kind, raw)))

- **Contar Inserciones**: Permite contar el número de mensajes de demostración insertados en la base de datos. (count+=1)

- **Imprimir Resumen de Inserciones**: Imprime un resumen del número de mensajes de demostración insertados en la base de datos. (print(f'Insertados {count} mensajes de demostración en {DB}'))
```

### requests
### Resumen Funcional del Módulo `requests`

- **Realización de solicitudes HTTP**: Permite enviar peticiones a servidores web y recibir sus respuestas (evidencia: `requests>=2.31` en requirements.txt).

Nota: La evidencia proporcionada confirma la capacidad de realizar solicitudes HTTP, pero no se han presentado detalles adicionales sobre funcionalidades específicas como manejo de sesiones, autenticación o descargas, por lo que estas capacidades no están incluidas en este resumen.
