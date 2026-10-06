/**
 * Actor es la unidad genérica del motor.
 *
 * No sabe si representa un coche, una nave, una roca o un personaje.
 * El comportamiento concreto se proporciona mediante configuración y
 * componentes/comportamientos.
 */
class Actor {
    constructor(configuracion = {}) {
        this.x = configuracion.x ?? 0;
        this.y = configuracion.y ?? 0;
        this.angulo = configuracion.angulo ?? 0;

        this.vx = configuracion.vx ?? 0;
        this.vy = configuracion.vy ?? 0;
        this.velocidad = configuracion.velocidad ?? 0;

        this.radio = configuracion.radio ?? 16;
        this.vida = configuracion.vida ?? 1;
        this.activo = true;

        this.etiquetas = new Set(configuracion.etiquetas ?? []);
        this.datos = { ...(configuracion.datos ?? {}) };
        this.comportamientos = [];

        this.alActualizar = configuracion.alActualizar ?? null;
        this.alDibujar = configuracion.alDibujar ?? null;
        this.alColisionar = configuracion.alColisionar ?? null;
        this.alCrear = configuracion.alCrear ?? null;
        this.alDestruir = configuracion.alDestruir ?? null;
    }

    agregarComportamiento(comportamiento) {
        this.comportamientos.push(comportamiento);
        return this;
    }

    actualizar(deltaTime, escena) {
        this.x += this.vx * deltaTime;
        this.y += this.vy * deltaTime;

        for (const comportamiento of this.comportamientos) {
            comportamiento(this, deltaTime, escena);
        }

        if (this.alActualizar) {
            this.alActualizar(this, deltaTime, escena);
        }
    }

    dibujar(contexto, escena) {
        if (this.alDibujar) {
            this.alDibujar(this, contexto, escena);
        }
    }

    colisionar(otro, escena) {
        if (this.alColisionar) {
            this.alColisionar(this, otro, escena);
        }
    }

    tiene(etiqueta) {
        return this.etiquetas.has(etiqueta);
    }

    destruir(escena) {
        if (!this.activo) {
            return;
        }

        this.activo = false;

        if (this.alDestruir) {
            this.alDestruir(this, escena);
        }
    }
}
