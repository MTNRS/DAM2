class Motor {
    constructor(canvas, configuracion = {}) {
        this.canvas = canvas;
        this.contexto = canvas.getContext("2d");
        this.entrada = new Entrada();
        this.escena = null;
        this.ultimoTiempo = 0;
        this.corriendo = false;

        this.redimensionar();

        window.addEventListener("resize", () => {
            this.redimensionar();
        });
    }

    redimensionar() {
        const proporcion = window.devicePixelRatio || 1;
        const anchura = window.innerWidth;
        const altura = window.innerHeight;

        this.canvas.width = Math.floor(anchura * proporcion);
        this.canvas.height = Math.floor(altura * proporcion);
        this.canvas.style.width = `${anchura}px`;
        this.canvas.style.height = `${altura}px`;

        this.contexto.setTransform(proporcion, 0, 0, proporcion, 0, 0);

        // Las escenas trabajan en píxeles CSS, no en píxeles físicos.
        this.canvas.anchoLogico = anchura;
        this.canvas.altoLogico = altura;
    }

    cargar(escena) {
        this.escena = escena;
        escena.motor = this;

        if (!escena.iniciada) {
            escena.iniciada = true;

            if (escena.alIniciar) {
                escena.alIniciar(escena);
            }
        }
    }

    iniciar() {
        this.corriendo = true;
        this.ultimoTiempo = performance.now();
        requestAnimationFrame((tiempo) => this.bucle(tiempo));
    }

    detener() {
        this.corriendo = false;
    }

    bucle(tiempoActual) {
        if (!this.corriendo) {
            return;
        }

        const deltaTime = Math.min(
            (tiempoActual - this.ultimoTiempo) / 1000,
            0.05
        );

        this.ultimoTiempo = tiempoActual;

        const anchura = this.canvas.anchoLogico;
        const altura = this.canvas.altoLogico;

        this.contexto.clearRect(0, 0, anchura, altura);

        if (this.escena) {
            this.escena.actualizar(deltaTime);
            this.escena.dibujar(this.contexto);
        }

        requestAnimationFrame((tiempo) => this.bucle(tiempo));
    }
}
