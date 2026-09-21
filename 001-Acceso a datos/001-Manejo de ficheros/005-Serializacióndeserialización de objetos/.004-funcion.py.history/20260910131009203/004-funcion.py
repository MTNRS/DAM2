def serializar(lista,delimitador=","):
  cadena = ""
  for elemento in  lista:
    cadena += elemento+delimitador
  return cadena

archivo = open("prueba.txt",'w')
frutas = ['manzanas','platanos','naranjas']
archivo.write(serializar(cadena))
archivo.close()