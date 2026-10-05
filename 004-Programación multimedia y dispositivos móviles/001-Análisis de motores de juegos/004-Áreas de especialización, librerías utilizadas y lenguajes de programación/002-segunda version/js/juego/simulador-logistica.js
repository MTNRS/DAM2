/*
 * JOCARSA - Simulador de nave logística
 *
 * Todo lo que aparece en este archivo pertenece al juego concreto.
 * El motor continúa sin saber qué es un camión, un muelle o una nave.
 *
 * El camión es una instancia de Jugador.
 * Los muelles, vehículos y obstáculos son instancias de Actor.
 */

const canvas = document.querySelector("#escenario");
const motor = new Motor(canvas);

const MUNDO = {
    anchura: 2400,
    altura: 1500
};

const NAVE = {
    x: 220,
    y: 130,
    anchura: 1960,
    altura: 430
};

const PATIO = {
    x: 120,
    y: 560,
    anchura: 2160,
    altura: 820
};

const CONFIGURACION_MUELLES = {
    cantidad: 8,
    inicioX: 390,
    separacion: 215,
    y: NAVE.y + NAVE.altura,
    anchura: 105,
    profundidad: 150
};

const escena = new Escena({
    camara: new Camara({
        suavizado: 4
    })
});

let camion = null;
let muelles = [];
let muelleObjetivo = null;
let misionCompletada = false;
let tiempoMision = 0;
let mensajeEstado = "Dirígete al muelle asignado";

function limitar(valor, minimo, maximo) {
    return Math.max(minimo, Math.min(maximo, valor));
}

function crearMuelle(numero, x, y) {
    return new Actor({
        x,
        y,
        radio: 44,
        etiquetas: ["muelle"],
        datos: {
            numero,
            anchura: CONFIGURACION_MUELLES.anchura,
            profundidad: CONFIGURACION_MUELLES.profundidad,
            ocupado: false,
            objetivo: false
        },

        alDibujar(actor, contexto) {
            dibujarMuelle(actor, contexto);
        }
    });
}

function dibujarMuelle(actor, contexto) {
    const ancho = actor.datos.anchura;
    const profundidad = actor.datos.profundidad;

    contexto.save();

    // Zona de maniobra del muelle.
    contexto.fillStyle = actor.datos.objetivo
        ? "rgba(255, 213, 0, 0.20)"
        : "rgba(255, 255, 255, 0.035)";
    contexto.fillRect(
        actor.x - ancho / 2,
        actor.y,
        ancho,
        profundidad
    );

    contexto.strokeStyle = actor.datos.objetivo ? "#ffd500" : "#e6e6e6";
    contexto.lineWidth = actor.datos.objetivo ? 6 : 3;
    contexto.setLineDash(actor.datos.objetivo ? [18, 10] : []);
    contexto.strokeRect(
        actor.x - ancho / 2,
        actor.y,
        ancho,
        profundidad
    );
    contexto.setLineDash([]);

    // Puerta del muelle en la fachada.
    contexto.fillStyle = "#25292c";
    contexto.fillRect(
        actor.x - 43,
        actor.y - 13,
        86,
        20
    );

    contexto.fillStyle = "#111315";
    contexto.fillRect(
        actor.x - 34,
        actor.y - 7,
        68,
        12
    );

    // Protectores de goma.
    contexto.fillStyle = "#111111";
    contexto.fillRect(actor.x - 48, actor.y - 5, 9, 18);
    contexto.fillRect(actor.x + 39, actor.y - 5, 9, 18);

    // Número del muelle.
    contexto.fillStyle = actor.datos.objetivo ? "#ffd500" : "#ffffff";
    contexto.font = "700 28px Arial";
    contexto.textAlign = "center";
    contexto.fillText(
        String(actor.datos.numero).padStart(2, "0"),
        actor.x,
        actor.y - 30
    );

    if (actor.datos.objetivo && !misionCompletada) {
        contexto.font = "700 16px Arial";
        contexto.fillText("DESTINO", actor.x, actor.y + profundidad - 16);
    }

    contexto.restore();
}

function crearCamion() {
    return new Jugador({
        x: 2050,
        y: 1210,
        angulo: Math.PI,
        radio: 30,
        etiquetas: ["jugador", "vehiculo", "camion"],

        datos: {
            velocidadMaxima: 250,
            velocidadMarchaAtras: -105,
            aceleracion: 125,
            frenada: 185,
            rozamiento: 0.975,
            giro: 1.35,
            longitud: 104,
            anchura: 42,
            remolqueLongitud: 145,
            remolqueAnchura: 46,
            anguloRemolque: Math.PI,
            distanciaEnganche: 105,
            distanciaEjesRemolque: 105
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
                girarVehiculo(actor, -1, deltaTime);
            },

            derecha(actor, deltaTime) {
                girarVehiculo(actor, 1, deltaTime);
            }
        },

        alActualizar(actor, deltaTime) {
            actualizarFisicaCamion(actor, deltaTime);
            actualizarRemolque(actor, deltaTime);
            comprobarAtraque(actor);
        },

        alDibujar(actor, contexto) {
            dibujarCamion(actor, contexto);
        }
    });
}

function girarVehiculo(actor, direccion, deltaTime) {
    const intensidad = limitar(Math.abs(actor.velocidad) / 90, 0, 1);

    if (intensidad < 0.03) {
        return;
    }

    const sentido = actor.velocidad >= 0 ? 1 : -1;

    actor.angulo += (
        direccion *
        actor.datos.giro *
        intensidad *
        sentido *
        deltaTime
    );
}

function actualizarFisicaCamion(actor, deltaTime) {
    actor.velocidad = limitar(
        actor.velocidad,
        actor.datos.velocidadMarchaAtras,
        actor.datos.velocidadMaxima
    );

    const rozamiento = Math.pow(actor.datos.rozamiento, deltaTime * 60);
    actor.velocidad *= rozamiento;

    if (Math.abs(actor.velocidad) < 0.25) {
        actor.velocidad = 0;
    }

    actor.vx = Math.cos(actor.angulo) * actor.velocidad;
    actor.vy = Math.sin(actor.angulo) * actor.velocidad;

    // Límites del recinto.
    actor.x = limitar(actor.x, 80, MUNDO.anchura - 80);
    actor.y = limitar(actor.y, NAVE.y + NAVE.altura + 90, MUNDO.altura - 80);
}

function actualizarRemolque(actor, deltaTime) {
    const engancheX = actor.x - Math.cos(actor.angulo) * actor.datos.distanciaEnganche;
    const engancheY = actor.y - Math.sin(actor.angulo) * actor.datos.distanciaEnganche;

    const diferencia = normalizarAngulo(
        actor.angulo - actor.datos.anguloRemolque
    );

    const respuesta = limitar(3.2 * deltaTime, 0, 1);
    actor.datos.anguloRemolque += diferencia * respuesta;

    actor.datos.remolqueX = (
        engancheX -
        Math.cos(actor.datos.anguloRemolque) * actor.datos.remolqueLongitud * 0.43
    );

    actor.datos.remolqueY = (
        engancheY -
        Math.sin(actor.datos.anguloRemolque) * actor.datos.remolqueLongitud * 0.43
    );
}

function normalizarAngulo(angulo) {
    while (angulo > Math.PI) {
        angulo -= Math.PI * 2;
    }

    while (angulo < -Math.PI) {
        angulo += Math.PI * 2;
    }

    return angulo;
}

function dibujarCamion(actor, contexto) {
    dibujarRemolque(actor, contexto);

    contexto.save();
    contexto.translate(actor.x, actor.y);
    contexto.rotate(actor.angulo);

    // Sombra de la cabeza tractora.
    contexto.fillStyle = "rgba(0, 0, 0, 0.25)";
    contexto.fillRect(-50, -17, 106, 43);

    // Ruedas.
    contexto.fillStyle = "#101010";
    contexto.fillRect(-32, -25, 18, 8);
    contexto.fillRect(-32, 17, 18, 8);
    contexto.fillRect(25, -25, 18, 8);
    contexto.fillRect(25, 17, 18, 8);

    // Cabina.
    contexto.fillStyle = "#1976d2";
    contexto.beginPath();
    contexto.roundRect(-48, -21, 96, 42, 8);
    contexto.fill();

    // Parabrisas.
    contexto.fillStyle = "#a9d7ea";
    contexto.fillRect(21, -16, 18, 32);

    // Quinta rueda / enganche.
    contexto.fillStyle = "#202326";
    contexto.beginPath();
    contexto.arc(-43, 0, 12, 0, Math.PI * 2);
    contexto.fill();

    // Faros delanteros.
    contexto.fillStyle = "#fff4a3";
    contexto.fillRect(44, -15, 5, 9);
    contexto.fillRect(44, 6, 5, 9);

    contexto.restore();
}

function dibujarRemolque(actor, contexto) {
    const x = actor.datos.remolqueX ?? actor.x - 150;
    const y = actor.datos.remolqueY ?? actor.y;
    const angulo = actor.datos.anguloRemolque;
    const longitud = actor.datos.remolqueLongitud;
    const anchura = actor.datos.remolqueAnchura;

    contexto.save();
    contexto.translate(x, y);
    contexto.rotate(angulo);

    contexto.fillStyle = "rgba(0, 0, 0, 0.22)";
    contexto.fillRect(
        -longitud / 2 + 5,
        -anchura / 2 + 6,
        longitud,
        anchura
    );

    contexto.fillStyle = "#eeeeea";
    contexto.fillRect(
        -longitud / 2,
        -anchura / 2,
        longitud,
        anchura
    );

    contexto.strokeStyle = "#a7aaab";
    contexto.lineWidth = 3;
    contexto.strokeRect(
        -longitud / 2,
        -anchura / 2,
        longitud,
        anchura
    );

    // Puertas traseras: están en la parte negativa del eje longitudinal.
    contexto.strokeStyle = "#777b7d";
    contexto.lineWidth = 2;
    contexto.beginPath();
    contexto.moveTo(-longitud / 2 + 4, -anchura / 2 + 3);
    contexto.lineTo(-longitud / 2 + 4, anchura / 2 - 3);
    contexto.stroke();

    // Ruedas del semirremolque.
    contexto.fillStyle = "#111111";
    contexto.fillRect(-48, -anchura / 2 - 5, 25, 7);
    contexto.fillRect(-48, anchura / 2 - 2, 25, 7);
    contexto.fillRect(-15, -anchura / 2 - 5, 25, 7);
    contexto.fillRect(-15, anchura / 2 - 2, 25, 7);

    contexto.fillStyle = "#1976d2";
    contexto.font = "700 18px Arial";
    contexto.textAlign = "center";
    contexto.textBaseline = "middle";
    contexto.fillText("JOCARSA LOGISTICS", 12, 0);

    contexto.restore();
}

function comprobarAtraque(actor) {
    if (!muelleObjetivo || misionCompletada) {
        return;
    }

    const trasera = obtenerCentroTraseraRemolque(actor);
    const dx = trasera.x - muelleObjetivo.x;
    const dy = trasera.y - (muelleObjetivo.y + 12);
    const distancia = Math.hypot(dx, dy);

    // Los muelles están en la pared superior del patio. El remolque debe
    // quedar aproximadamente perpendicular a la fachada y con poca velocidad.
    const anguloRemolque = normalizarAngulo(actor.datos.anguloRemolque);
    const orientacionCorrecta = Math.abs(Math.sin(anguloRemolque)) > 0.90;
    const velocidadCorrecta = Math.abs(actor.velocidad) < 9;
    const posicionCorrecta = distancia < 38;

    if (posicionCorrecta && orientacionCorrecta && velocidadCorrecta) {
        misionCompletada = true;
        muelleObjetivo.datos.ocupado = true;
        mensajeEstado = "¡Atraque completado!";
        actor.velocidad = 0;
        actor.vx = 0;
        actor.vy = 0;
    } else if (distancia < 125) {
        if (!orientacionCorrecta) {
            mensajeEstado = "Alinea el remolque con el muelle";
        } else if (!velocidadCorrecta) {
            mensajeEstado = "Reduce la velocidad para atracar";
        } else {
            mensajeEstado = "Continúa marcha atrás lentamente";
        }
    } else {
        mensajeEstado = "Dirígete al muelle asignado";
    }
}

function obtenerCentroTraseraRemolque(actor) {
    const longitud = actor.datos.remolqueLongitud;

    return {
        x: actor.datos.remolqueX - Math.cos(actor.datos.anguloRemolque) * longitud / 2,
        y: actor.datos.remolqueY - Math.sin(actor.datos.anguloRemolque) * longitud / 2
    };
}

function crearVehiculoAparcado(x, y, angulo, color) {
    return new Actor({
        x,
        y,
        angulo,
        radio: 30,
        etiquetas: ["vehiculo", "decoracion"],
        datos: {
            color
        },

        alDibujar(actor, contexto) {
            contexto.save();
            contexto.translate(actor.x, actor.y);
            contexto.rotate(actor.angulo);

            contexto.fillStyle = "rgba(0, 0, 0, 0.20)";
            contexto.fillRect(-42, -17, 88, 39);

            contexto.fillStyle = actor.datos.color;
            contexto.beginPath();
            contexto.roundRect(-42, -18, 84, 36, 7);
            contexto.fill();

            contexto.fillStyle = "#a9d7ea";
            contexto.fillRect(17, -13, 14, 26);

            contexto.restore();
        }
    });
}

function dibujarRecinto(contexto) {
    // Exterior del recinto.
    contexto.fillStyle = "#8b908c";
    contexto.fillRect(0, 0, MUNDO.anchura, MUNDO.altura);

    // Césped perimetral.
    contexto.fillStyle = "#637d55";
    contexto.fillRect(0, 0, MUNDO.anchura, 95);
    contexto.fillRect(0, 1400, MUNDO.anchura, 100);
    contexto.fillRect(0, 0, 80, MUNDO.altura);
    contexto.fillRect(2320, 0, 80, MUNDO.altura);

    // Patio de maniobras.
    contexto.fillStyle = "#565b5e";
    contexto.fillRect(PATIO.x, PATIO.y, PATIO.anchura, PATIO.altura);

    dibujarMarcasPatio(contexto);
    dibujarNave(contexto);
    dibujarAcceso(contexto);
}

function dibujarNave(contexto) {
    // Sombra.
    contexto.fillStyle = "rgba(0, 0, 0, 0.25)";
    contexto.fillRect(NAVE.x + 14, NAVE.y + 18, NAVE.anchura, NAVE.altura);

    // Edificio.
    contexto.fillStyle = "#d9dcda";
    contexto.fillRect(NAVE.x, NAVE.y, NAVE.anchura, NAVE.altura);

    // Cubierta simulada.
    contexto.fillStyle = "#bcc1bf";
    for (let x = NAVE.x; x < NAVE.x + NAVE.anchura; x += 80) {
        contexto.fillRect(x, NAVE.y, 38, NAVE.altura - 28);
    }

    // Franja de fachada.
    contexto.fillStyle = "#343a3d";
    contexto.fillRect(NAVE.x, NAVE.y + NAVE.altura - 28, NAVE.anchura, 28);

    // Oficinas.
    contexto.fillStyle = "#ecefed";
    contexto.fillRect(NAVE.x + 35, NAVE.y + 40, 245, 130);

    contexto.fillStyle = "#72a6bb";
    for (let i = 0; i < 4; i++) {
        contexto.fillRect(NAVE.x + 55 + i * 54, NAVE.y + 68, 38, 42);
    }

    contexto.fillStyle = "#303638";
    contexto.font = "700 34px Arial";
    contexto.textAlign = "left";
    contexto.fillText("JOCARSA LOGISTICS", NAVE.x + 340, NAVE.y + 90);

    contexto.font = "18px Arial";
    contexto.fillStyle = "#606567";
    contexto.fillText("CENTRO DE DISTRIBUCIÓN", NAVE.x + 342, NAVE.y + 122);
}

function dibujarMarcasPatio(contexto) {
    contexto.save();

    // Líneas de circulación.
    contexto.strokeStyle = "rgba(255, 255, 255, 0.55)";
    contexto.lineWidth = 4;
    contexto.setLineDash([28, 22]);

    contexto.beginPath();
    contexto.moveTo(250, 1130);
    contexto.lineTo(2140, 1130);
    contexto.stroke();

    contexto.setLineDash([]);

    // Flechas de circulación.
    contexto.fillStyle = "rgba(255, 255, 255, 0.55)";
    for (let x = 500; x < 2100; x += 420) {
        contexto.beginPath();
        contexto.moveTo(x + 45, 1129);
        contexto.lineTo(x + 15, 1114);
        contexto.lineTo(x + 15, 1144);
        contexto.closePath();
        contexto.fill();
    }

    // Zona rayada de seguridad junto a la nave.
    contexto.strokeStyle = "rgba(255, 213, 0, 0.38)";
    contexto.lineWidth = 5;

    for (let x = 250; x < 2200; x += 55) {
        contexto.beginPath();
        contexto.moveTo(x, 580);
        contexto.lineTo(x + 45, 625);
        contexto.stroke();
    }

    contexto.restore();
}

function dibujarAcceso(contexto) {
    contexto.fillStyle = "#34393b";
    contexto.fillRect(1960, 1320, 300, 80);

    contexto.fillStyle = "#e8e8e8";
    contexto.fillRect(2025, 1328, 5, 65);
    contexto.fillRect(2190, 1328, 5, 65);

    contexto.fillStyle = "#f0c419";
    contexto.font = "700 18px Arial";
    contexto.textAlign = "center";
    contexto.fillText("ACCESO CAMIONES", 2110, 1370);
}

function dibujarFlechaObjetivo(contexto) {
    if (!muelleObjetivo || misionCompletada) {
        return;
    }

    const x = muelleObjetivo.x;
    const y = muelleObjetivo.y + 215;
    const pulso = 1 + Math.sin(performance.now() / 180) * 0.12;

    contexto.save();
    contexto.translate(x, y);
    contexto.scale(pulso, pulso);

    contexto.fillStyle = "#ffd500";
    contexto.beginPath();
    contexto.moveTo(0, -30);
    contexto.lineTo(-22, 2);
    contexto.lineTo(-8, 2);
    contexto.lineTo(-8, 30);
    contexto.lineTo(8, 30);
    contexto.lineTo(8, 2);
    contexto.lineTo(22, 2);
    contexto.closePath();
    contexto.fill();

    contexto.restore();
}

function dibujarInterfaz(contexto) {
    const anchura = motor.canvas.anchoLogico;
    const altura = motor.canvas.altoLogico;
    const velocidadKmh = Math.round(Math.abs(camion.velocidad) * 0.32);

    contexto.save();

    contexto.fillStyle = "rgba(17, 21, 23, 0.88)";
    contexto.beginPath();
    contexto.roundRect(20, 20, 390, 164, 14);
    contexto.fill();

    contexto.fillStyle = "#ffd500";
    contexto.font = "700 18px Arial";
    contexto.fillText("ORDEN DE MUELLE", 40, 50);

    contexto.fillStyle = "#ffffff";
    contexto.font = "700 44px Arial";
    contexto.fillText(
        `MUELLE ${String(muelleObjetivo.datos.numero).padStart(2, "0")}`,
        40,
        96
    );

    contexto.font = "18px Arial";
    contexto.fillStyle = misionCompletada ? "#7ee081" : "#d8d8d8";
    contexto.fillText(mensajeEstado, 40, 128);

    contexto.fillStyle = "#b9bec0";
    contexto.font = "16px Arial";
    contexto.fillText(`${velocidadKmh} km/h`, 40, 158);
    contexto.fillText(`Tiempo: ${formatearTiempo(tiempoMision)}`, 145, 158);

    const ayuda = "WASD / flechas · conduce y maniobra marcha atrás";
    contexto.font = "15px Arial";
    const anchoAyuda = contexto.measureText(ayuda).width + 34;

    contexto.fillStyle = "rgba(17, 21, 23, 0.78)";
    contexto.beginPath();
    contexto.roundRect(
        anchura - anchoAyuda - 20,
        altura - 58,
        anchoAyuda,
        38,
        10
    );
    contexto.fill();

    contexto.fillStyle = "#ffffff";
    contexto.fillText(
        ayuda,
        anchura - anchoAyuda - 3,
        altura - 33
    );

    if (misionCompletada) {
        contexto.fillStyle = "rgba(17, 21, 23, 0.90)";
        contexto.beginPath();
        contexto.roundRect(
            anchura / 2 - 230,
            30,
            460,
            86,
            14
        );
        contexto.fill();

        contexto.fillStyle = "#7ee081";
        contexto.font = "700 28px Arial";
        contexto.textAlign = "center";
        contexto.fillText("ATRAQUE COMPLETADO", anchura / 2, 68);

        contexto.fillStyle = "#ffffff";
        contexto.font = "16px Arial";
        contexto.fillText(
            `Muelle ${String(muelleObjetivo.datos.numero).padStart(2, "0")} · ${formatearTiempo(tiempoMision)}`,
            anchura / 2,
            96
        );
    }

    contexto.restore();
}

function formatearTiempo(segundos) {
    const minutos = Math.floor(segundos / 60);
    const resto = segundos % 60;

    return `${String(minutos).padStart(2, "0")}:${resto.toFixed(1).padStart(4, "0")}`;
}

escena.alIniciar = function (escenaActual) {
    camion = escenaActual.agregar(crearCamion());
    escenaActual.camara.seguir(camion);

    for (let i = 0; i < CONFIGURACION_MUELLES.cantidad; i++) {
        const numero = i + 1;
        const x = CONFIGURACION_MUELLES.inicioX + i * CONFIGURACION_MUELLES.separacion;
        const muelle = escenaActual.agregar(
            crearMuelle(numero, x, CONFIGURACION_MUELLES.y)
        );

        muelles.push(muelle);
    }

    // Se asigna una orden distinta en cada carga del simulador.
    muelleObjetivo = muelles[Math.floor(Math.random() * muelles.length)];
    muelleObjetivo.datos.objetivo = true;

    // Vehículos de ambientación. Son Actor genéricos, no clases especializadas.
    escenaActual.agregar(crearVehiculoAparcado(420, 1000, 0, "#ef6c00"));
    escenaActual.agregar(crearVehiculoAparcado(650, 1260, Math.PI, "#455a64"));
    escenaActual.agregar(crearVehiculoAparcado(980, 1010, 0, "#7b1fa2"));
    escenaActual.agregar(crearVehiculoAparcado(1450, 1260, Math.PI, "#388e3c"));
};

escena.alActualizar = function (escenaActual, deltaTime) {
    if (!misionCompletada) {
        tiempoMision += deltaTime;
    }
};

escena.alDibujarFondo = function (escenaActual, contexto) {
    dibujarRecinto(contexto);
    dibujarFlechaObjetivo(contexto);
};

escena.alDibujarInterfaz = function (escenaActual, contexto) {
    dibujarInterfaz(contexto);
};

motor.cargar(escena);
motor.iniciar();
