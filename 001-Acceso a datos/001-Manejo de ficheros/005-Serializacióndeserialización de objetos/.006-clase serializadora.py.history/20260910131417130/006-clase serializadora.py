class JocarsaSerializador():
  def serializar(self,lista,delimitador=","):
    cadena = ""
    for elemento in  lista:
      cadena += elemento+delimitador
    return cadena

  def desserializar(self,cadena,delimitador=","):
    lista = cadena.split(delimitador)
    return lista

serial = JocarsaSerializador()
