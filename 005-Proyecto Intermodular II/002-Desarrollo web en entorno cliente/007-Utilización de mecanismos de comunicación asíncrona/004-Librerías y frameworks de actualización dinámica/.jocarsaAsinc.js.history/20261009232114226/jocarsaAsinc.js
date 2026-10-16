function jocarsaAsinc(archivo, formato="json"){
        return fetch(archivo)
        .then(function(resultado){
          if(formato == "json"){
            return resultado.json()
          }else if(formato == "texto"){
            return resultado.text()
          }
        })
        .then(function(datos){
          return datos;
        })
      }