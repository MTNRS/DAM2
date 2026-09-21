def serializar(lista,delimitador=","):
  cadena = ""
  for elemento in  lista:
    cadena += elemento+delimitador
  return cadena

def desserializar(cadena,delimitador=","):
	lista = cadena.split(delimitador)
  return lista

archivo = open("prueba.txt",'w')
frutas = ['manzanas','platanos','naranjas']
archivo.write(serializar(frutas))
archivo.close()