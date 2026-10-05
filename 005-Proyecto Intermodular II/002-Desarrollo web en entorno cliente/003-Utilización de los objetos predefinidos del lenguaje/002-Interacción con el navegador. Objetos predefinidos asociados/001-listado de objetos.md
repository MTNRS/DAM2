| Objeto / API | Para qué sirve | Ejemplo |
|---|---|---|
| `window` | Representa la ventana del navegador | `window.innerWidth` |
| `document` | Representa el documento HTML (DOM) | `document.querySelector("h1")` |
| `console` | Mostrar información de depuración | `console.log("Hola")` |
| `location` | Información y navegación de la URL actual | `location.href` |
| `history` | Historial de navegación | `history.back()` |
| `navigator` | Información y capacidades del navegador/dispositivo | `navigator.language` |
| `screen` | Información sobre la pantalla | `screen.width` |
| `localStorage` | Almacenamiento persistente en el navegador | `localStorage.setItem("nombre","Ana")` |
| `sessionStorage` | Almacenamiento durante la sesión | `sessionStorage.setItem("id","25")` |
| `fetch` | Realizar peticiones HTTP | `fetch("datos.json")` |
| `URL` | Crear y manipular URLs | `new URL(location.href)` |
| `URLSearchParams` | Trabajar con parámetros de una URL | `new URLSearchParams(location.search)` |
| `FormData` | Recoger y enviar datos de formularios | `new FormData(formulario)` |
| `File` | Representa un archivo | `archivo.name` |
| `FileReader` | Leer archivos seleccionados por el usuario | `new FileReader()` |
| `Blob` | Manejar datos binarios | `new Blob(["Hola"])` |
| `Image` | Crear/cargar imágenes desde JavaScript | `new Image()` |
| `Audio` | Reproducir audio | `new Audio("audio.mp3")` |
| `WebSocket` | Comunicación bidireccional en tiempo real | `new WebSocket(url)` |
| `Event` | Crear/representar eventos | `new Event("cambio")` |
| `MouseEvent` | Eventos relacionados con el ratón | `new MouseEvent("click")` |
| `KeyboardEvent` | Eventos de teclado | `new KeyboardEvent("keydown")` |
| `Notification` | Notificaciones del sistema | `new Notification("Hola")` |
| `Geolocation` | Obtener ubicación, con permiso | `navigator.geolocation.getCurrentPosition(...)` |
| `Clipboard` | Leer/escribir en el portapapeles | `navigator.clipboard.writeText("Hola")` |
| `MediaDevices` | Acceso a cámara y micrófono | `navigator.mediaDevices.getUserMedia(...)` |
| `SpeechSynthesis` | Síntesis de voz | `speechSynthesis.speak(...)` |
| `SpeechSynthesisUtterance` | Define el texto que se va a hablar | `new SpeechSynthesisUtterance("Hola")` |
| `WebWorker` / `Worker` | Ejecutar JS en otro hilo | `new Worker("worker.js")` |
| `WebSocket` | Comunicación cliente-servidor persistente | `new WebSocket("wss://...")` |
| `XMLHttpRequest` | Peticiones HTTP clásicas | `new XMLHttpRequest()` |
| `IntersectionObserver` | Detectar si un elemento entra en pantalla | `new IntersectionObserver(...)` |
| `MutationObserver` | Detectar cambios en el DOM | `new MutationObserver(...)` |
| `ResizeObserver` | Detectar cambios de tamaño | `new ResizeObserver(...)` |
| `Performance` | Medir rendimiento y tiempos | `performance.now()` |
| `Crypto` | Funciones criptográficas seguras | `crypto.randomUUID()` |
| `CanvasRenderingContext2D` | Dibujar en un `<canvas>` | `ctx.fillRect(...)` |