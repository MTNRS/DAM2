# JOCARSA Motor de juegos — abstracción del juego 014

El proyecto sustituye las clases especializadas Nave/Roca/Proyectil/Estrella por un pequeño núcleo reutilizable:

- `Actor`: objeto universal de escena. Posición, velocidad, radio, vida, etiquetas, datos, callbacks y comportamientos.
- `Jugador`: Actor genérico con mapa configurable de controles y acciones. No conoce el juego de naves.
- `Escena`: contiene actores, actualiza, dibuja, busca por etiquetas y resuelve colisiones.
- `Motor`: canvas, tiempo, entrada, resize y game loop.
- `Entrada`: estado de teclado.

El juego de naves vive únicamente en `js/juego/juego-naves.js`: crea instancias de `Actor` y `Jugador` y les inyecta comportamiento. Así, para hacer otro juego no es necesario crear `Roca`, `Nave`, `Proyectil`, etc.; se configura cada actor.

## Idea central

```js
const enemigo = new Actor({
  x: 100,
  y: 100,
  etiquetas: ["enemigo"],
  alDibujar(actor, ctx) { /* apariencia */ },
  alColisionar(actor, otro, escena) { /* reacción */ }
});

enemigo.agregarComportamiento((actor, dt, escena) => {
  // comportamiento reutilizable
});
```

Esto se aproxima conceptualmente a Actor + componentes/behaviours: herencia mínima y composición/configuración para especializar instancias.
