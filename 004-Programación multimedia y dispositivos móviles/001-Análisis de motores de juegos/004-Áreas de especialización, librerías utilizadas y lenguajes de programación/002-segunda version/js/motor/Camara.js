/**
 * Cámara 2D genérica.
 * Permite que una escena sea mayor que el canvas y seguir a cualquier Actor.
 */
class Camara {
    constructor(configuracion = {}) {
        this.x = configuracion.x ?? 0;
        this.y = configuracion.y ?? 0;
        this.objetivo = configuracion.objetivo ?? null;
        this.suavizado = configuracion.suavizado ?? 6;
    }

    seguir(actor) {
        this.objetivo = actor;
        return this;
    }

    actualizar(deltaTime, motor) {
        if (!this.objetivo) {
            return;
        }

        const destinoX = this.objetivo.x - motor.canvas.width / 2;
        const destinoY = this.objetivo.y - motor.canvas.height / 2;
        const factor = 1 - Math.exp(-this.suavizado * deltaTime);

        this.x += (destinoX - this.x) * factor;
        this.y += (destinoY - this.y) * factor;
    }
}
