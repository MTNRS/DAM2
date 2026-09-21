function inicio(){
	//console.log("Soy el inicio");
  contexto.clearRect(0,0,anchura,altura)
  jugador = new Nave(200,200,0,0);
  
  for(let i = 0;i<numerorocas;i++){
  	rocas.push(new Roca(
      Math.random()*anchura,
      Math.random()*altura
      ,0,0))
  }
  temporizador = setTimeout("bucle()",1000);
}