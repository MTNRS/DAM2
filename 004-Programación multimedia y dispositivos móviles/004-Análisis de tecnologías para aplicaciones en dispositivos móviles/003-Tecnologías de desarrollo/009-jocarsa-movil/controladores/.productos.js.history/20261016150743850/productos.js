let botonanadeproductos = document.querySelector("#anadeproducto")

botonanadeproductos.onclick = function(){
  let valor = document.querySelector("#nombreproducto").value
  let datos = localStorage.getItem("productos")
  datos.push(valor)
	localStorage.setItem("productos",datos)
}