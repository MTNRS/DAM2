import os

class JocarsaSerializador():
  def serializar(self,lista,delimitador=","):
    cadena = ""
    for elemento in lista:
      cadena += str(elemento)+delimitador
    cadena = cadena[:-1]
    return cadena

  def desserializar(self,cadena,delimitador=","):
    lista = cadena.split(delimitador)
    return lista


class JocarsaBBDD:
  def __init__(self):
    self.instalacion = "/var/jocarsa-basededatos/"
    self.basededatos = ""
    self.tamanoRegistro = 512

  def creaBaseDatos(self,nombre):
    ruta = self.instalacion+nombre
    if not os.path.exists(ruta):
      os.mkdir(ruta)

  def usaBaseDatos(self,nombre):
    self.basededatos = nombre

  def creaTabla(self,nombre,esquema):
    archivo = open(self.instalacion+self.basededatos+"/"+nombre+".csv",'wb')
    archivo.close()

    archivo = open(self.instalacion+self.basededatos+"/"+nombre+".esquema",'w')
    archivo.write("id,activo,"+esquema)
    archivo.close()

    archivo = open(self.instalacion+self.basededatos+"/"+nombre+".idx",'w')
    archivo.close()

  def obtenerEsquema(self,tabla):
    archivo = open(self.instalacion+self.basededatos+"/"+tabla+".esquema",'r')
    esquema = archivo.read()
    archivo.close()

    serial = JocarsaSerializador()
    return serial.desserializar(esquema)

  def siguienteId(self,tabla):
    ruta = self.instalacion+self.basededatos+"/"+tabla+".idx"

    archivo = open(ruta,'r')
    ultimo = 0

    for linea in archivo:
      linea = linea.strip()
      if linea != "":
        partes = linea.split(",")
        ultimo = int(partes[0])

    archivo.close()

    return ultimo+1

  def insertarDatos(self,tabla,datos):
    serial = JocarsaSerializador()
    id = self.siguienteId(tabla)

    elementos = [id,1]+datos
    cadena = serial.serializar(elementos)

    datosRegistro = cadena.encode("utf-8")

    if len(datosRegistro) > self.tamanoRegistro-1:
      print("Error: el registro es demasiado grande")
      return

    registro = datosRegistro+b" "*(self.tamanoRegistro-1-len(datosRegistro))+b"\n"

    ruta = self.instalacion+self.basededatos+"/"+tabla+".csv"

    archivo = open(ruta,'ab')
    posicion = archivo.tell()
    archivo.write(registro)
    archivo.close()

    indice = open(self.instalacion+self.basededatos+"/"+tabla+".idx",'a')
    indice.write(str(id)+","+str(posicion)+"\n")
    indice.close()

    return id

  def buscarPosicion(self,tabla,id):
    archivo = open(self.instalacion+self.basededatos+"/"+tabla+".idx",'r')

    for linea in archivo:
      linea = linea.strip()
      if linea != "":
        partes = linea.split(",")
        if partes[0] == str(id):
          archivo.close()
          return int(partes[1])

    archivo.close()
    return -1

  def leerRegistro(self,tabla,id):
    posicion = self.buscarPosicion(tabla,id)

    if posicion == -1:
      return None

    ruta = self.instalacion+self.basededatos+"/"+tabla+".csv"

    archivo = open(ruta,'rb')
    archivo.seek(posicion)
    registro = archivo.read(self.tamanoRegistro)
    archivo.close()

    cadena = registro.decode("utf-8").rstrip("\n").rstrip()

    serial = JocarsaSerializador()
    elementos = serial.desserializar(cadena)

    if len(elementos) < 2:
      return None

    if elementos[1] == "0":
      return None

    return elementos

  def seleccionar(self,tabla,id):
    registro = self.leerRegistro(tabla,id)

    if registro == None:
      return None

    esquema = self.obtenerEsquema(tabla)
    resultado = {}

    for i in range(len(esquema)):
      resultado[esquema[i]] = registro[i]

    return resultado

  def listarTodo(self,tabla):
    esquema = self.obtenerEsquema(tabla)
    ruta = self.instalacion+self.basededatos+"/"+tabla+".csv"

    archivo = open(ruta,'rb')

    while True:
      registro = archivo.read(self.tamanoRegistro)

      if registro == b"":
        break

      cadena = registro.decode("utf-8").rstrip("\n").rstrip()

      if cadena != "":
        serial = JocarsaSerializador()
        elementos = serial.desserializar(cadena)

        if len(elementos) > 1 and elementos[1] == "1":
          resultado = {}

          for i in range(len(esquema)):
            resultado[esquema[i]] = elementos[i]

          print(resultado)

    archivo.close()

  def buscarColumna(self,tabla,columna,valor):
    esquema = self.obtenerEsquema(tabla)

    if columna not in esquema:
      return

    posicionColumna = esquema.index(columna)
    ruta = self.instalacion+self.basededatos+"/"+tabla+".csv"

    archivo = open(ruta,'rb')

    while True:
      registro = archivo.read(self.tamanoRegistro)

      if registro == b"":
        break

      cadena = registro.decode("utf-8").rstrip("\n").rstrip()

      if cadena != "":
        serial = JocarsaSerializador()
        elementos = serial.desserializar(cadena)

        if len(elementos) > 1:
          if elementos[1] == "1" and elementos[posicionColumna] == str(valor):
            resultado = {}

            for i in range(len(esquema)):
              resultado[esquema[i]] = elementos[i]

            print(resultado)

    archivo.close()

  def actualizar(self,tabla,id,datos):
    posicion = self.buscarPosicion(tabla,id)

    if posicion == -1:
      print("Error: registro no encontrado")
      return

    registroActual = self.leerRegistro(tabla,id)

    if registroActual == None:
      print("Error: registro no encontrado o eliminado")
      return

    serial = JocarsaSerializador()
    elementos = [id,1]+datos
    cadena = serial.serializar(elementos)

    datosRegistro = cadena.encode("utf-8")

    if len(datosRegistro) > self.tamanoRegistro-1:
      print("Error: el registro es demasiado grande")
      return

    registro = datosRegistro+b" "*(self.tamanoRegistro-1-len(datosRegistro))+b"\n"

    ruta = self.instalacion+self.basededatos+"/"+tabla+".csv"

    archivo = open(ruta,'r+b')
    archivo.seek(posicion)
    archivo.write(registro)
    archivo.close()

  def eliminar(self,tabla,id):
    posicion = self.buscarPosicion(tabla,id)

    if posicion == -1:
      print("Error: registro no encontrado")
      return

    ruta = self.instalacion+self.basededatos+"/"+tabla+".csv"

    archivo = open(ruta,'r+b')
    archivo.seek(posicion)
    registro = archivo.read(self.tamanoRegistro)

    cadena = registro.decode("utf-8").rstrip("\n").rstrip()

    serial = JocarsaSerializador()
    elementos = serial.desserializar(cadena)

    if len(elementos) < 2:
      archivo.close()
      return

    elementos[1] = "0"
    cadena = serial.serializar(elementos)

    datosRegistro = cadena.encode("utf-8")
    registro = datosRegistro+b" "*(self.tamanoRegistro-1-len(datosRegistro))+b"\n"

    archivo.seek(posicion)
    archivo.write(registro)
    archivo.close()


# EJEMPLO DE USO

conexion = JocarsaBBDD()

# conexion.creaBaseDatos("empresa")
conexion.usaBaseDatos("empresa")

# conexion.creaTabla("clientes","nombre,apellidos,telefono")

# id1 = conexion.insertarDatos("clientes",["Jose Vicente","Carratala",54354])
# id2 = conexion.insertarDatos("clientes",["Ana","Garcia",666666666])
# id3 = conexion.insertarDatos("clientes",["Juan","Lopez",777777777])

# conexion.actualizar( "clientes",2,["Ana","Garcia Martinez",999999999] )

# conexion.eliminar("clientes",3)

# print(conexion.seleccionar("clientes",2))

# conexion.buscarColumna("clientes","nombre","Ana")

conexion.listarTodo("clientes")