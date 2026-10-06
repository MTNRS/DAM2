class Entrada {
    constructor() {
        this.teclas = new Set();

        window.addEventListener("keydown", (evento) => {
            this.teclas.add(evento.code);

            const teclasJuego = [
                "ArrowUp",
                "ArrowDown",
                "ArrowLeft",
                "ArrowRight",
                "Space"
            ];

            if (teclasJuego.includes(evento.code)) {
                evento.preventDefault();
            }
        });

        window.addEventListener("keyup", (evento) => {
            this.teclas.delete(evento.code);
        });
    }

    pulsada(codigo) {
        return this.teclas.has(codigo);
    }
}
