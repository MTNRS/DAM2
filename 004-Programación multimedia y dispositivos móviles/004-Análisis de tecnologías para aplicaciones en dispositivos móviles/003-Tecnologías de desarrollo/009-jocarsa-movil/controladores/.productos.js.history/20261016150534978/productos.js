let botonanadeproductos = document.querySelector("#anadeproducto")

botonanadeproductos.onclick = function(){
  let valor = document.querySelector("#nombreproducto").value
	localStorage.setItem("productos",valor)
}