onmessage = function(){
  let numero = 0.0000000054;
      for(let i = 0;i<10000000000;i++){
      	numero *= 1.00000000545
      }
      console.log("Ya he acabado");
	postMessage("soy el trabajador y te devuelvo un mensaje")
}
