# JOCARSA - Simulador de nave logística

Ejemplo construido sobre el mismo motor abstracto de `Actor`, `Jugador`, `Escena`, `Motor`, `Entrada` y `Camara`.

## Objetivo

El jugador conduce un camión articulado dentro del patio de una nave industrial. Al comenzar recibe un muelle de carga aleatorio. Debe maniobrar y colocar la parte trasera del semirremolque en ese muelle, correctamente alineada y a baja velocidad.

## Controles

- W / flecha arriba: acelerar.
- S / flecha abajo: frenar y marcha atrás.
- A / flecha izquierda: girar a la izquierda.
- D / flecha derecha: girar a la derecha.

## Arquitectura

No se ha creado una clase `Camion`, `Muelle` o `VehiculoAparcado`.

- El camión es una instancia configurable de `Jugador`.
- Los muelles son instancias de `Actor`.
- Los vehículos de ambientación son instancias de `Actor`.
- Toda la lógica específica de logística vive en `js/juego/simulador-logistica.js`.
- El directorio `js/motor/` continúa siendo genérico y reutilizable.

El ejemplo añade una simulación sencilla de semirremolque y una condición de atraque basada en posición, orientación y velocidad.

## Estructura

```text
jocarsa-simulador-logistica/
├── index.html
├── README.md
├── css/
│   └── estilo.css
└── js/
    ├── motor/
    │   ├── Actor.js
    │   ├── Jugador.js
    │   ├── Entrada.js
    │   ├── Camara.js
    │   ├── Escena.js
    │   └── Motor.js
    └── juego/
        └── simulador-logistica.js
```

Todo el HTML, CSS y JavaScript está escrito de forma expandida y legible, sin minificar.
