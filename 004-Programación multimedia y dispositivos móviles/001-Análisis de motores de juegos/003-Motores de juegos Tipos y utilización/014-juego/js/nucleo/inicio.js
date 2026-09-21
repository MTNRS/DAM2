function inicio(){
	//console.log("Soy el inicio");
  contexto.clearRect(0,0,anchura,altura)
  jugador = new Nave(200,200,0,0,100);
  
  for(let i = 0;i<numerorocas;i++){
  	rocas.push(new Roca(
      Math.random()*anchura,
      Math.random()*altura
      ,Math.random()*Math.PI,0))
  }
  
  for(let i = 0;i<numeroestrellas;i++){
  	estrellas.push(new Estrella(
      Math.random()*anchura,
      Math.random()*altura,
      0,
      0,
      Math.random()))
  }
  temporizador = setTimeout("bucle()",1000);
}