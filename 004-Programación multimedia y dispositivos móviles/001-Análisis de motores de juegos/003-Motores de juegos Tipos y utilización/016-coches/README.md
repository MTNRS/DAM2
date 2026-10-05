# JOCARSA Motor - Simulador cenital de coches

Este proyecto reutiliza y extiende el motor abstracto creado para el juego de naves.

## Qué se mantiene genérico

El directorio `js/motor/` no contiene ninguna clase `Coche`, `Pista` o `Cono`.

- `Actor`: entidad genérica configurable.
- `Jugador`: Actor controlable mediante un mapa de entrada/acciones.
- `Escena`: colección de actores, actualización, dibujo y colisiones.
- `Motor`: canvas, delta time y game loop.
- `Entrada`: estado del teclado.
- `Camara`: nueva abstracción 2D capaz de seguir cualquier Actor.

## Qué pertenece al simulador

`js/juego/simulador-coches.js` construye el coche como una instancia de `Jugador` y los conos como instancias de `Actor`.

La física del coche se configura mediante datos y callbacks: aceleración, frenada, marcha atrás, rozamiento, dirección, velocidad máxima y penalización al salir al césped.

## Controles

- W / Flecha arriba: acelerar.
- S / Flecha abajo: frenar y marcha atrás.
- A / Flecha izquierda: girar a la izquierda.
- D / Flecha derecha: girar a la derecha.

## Extensión del motor

La única ampliación estructural necesaria ha sido `Camara.js`, más los hooks `alDibujarFondo` y `alDibujarInterfaz` en `Escena`. Son conceptos genéricos y reutilizables en otros juegos.

Todo el HTML, CSS y JavaScript está deliberadamente sin minificar para facilitar su uso docente y su evolución.
