let botonanadeproductos = document.querySelector("#anadeproducto");

botonanadeproductos.onclick = function(){
    let valor = document.querySelector("#nombreproducto").value;

    let basededatos = localStorage.getItem("productos")
        ? JSON.parse(localStorage.getItem("productos"))
        : [];

    basededatos.push(valor);

    localStorage.setItem("productos", JSON.stringify(basededatos));

    document.querySelector("#nombreproducto").value = "";
};