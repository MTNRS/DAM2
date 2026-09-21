class Escena {
    constructor(configuracion = {}) {
        this.actores = [];
        this.motor = null;
        this.iniciada = false;
        this.camara = configuracion.camara ?? new Camara();

        this.alIniciar = configuracion.alIniciar ?? null;
        this.alActualizar = configuracion.alActualizar ?? null;
        this.alDibujarFondo = configuracion.alDibujarFondo ?? null;
        this.alDibujarInterfaz = configuracion.alDibujarInterfaz ?? null;
    }

    agregar(actor) {
        this.actores.push(actor);

        if (actor.alCrear) {
            actor.alCrear(actor, this);
        }

        return actor;
    }

    buscar(etiqueta) {
        return this.actores.filter((actor) => {
            return actor.activo && actor.tiene(etiqueta);
        });
    }

    actualizar(deltaTime) {
        if (this.alActualizar) {
            this.alActualizar(this, deltaTime);
        }

        for (const actor of [...this.actores]) {
            if (actor.activo) {
                actor.actualizar(deltaTime, this);
            }
        }

        this.resolverColisiones();
        this.actores = this.actores.filter((actor) => actor.activo);
        this.camara.actualizar(deltaTime, this.motor);
    }

    resolverColisiones() {
        for (let i = 0; i < this.actores.length; i++) {
            for (let j = i + 1; j < this.actores.length; j++) {
                const actorA = this.actores[i];
                const actorB = this.actores[j];

                if (!actorA.activo || !actorB.activo) {
                    continue;
                }

                const dx = actorA.x - actorB.x;
                const dy = actorA.y - actorB.y;
                const radio = actorA.radio + actorB.radio;

                if (dx * dx + dy * dy <= radio * radio) {
                    actorA.colisionar(actorB, this);
                    actorB.colisionar(actorA, this);
                }
            }
        }
    }

    dibujar(contexto) {
        contexto.save();
        contexto.translate(-this.camara.x, -this.camara.y);

        if (this.alDibujarFondo) {
            this.alDibujarFondo(this, contexto);
        }

        for (const actor of this.actores) {
            if (actor.activo) {
                actor.dibujar(contexto, this);
            }
        }

        contexto.restore();

        if (this.alDibujarInterfaz) {
            this.alDibujarInterfaz(this, contexto);
        }
    }
}
