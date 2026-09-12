class JocarsaSerializador():
  def serializar(self,lista,delimitador=","):
    cadena = ""
    for elemento in  lista:
      cadena += elemento+delimitador
    return cadena

  def desserializar(self,cadena,delimitador=","):
    lista = cadena.split(delimitador)
    return lista

archivo = open("prueba.txt",'w')
frutas = ['manzanas','platanos','naranjas']
archivo.write(serializar(frutas))
archivo.close()

print(desserializar("manzana,pera,platano"))
