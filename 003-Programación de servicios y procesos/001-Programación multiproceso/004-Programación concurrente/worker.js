let seres = []

let width = 1000
let height = 800



class Ser{

  constructor(){

    this.x =
      Math.random() * width

    this.y =
      Math.random() * height


    this.angulo =
      Math.random() *
      Math.PI *
      2


    this.hambre =
      Math.random() * 100

    this.energia =
      Math.random() * 100


    this.velocidad =
      1 + Math.random()


    // Sensores

    this.distanciaSensor = 30

    this.anguloSensor = 0.5

    this.radioDeteccion = 12

  }



  sensor(anguloSensor){

    let angulo =
      this.angulo +
      anguloSensor


    let sensorX =
      this.x +
      Math.cos(angulo) *
      this.distanciaSensor


    let sensorY =
      this.y +
      Math.sin(angulo) *
      this.distanciaSensor


    let distanciaMinima = Infinity


    for(let otro of seres){

      if(otro === this){
        continue
      }


      let dx =
        otro.x - sensorX

      let dy =
        otro.y - sensorY


      let distanciaCuadrado =
        dx * dx +
        dy * dy


      if(
        distanciaCuadrado <
        this.radioDeteccion *
        this.radioDeteccion
      ){

        let distancia =
          Math.sqrt(
            distanciaCuadrado
          )


        if(
          distancia <
          distanciaMinima
        ){

          distanciaMinima =
            distancia

        }

      }

    }


    return distanciaMinima

  }



  pensar(){

    let izquierda =
      this.sensor(
        -this.anguloSensor
      )


    let centro =
      this.sensor(0)


    let derecha =
      this.sensor(
        this.anguloSensor
      )



    if(centro !== Infinity){

      if(izquierda > derecha){

        this.angulo -= 0.12

      }else{

        this.angulo += 0.12

      }

    }



    if(izquierda !== Infinity){

      this.angulo += 0.05

    }



    if(derecha !== Infinity){

      this.angulo -= 0.05

    }

  }



  mover(){

    this.x +=
      Math.cos(this.angulo) *
      this.velocidad


    this.y +=
      Math.sin(this.angulo) *
      this.velocidad



    if(this.x < 0)
      this.x = width

    if(this.x > width)
      this.x = 0

    if(this.y < 0)
      this.y = height

    if(this.y > height)
      this.y = 0

  }

}



// Recibir mensajes

onmessage = function(event){

  let datos = event.data


  if(datos.tipo === "iniciar"){

    width = datos.width
    height = datos.height


    seres = []


    for(
      let i = 0;
      i < datos.cantidad;
      i++
    ){

      seres.push(
        new Ser()
      )

    }


    bucle()

  }



  if(datos.tipo === "resize"){

    width = datos.width
    height = datos.height

  }

}



function bucle(){

  // Array:
  //
  // x,y,x,y,x,y...
  //
  // 1000 seres =
  // 2000 números

  let posiciones =
    new Float32Array(
      seres.length * 2
    )


  let indice = 0


  for(let ser of seres){

    ser.pensar()

    ser.mover()


    posiciones[indice++] =
      ser.x

    posiciones[indice++] =
      ser.y

  }


  // Enviar al hilo principal.
  // Transferimos el buffer para evitar
  // copiar todos los datos.

  postMessage(
    posiciones,
    [posiciones.buffer]
  )


  setTimeout(
    bucle,
    0
  )

}