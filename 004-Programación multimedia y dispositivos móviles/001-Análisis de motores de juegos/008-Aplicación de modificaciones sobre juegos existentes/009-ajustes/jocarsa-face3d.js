import { FaceDetector, FilesetResolver } from "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@latest";

export default class JocarsaFace3D {
    constructor(selector, opciones = {}) {
        this.contenedor = typeof selector === "string" ? document.querySelector(selector) : selector;
        if (!this.contenedor) throw new Error("JocarsaFace3D: contenedor no encontrado");
        this.config = {
            rotacionX:28, rotacionY:34,
            desplazamientoX:38, desplazamientoY:24,
            escalaZ:0.16,
            profundidad:1.0,
            suavizado:0.035,
            suavizadoDetector:0.12,
            zonaMuerta:0.012,
            mostrarCamara:true, mostrarEstado:true,
            svg:"avatar.svg",
            capas:[
                ["avatar-back-hair"],
                ["avatar-body"],
                ["avatar-head"],
                ["avatar-face-base","avatar-eyes-default","avatar-eyes-2","avatar-eyes-3","avatar-eyes-4"],
                ["avatar-front-hair"]
            ],
            ojos:"avatar-eyes-default",
            ...opciones
        };
        this.faceDetector=null; this.referencia=null;
        this.objetivo={x:0,y:0,z:0};
        this.filtrado={x:0,y:0,z:0};
        this.actual={x:0,y:0,z:0};
        this.crearDOM();
    }

    crearDOM(){
        this.raiz=document.createElement("div"); this.raiz.className="jocarsa-face3d";
        this.escena=document.createElement("div"); this.escena.className="jocarsa-face3d-escena";
        this.raiz.appendChild(this.escena);
        if(this.config.mostrarEstado){
            this.estado=document.createElement("div"); this.estado.className="jocarsa-face3d-estado";
            this.estado.textContent="Cargando SVG, webcam y MediaPipe…"; document.body.appendChild(this.estado);
        }
        if(this.config.mostrarCamara){
            this.pip=document.createElement("div"); this.pip.className="jocarsa-face3d-pip";
            this.video=document.createElement("video"); this.video.autoplay=true; this.video.muted=true; this.video.playsInline=true;
            this.canvas=document.createElement("canvas"); this.canvas.width=640; this.canvas.height=480;
            this.pip.append(this.video,this.canvas); document.body.appendChild(this.pip);
        }else{
            this.video=document.createElement("video"); this.video.autoplay=true; this.video.muted=true; this.video.playsInline=true; this.video.style.display="none"; document.body.appendChild(this.video);
            this.canvas=document.createElement("canvas"); this.canvas.width=640; this.canvas.height=480;
        }
        this.ctx=this.canvas.getContext("2d"); this.contenedor.appendChild(this.raiz);
    }

    async cargarSVG(){
        const texto=await (await fetch(this.config.svg)).text();
        const doc=new DOMParser().parseFromString(texto,"image/svg+xml");
        const original=doc.documentElement;
        if(original.querySelector("parsererror")) throw new Error("No se pudo interpretar avatar.svg");
        this.config.capas.forEach((ids,i)=>{
            const plano=document.createElement("div");
            plano.className=`jocarsa-face3d-capa jocarsa-face3d-capa-${i+1}`;
            const profundidades=[-120,-60,0,60,120];
            plano.style.transform=`translateZ(${profundidades[i] * this.config.profundidad}px)`;
            const svg=document.createElementNS("http://www.w3.org/2000/svg","svg");
            ["viewBox","width","height","preserveAspectRatio"].forEach(a=>{ if(original.hasAttribute(a)) svg.setAttribute(a,original.getAttribute(a)); });
            svg.setAttribute("preserveAspectRatio","xMidYMid meet");
            const defs=original.querySelector("defs"); if(defs) svg.appendChild(document.importNode(defs,true));
            ids.forEach(id=>{ const g=original.querySelector(`#${CSS.escape(id)}`); if(g) svg.appendChild(document.importNode(g,true)); });
            plano.appendChild(svg); this.escena.appendChild(plano);
        });
        this.setOjos(this.config.ojos);
    }

    setOjos(id){
        const ojos=this.escena.querySelectorAll('g[id^="avatar-eyes-"]');
        ojos.forEach(g=>{
            const visible=g.id===id;
            g.style.setProperty("display", visible ? "inline" : "none", "important");
            g.style.setProperty("visibility", visible ? "visible" : "hidden", "important");
            g.style.setProperty("opacity", visible ? "1" : "0", "important");
        });
        this.config.ojos=id;
    }

    setProfundidad(multiplicador){
        this.config.profundidad=Number(multiplicador);
        const profundidades=[-120,-60,0,60,120];
        this.escena.querySelectorAll(".jocarsa-face3d-capa").forEach((capa,i)=>{
            capa.style.transform=`translateZ(${profundidades[i] * this.config.profundidad}px)`;
        });
    }

    async iniciar(){
        await this.cargarSVG();
        const stream=await navigator.mediaDevices.getUserMedia({video:{width:{ideal:640},height:{ideal:480},facingMode:"user"},audio:false});
        this.video.srcObject=stream; await this.video.play();
        const vision=await FilesetResolver.forVisionTasks("https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@latest/wasm");
        this.faceDetector=await FaceDetector.createFromOptions(vision,{baseOptions:{modelAssetPath:"https://storage.googleapis.com/mediapipe-models/face_detector/blaze_face_short_range/float16/latest/blaze_face_short_range.tflite"},runningMode:"VIDEO",minDetectionConfidence:0.5});
        if(this.estado) this.estado.textContent="Mueve la cabeza: izquierda/derecha, arriba/abajo y cerca/lejos";
        requestAnimationFrame(t=>this.loop(t));
    }

    procesarCara(cara){
        const b=cara.boundingBox, cx=b.originX+b.width/2, cy=b.originY+b.height/2;
        const fx=cx/this.video.videoWidth, fy=cy/this.video.videoHeight;
        const area=(b.width*b.height)/(this.video.videoWidth*this.video.videoHeight);
        if(!this.referencia) this.referencia={x:fx,y:fy,area};
        let nx=-(fx-this.referencia.x)*2;
        let ny=(fy-this.referencia.y)*2;
        let nz=Math.max(-1,Math.min(1,(area/this.referencia.area)-1));

        // Dead zone: tiny detector changes are webcam noise, not intentional movement.
        const dz=this.config.zonaMuerta;
        if(Math.abs(nx-this.filtrado.x)<dz) nx=this.filtrado.x;
        if(Math.abs(ny-this.filtrado.y)<dz) ny=this.filtrado.y;
        if(Math.abs(nz-this.filtrado.z)<dz*2) nz=this.filtrado.z;

        // First low-pass stage directly on MediaPipe measurements.
        const sd=this.config.suavizadoDetector;
        this.filtrado.x+=(nx-this.filtrado.x)*sd;
        this.filtrado.y+=(ny-this.filtrado.y)*sd;
        this.filtrado.z+=(nz-this.filtrado.z)*sd;

        this.objetivo.x=this.filtrado.x;
        this.objetivo.y=this.filtrado.y;
        this.objetivo.z=this.filtrado.z;
        if(this.config.mostrarCamara){
            const sx=this.canvas.width/this.video.videoWidth, sy=this.canvas.height/this.video.videoHeight;
            this.ctx.strokeStyle="#7CFF72"; this.ctx.lineWidth=3;
            this.ctx.strokeRect(b.originX*sx,b.originY*sy,b.width*sx,b.height*sy);
        }
    }

    loop(t){
        this.ctx.clearRect(0,0,this.canvas.width,this.canvas.height);
        if(this.video.readyState>=2 && this.faceDetector){
            const resultado=this.faceDetector.detectForVideo(this.video,t);
            if(resultado.detections.length) this.procesarCara(resultado.detections[0]);
            else { this.objetivo.x*=.96; this.objetivo.y*=.96; this.objetivo.z*=.96; }
        }
        this.actual.x+=(this.objetivo.x-this.actual.x)*this.config.suavizado;
        this.actual.y+=(this.objetivo.y-this.actual.y)*this.config.suavizado;
        this.actual.z+=(this.objetivo.z-this.actual.z)*this.config.suavizado;
        const rotY=-this.actual.x*this.config.rotacionY, rotX=this.actual.y*this.config.rotacionX;
        const tx=this.actual.x*this.config.desplazamientoX, ty=-this.actual.y*this.config.desplazamientoY;
        const escala=1+this.actual.z*this.config.escalaZ;
        this.escena.style.transform=`translate3d(${tx}px,${ty}px,0) rotateX(${rotX}deg) rotateY(${rotY}deg) scale(${escala})`;
        requestAnimationFrame(tt=>this.loop(tt));
    }
    recalibrar(){
        this.referencia=null;
        this.objetivo={x:0,y:0,z:0};
        this.filtrado={x:0,y:0,z:0};
        this.actual={x:0,y:0,z:0};
    }
}
