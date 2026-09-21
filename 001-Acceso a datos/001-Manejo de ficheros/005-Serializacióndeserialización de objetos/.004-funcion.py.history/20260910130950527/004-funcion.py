def serializar(lista,delimitador=","):
  cadena = ""
  for elemento in  lista:
    cadena += elemento+delimitador
  return cadena

archivo = open("prueba.txt",'w')
frutas = ['manzanas','platanos','naranjas']
cadena = ""
for fruta in frutas:
  cadena += fruta+","
archivo.write(cadena)
archivo.close()