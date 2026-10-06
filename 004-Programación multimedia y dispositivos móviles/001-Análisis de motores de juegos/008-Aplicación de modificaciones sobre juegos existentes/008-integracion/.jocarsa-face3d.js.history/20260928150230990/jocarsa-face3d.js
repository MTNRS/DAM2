import {
    FaceDetector,
    FilesetResolver
} from "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@latest";


export default class JocarsaFace3D {

    constructor(selector, opciones = {}) {

        this.contenedor =
            typeof selector === "string"
                ? document.querySelector(selector)
                : selector;

        if (!this.contenedor) {
            throw new Error("JocarsaFace3D: contenedor no encontrado");
        }


        /* CONFIGURACIÓN */

        this.config = {

            rotacionX: 28,
            rotacionY: 34,

            desplazamientoX: 38,
            desplazamientoY: 24,

            escalaZ: 0.16,

            suavizado: 0.10,

            mostrarCamara: true,
            mostrarEstado: true,

            capas: [
                null,
                "capas/cuerpo.png",
                "capas/cara.png",
                "capas/auriculares.png",
                "capas/pelo.png"
            ],

            ...opciones
        };


        this.faceDetector = null;

        this.referencia = null;

        this.objetivo = {
            x:0,
            y:0,
            z:0
        };

        this.actual = {
            x:0,
            y:0,
            z:0
        };


        this.crearDOM();
    }



    crearDOM(){

        this.raiz = document.createElement("div");

        this.raiz.className = "jocarsa-face3d";


        /* ESCENA */

        this.escena = document.createElement("div");

        this.escena.className =
            "jocarsa-face3d-escena";


        this.config.capas.forEach((imagen, i) => {

            const capa = document.createElement("div");

            capa.className =
                `jocarsa-face3d-capa jocarsa-face3d-capa-${i+1}`;

            if(imagen){

                capa.style.backgroundImage =
                    `url("${imagen}")`;

            }

            this.escena.appendChild(capa);

        });


        this.raiz.appendChild(this.escena);



        /* ESTADO */

        if(this.config.mostrarEstado){

            this.estado =
                document.createElement("div");

            this.estado.className =
                "jocarsa-face3d-estado";

            this.estado.textContent =
                "Cargando webcam y MediaPipe…";

            document.body.appendChild(this.estado);

        }



        /* WEBCAM */

        if(this.config.mostrarCamara){

            this.pip =
                document.createElement("div");

            this.pip.className =
                "jocarsa-face3d-pip";


            this.video =
                document.createElement("video");

            this.video.autoplay = true;
            this.video.muted = true;
            this.video.playsInline = true;


            this.canvas =
                document.createElement("canvas");

            this.canvas.width = 640;
            this.canvas.height = 480;


            this.pip.appendChild(this.video);
            this.pip.appendChild(this.canvas);

            document.body.appendChild(this.pip);

        }
        else{

            this.video =
                document.createElement("video");

            this.video.autoplay = true;
            this.video.muted = true;
            this.video.playsInline = true;

            this.video.style.display = "none";

            document.body.appendChild(this.video);


            this.canvas =
                document.createElement("canvas");

            this.canvas.width = 640;
            this.canvas.height = 480;

        }


        this.ctx =
            this.canvas.getContext("2d");


        this.contenedor.appendChild(this.raiz);
    }



    async iniciar(){

        /* WEBCAM */

        const stream =
            await navigator.mediaDevices.getUserMedia({

                video:{
                    width:{ideal:640},
                    height:{ideal:480},
                    facingMode:"user"
                },

                audio:false

            });


        this.video.srcObject = stream;

        await this.video.play();



        /* MEDIAPIPE */

        const vision =
            await FilesetResolver.forVisionTasks(

                "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@latest/wasm"

            );


        this.faceDetector =
            await FaceDetector.createFromOptions(

                vision,

                {

                    baseOptions:{

                        modelAssetPath:
                        "https://storage.googleapis.com/mediapipe-models/face_detector/blaze_face_short_range/float16/latest/blaze_face_short_range.tflite"

                    },

                    runningMode:"VIDEO",

                    minDetectionConfidence:0.5

                }

            );


        if(this.estado){

            this.estado.textContent =
                "Mueve la cabeza: izquierda/derecha, arriba/abajo y cerca/lejos";

        }


        requestAnimationFrame(
            t => this.loop(t)
        );
    }



    procesarCara(cara){

        const b = cara.boundingBox;


        const cx =
            b.originX +
            b.width / 2;

        const cy =
            b.originY +
            b.height / 2;


        const fx =
            cx /
            this.video.videoWidth;

        const fy =
            cy /
            this.video.videoHeight;


        const area =
            (b.width * b.height) /
            (
                this.video.videoWidth *
                this.video.videoHeight
            );


        if(!this.referencia){

            this.referencia = {

                x:fx,
                y:fy,
                area:area

            };

        }


        /*
        Inversión X/Y correspondiente
        al comportamiento actual.
        */

        this.objetivo.x =
            -(fx - this.referencia.x) * 2;

        this.objetivo.y =
            (fy - this.referencia.y) * 2;


        this.objetivo.z =
            Math.max(
                -1,
                Math.min(
                    1,
                    (area / this.referencia.area) - 1
                )
            );



        /* DEBUG */

        if(this.config.mostrarCamara){

            const sx =
                this.canvas.width /
                this.video.videoWidth;

            const sy =
                this.canvas.height /
                this.video.videoHeight;


            this.ctx.strokeStyle =
                "#7CFF72";

            this.ctx.lineWidth = 3;


            this.ctx.strokeRect(

                b.originX * sx,
                b.originY * sy,

                b.width * sx,
                b.height * sy

            );

        }

    }



    loop(t){

        this.ctx.clearRect(
            0,
            0,
            this.canvas.width,
            this.canvas.height
        );


        if(
            this.video.readyState >= 2 &&
            this.faceDetector
        ){

            const resultado =
                this.faceDetector.detectForVideo(
                    this.video,
                    t
                );


            if(resultado.detections.length){

                this.procesarCara(
                    resultado.detections[0]
                );

            }
            else{

                this.objetivo.x *= 0.96;
                this.objetivo.y *= 0.96;
                this.objetivo.z *= 0.96;

            }

        }



        /* SUAVIZADO */

        this.actual.x +=
            (this.objetivo.x - this.actual.x) *
            this.config.suavizado;

        this.actual.y +=
            (this.objetivo.y - this.actual.y) *
            this.config.suavizado;

        this.actual.z +=
            (this.objetivo.z - this.actual.z) *
            this.config.suavizado;



        /* TRANSFORMACIÓN */

        const rotY =
            -this.actual.x *
            this.config.rotacionY;

        const rotX =
            this.actual.y *
            this.config.rotacionX;


        const tx =
            this.actual.x *
            this.config.desplazamientoX;

        const ty =
            -this.actual.y *
            this.config.desplazamientoY;


        const escala =
            1 +
            this.actual.z *
            this.config.escalaZ;



        this.escena.style.transform = `

            translate3d(
                ${tx}px,
                ${ty}px,
                0
            )

            rotateX(${rotX}deg)

            rotateY(${rotY}deg)

            scale(${escala})

        `;



        requestAnimationFrame(
            tiempo => this.loop(tiempo)
        );

    }



    recalibrar(){

        this.referencia = null;

    }

}