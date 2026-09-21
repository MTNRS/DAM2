/**
 * Jugador extiende Actor únicamente con un mapa configurable de controles.
 * Sigue sin conocer el tipo de juego que se está ejecutando.
 */
class Jugador extends Actor {
    constructor(configuracion = {}) {
        super(configuracion);

        this.controles = configuracion.controles ?? {};
        this.acciones = configuracion.acciones ?? {};
    }

    actualizar(deltaTime, escena) {
        for (const [accion, teclas] of Object.entries(this.controles)) {
            const listaTeclas = Array.isArray(teclas) ? teclas : [teclas];
            const activa = listaTeclas.some((tecla) => {
                return escena.motor.entrada.pulsada(tecla);
            });

            if (activa && this.acciones[accion]) {
                this.acciones[accion](this, deltaTime, escena);
            }
        }

        super.actualizar(deltaTime, escena);
    }
}
