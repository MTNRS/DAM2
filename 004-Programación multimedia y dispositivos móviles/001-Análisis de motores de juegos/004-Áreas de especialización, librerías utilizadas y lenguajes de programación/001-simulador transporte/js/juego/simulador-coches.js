/*
 * JOCARSA - Simulador cenital de coches
 *
 * Este archivo contiene TODO lo específico del juego.
 * El motor no sabe qué es un coche, una carretera, un cono o una vuelta.
 */

const canvas = document.querySelector("#escenario");
const motor = new Motor(canvas);

const MUNDO = {
    anchura: 2200,
    altura: 1500
};

const PISTA = {
    centroX: 1100,
    centroY: 750,
    radioXExterior: 850,
    radioYExterior: 560,
    radioXInterior: 520,
    radioYInterior: 270
};

const escena = new Escena({
    camara: new Camara({
        suavizado: 5
    })
});

let coche = null;
let tiempoVuelta = 0;
let mejorVuelta = null;
let vueltas = 0;
let pasoCheckpoint = false;

function puntoDentroElipse(x, y, radioX, radioY) {
    const dx = (x - PISTA.centroX) / radioX;
    const dy = (y - PISTA.centroY) / radioY;

    return dx * dx + dy * dy <= 1;
}

function estaSobreAsfalto(x, y) {
    const dentroExterior = puntoDentroElipse(
        x,
        y,
        PISTA.radioXExterior,
        PISTA.radioYExterior
    );

    const dentroInterior = puntoDentroElipse(
        x,
        y,
        PISTA.radioXInterior,
        PISTA.radioYInterior
    );

    return dentroExterior && !dentroInterior;
}

function dibujarElipse(contexto, radioX, radioY) {
    contexto.beginPath();
    contexto.ellipse(
        PISTA.centroX,
        PISTA.centroY,
        radioX,
        radioY,
        0,
        0,
        Math.PI * 2
    );
}

function dibujarPista(contexto) {
    contexto.fillStyle = "#3f7d3b";
    contexto.fillRect(0, 0, MUNDO.anchura, MUNDO.altura);

    // Pequeñas marcas de césped para dar sensación de movimiento.
    contexto.fillStyle = "rgba(255, 255, 255, 0.055)";
    for (let x = 0; x < MUNDO.anchura; x += 80) {
        for (let y = 0; y < MUNDO.altura; y += 80) {
            if ((x / 80 + y / 80) % 2 === 0) {
                contexto.fillRect(x, y, 40, 40);
            }
        }
    }

    // Asfalto exterior.
    contexto.fillStyle = "#3b3d40";
    dibujarElipse(
        contexto,
        PISTA.radioXExterior,
        PISTA.radioYExterior
    );
    contexto.fill();

    // Zona interior de césped.
    contexto.fillStyle = "#4b9145";
    dibujarElipse(
        contexto,
        PISTA.radioXInterior,
        PISTA.radioYInterior
    );
    contexto.fill();

    // Bordes blancos.
    contexto.lineWidth = 10;
    contexto.strokeStyle = "#eeeeee";

    dibujarElipse(
        contexto,
        PISTA.radioXExterior - 5,
        PISTA.radioYExterior - 5
    );
    contexto.stroke();

    dibujarElipse(
        contexto,
        PISTA.radioXInterior + 5,
        PISTA.radioYInterior + 5
    );
    contexto.stroke();

    // Línea central discontinua.
    contexto.save();
    contexto.setLineDash([28, 24]);
    contexto.lineWidth = 4;
    contexto.strokeStyle = "rgba(255,255,255,0.55)";

    dibujarElipse(
        contexto,
        (PISTA.radioXExterior + PISTA.radioXInterior) / 2,
        (PISTA.radioYExterior + PISTA.radioYInterior) / 2
    );
    contexto.stroke();
    contexto.restore();

    dibujarLineaMeta(contexto);
}

function dibujarLineaMeta(contexto) {
    const x = PISTA.centroX;
    const yExterior = PISTA.centroY - PISTA.radioYExterior;
    const yInterior = PISTA.centroY - PISTA.radioYInterior;
    const tamano = 16;

    for (let y = yExterior; y < yInterior; y += tamano) {
        const fila = Math.floor((y - yExterior) / tamano);

        for (let columna = 0; columna < 2; columna++) {
            contexto.fillStyle = (fila + columna) % 2 === 0
                ? "#ffffff"
                : "#111111";

            contexto.fillRect(
                x - tamano + columna * tamano,
                y,
                tamano,
                tamano
            );
        }
    }
}

function crearCoche() {
    const configuracion = {
        x: PISTA.centroX + 60,
        y: PISTA.centroY - 410,
        angulo: 0,
        radio: 22,
        etiquetas: ["jugador", "vehiculo"],

        datos: {
            aceleracion: 330,
            frenada: 480,
            velocidadMaxima: 520,
            velocidadMarchaAtras: -150,
            rozamientoAsfalto: 0.985,
            rozamientoCesped: 0.94,
            giro: 2.5,
            longitud: 54,
            anchura: 30,
            sobreAsfalto: true
        },

        controles: {
            acelerar: ["ArrowUp", "KeyW"],
            frenar: ["ArrowDown", "KeyS"],
            izquierda: ["ArrowLeft", "KeyA"],
            derecha: ["ArrowRight", "KeyD"]
        },

        acciones: {
            acelerar(actor, deltaTime) {
                actor.velocidad += actor.datos.aceleracion * deltaTime;
            },

            frenar(actor, deltaTime) {
                actor.velocidad -= actor.datos.frenada * deltaTime;
            },

            izquierda(actor, deltaTime) {
                girarCoche(actor, -1, deltaTime);
            },

            derecha(actor, deltaTime) {
                girarCoche(actor, 1, deltaTime);
            }
        },

        alActualizar(actor, deltaTime) {
            actualizarFisicaCoche(actor, deltaTime);
            actualizarVueltas(actor);
        },

        alDibujar(actor, contexto) {
            dibujarCoche(actor, contexto);
        }
    };

    return new Jugador(configuracion);
}

function girarCoche(actor, direccion, deltaTime) {
    const velocidadNormalizada = Math.min(
        Math.abs(actor.velocidad) / 180,
        1
    );

    if (velocidadNormalizada < 0.04) {
        return;
    }

    const sentidoMarcha = actor.velocidad >= 0 ? 1 : -1;

    actor.angulo += (
        direccion *
        actor.datos.giro *
        velocidadNormalizada *
        sentidoMarcha *
        deltaTime
    );
}

function actualizarFisicaCoche(actor, deltaTime) {
    actor.datos.sobreAsfalto = estaSobreAsfalto(actor.x, actor.y);

    actor.velocidad = Math.min(
        actor.velocidad,
        actor.datos.velocidadMaxima
    );

    actor.velocidad = Math.max(
        actor.velocidad,
        actor.datos.velocidadMarchaAtras
    );

    const rozamientoBase = actor.datos.sobreAsfalto
        ? actor.datos.rozamientoAsfalto
        : actor.datos.rozamientoCesped;

    const rozamientoAjustado = Math.pow(rozamientoBase, deltaTime * 60);
    actor.velocidad *= rozamientoAjustado;

    if (Math.abs(actor.velocidad) < 0.5) {
        actor.velocidad = 0;
    }

    // Actor ya integra vx/vy. Aquí convertimos velocidad longitudinal
    // del vehículo en un vector del mundo.
    actor.vx = Math.cos(actor.angulo) * actor.velocidad;
    actor.vy = Math.sin(actor.angulo) * actor.velocidad;

    // El césped limita mucho la velocidad.
    if (!actor.datos.sobreAsfalto) {
        actor.velocidad = Math.max(
            Math.min(actor.velocidad, 150),
            -80
        );
    }

    // Mantener el coche dentro del mundo.
    actor.x = Math.max(20, Math.min(MUNDO.anchura - 20, actor.x));
    actor.y = Math.max(20, Math.min(MUNDO.altura - 20, actor.y));
}

function dibujarCoche(actor, contexto) {
    const longitud = actor.datos.longitud;
    const anchura = actor.datos.anchura;

    contexto.save();
    contexto.translate(actor.x, actor.y);
    contexto.rotate(actor.angulo);

    // Sombra.
    contexto.fillStyle = "rgba(0, 0, 0, 0.28)";
    contexto.fillRect(
        -longitud / 2 + 4,
        -anchura / 2 + 5,
        longitud,
        anchura
    );

    // Ruedas.
    contexto.fillStyle = "#111111";
    contexto.fillRect(-19, -19, 13, 7);
    contexto.fillRect(10, -19, 13, 7);
    contexto.fillRect(-19, 12, 13, 7);
    contexto.fillRect(10, 12, 13, 7);

    // Carrocería.
    contexto.fillStyle = "#d63a32";
    contexto.beginPath();
    contexto.roundRect(
        -longitud / 2,
        -anchura / 2,
        longitud,
        anchura,
        8
    );
    contexto.fill();

    // Habitáculo.
    contexto.fillStyle = "#9bc4d8";
    contexto.beginPath();
    contexto.roundRect(-9, -11, 24, 22, 5);
    contexto.fill();

    // Capó / dirección del vehículo.
    contexto.fillStyle = "rgba(255,255,255,0.35)";
    contexto.fillRect(19, -10, 4, 20);

    // Faros.
    contexto.fillStyle = "#fff3a6";
    contexto.fillRect(24, -10, 4, 6);
    contexto.fillRect(24, 4, 4, 6);

    contexto.restore();
}

function crearCono(x, y) {
    return new Actor({
        x,
        y,
        radio: 12,
        etiquetas: ["obstaculo", "cono"],

        alDibujar(actor, contexto) {
            contexto.save();
            contexto.translate(actor.x, actor.y);

            contexto.fillStyle = "#f57c00";
            contexto.beginPath();
            contexto.moveTo(0, -15);
            contexto.lineTo(-10, 10);
            contexto.lineTo(10, 10);
            contexto.closePath();
            contexto.fill();

            contexto.fillStyle = "#ffffff";
            contexto.fillRect(-6, 0, 12, 4);

            contexto.fillStyle = "#333333";
            contexto.fillRect(-13, 10, 26, 5);

            contexto.restore();
        },

        alColisionar(actor, otro) {
            if (!otro.tiene("vehiculo")) {
                return;
            }

            const dx = otro.x - actor.x;
            const dy = otro.y - actor.y;
            const longitud = Math.hypot(dx, dy) || 1;

            otro.x += (dx / longitud) * 8;
            otro.y += (dy / longitud) * 8;
            otro.velocidad *= 0.55;
        }
    });
}

function actualizarVueltas(actor) {
    tiempoVuelta += 1 / 60;

    const cercaParteInferior = actor.y > PISTA.centroY + 300;
    if (cercaParteInferior) {
        pasoCheckpoint = true;
    }

    const cruzaMeta = (
        pasoCheckpoint &&
        Math.abs(actor.x - PISTA.centroX) < 45 &&
        actor.y > PISTA.centroY - PISTA.radioYExterior - 25 &&
        actor.y < PISTA.centroY - PISTA.radioYInterior + 25 &&
        Math.sin(actor.angulo) < 0.25
    );

    if (cruzaMeta && actor.datos.ultimoCruce !== true) {
        vueltas++;

        if (vueltas > 1) {
            if (mejorVuelta === null || tiempoVuelta < mejorVuelta) {
                mejorVuelta = tiempoVuelta;
            }
        }

        tiempoVuelta = 0;
        pasoCheckpoint = false;
        actor.datos.ultimoCruce = true;
    }

    if (Math.abs(actor.x - PISTA.centroX) > 80) {
        actor.datos.ultimoCruce = false;
    }
}

function formatearTiempo(segundos) {
    if (segundos === null) {
        return "--:--.--";
    }

    const minutos = Math.floor(segundos / 60);
    const resto = segundos % 60;

    return `${String(minutos).padStart(2, "0")}:${resto.toFixed(2).padStart(5, "0")}`;
}

escena.alIniciar = function (escenaActual) {
    coche = escenaActual.agregar(crearCoche());
    escenaActual.camara.seguir(coche);

    const conos = [
        [PISTA.centroX + 300, PISTA.centroY - 420],
        [PISTA.centroX + 470, PISTA.centroY - 320],
        [PISTA.centroX + 610, PISTA.centroY - 130],
        [PISTA.centroX - 420, PISTA.centroY + 390],
        [PISTA.centroX - 650, PISTA.centroY + 120]
    ];

    for (const [x, y] of conos) {
        escenaActual.agregar(crearCono(x, y));
    }
};

escena.alDibujarFondo = function (escenaActual, contexto) {
    dibujarPista(contexto);
};

escena.alDibujarInterfaz = function (escenaActual, contexto) {
    const velocidadKmh = Math.round(Math.abs(coche.velocidad) * 0.62);
    const anchura = motor.canvas.anchoLogico;
    const altura = motor.canvas.altoLogico;

    contexto.save();

    contexto.fillStyle = "rgba(0, 0, 0, 0.72)";
    contexto.beginPath();
    contexto.roundRect(20, 20, 285, 126, 14);
    contexto.fill();

    contexto.fillStyle = "white";
    contexto.font = "700 32px system-ui, sans-serif";
    contexto.fillText(`${velocidadKmh} km/h`, 38, 61);

    contexto.font = "16px system-ui, sans-serif";
    contexto.fillStyle = "#d8d8d8";
    contexto.fillText(
        coche.datos.sobreAsfalto ? "SUPERFICIE: ASFALTO" : "SUPERFICIE: CÉSPED",
        38,
        88
    );
    contexto.fillText(`VUELTA: ${Math.max(vueltas, 1)}`, 38, 113);
    contexto.fillText(`TIEMPO: ${formatearTiempo(tiempoVuelta)}`, 145, 113);
    contexto.fillText(`MEJOR: ${formatearTiempo(mejorVuelta)}`, 38, 137);

    const ayuda = "WASD / flechas · acelerar, frenar y girar";
    contexto.font = "15px system-ui, sans-serif";
    const anchoAyuda = contexto.measureText(ayuda).width + 32;

    contexto.fillStyle = "rgba(0, 0, 0, 0.64)";
    contexto.beginPath();
    contexto.roundRect(
        anchura - anchoAyuda - 20,
        altura - 55,
        anchoAyuda,
        35,
        10
    );
    contexto.fill();

    contexto.fillStyle = "white";
    contexto.fillText(
        ayuda,
        anchura - anchoAyuda - 4,
        altura - 32
    );

    contexto.restore();
};

motor.cargar(escena);
motor.iniciar();
