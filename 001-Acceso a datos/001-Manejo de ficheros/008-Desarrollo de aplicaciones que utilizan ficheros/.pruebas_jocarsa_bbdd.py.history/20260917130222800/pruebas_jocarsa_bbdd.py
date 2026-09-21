import os
import shutil
import tempfile
from contextlib import redirect_stdout
from io import StringIO
import JocarsaBBDD as jocarsa_bbdd

# Ajusta este import al nombre real del archivo que contiene las clases.
# Ejemplo: from jocarsa_bbdd import JocarsaSerializador, JocarsaBBDD
try:
  from jocarsa_bbdd import JocarsaSerializador, JocarsaBBDD
except Exception as error:
  print("Se ha producido un error al importar las clases:")
  print(error)
  print("Edita la línea 'from jocarsa_bbdd import ...' con el nombre de tu módulo.")
  raise


class PruebasJocarsaBBDD:
  def __init__(self):
    self.correctas = 0
    self.incorrectas = 0
    self.directorio = tempfile.mkdtemp(prefix="jocarsa-bbdd-pruebas-")+"/"

  def comprobar(self,nombre,condicion):
    try:
      assert condicion, "La condición de la prueba no se ha cumplido"
      self.correctas += 1
      print("[OK] "+nombre)
    except Exception as error:
      self.incorrectas += 1
      print("[ERROR] "+nombre)
      print(error)

  def captura(self,funcion,*argumentos):
    salida = StringIO()
    try:
      with redirect_stdout(salida):
        resultado = funcion(*argumentos)
      return resultado,salida.getvalue()
    except Exception as error:
      print("Se ha producido un error al capturar la salida:")
      print(error)
      return None,salida.getvalue()

  def ejecutar(self):
    try:
      print("========================================")
      print(" PRUEBAS EXHAUSTIVAS JOCARSA BBDD")
      print("========================================")
      print("Directorio temporal:",self.directorio)

      serial = JocarsaSerializador()

      self.comprobar(
        "serializar lista",
        serial.serializar(["Jose","Valencia",48]) == "Jose,Valencia,48"
      )

      self.comprobar(
        "serializar con delimitador personalizado",
        serial.serializar(["uno","dos","tres"],"|") == "uno|dos|tres"
      )

      self.comprobar(
        "desserializar cadena",
        serial.desserializar("Jose,Valencia,48") == ["Jose","Valencia","48"]
      )

      self.comprobar(
        "desserializar con delimitador personalizado",
        serial.desserializar("uno|dos|tres","|") == ["uno","dos","tres"]
      )

      bbdd = JocarsaBBDD()
      bbdd.instalacion = self.directorio

      bbdd.creaBaseDatos("empresa")
      self.comprobar(
        "crear base de datos",
        os.path.isdir(self.directorio+"empresa")
      )

      _,salida = self.captura(bbdd.creaBaseDatos,"empresa")
      self.comprobar(
        "impedir crear una base de datos duplicada",
        "ya existe" in salida
      )

      bbdd.usaBaseDatos("empresa")
      self.comprobar(
        "usar base de datos",
        bbdd.basededatos == "empresa"
      )

      bbdd2 = JocarsaBBDD()
      bbdd2.instalacion = self.directorio
      _,salida = self.captura(bbdd2.usaBaseDatos,"inexistente")
      self.comprobar(
        "detectar base de datos inexistente",
        "no existe" in salida
      )

      bbdd.creaTabla("clientes","nombre,apellidos,email")
      self.comprobar(
        "crear archivo de datos",
        os.path.isfile(self.directorio+"empresa/clientes.csv")
      )
      self.comprobar(
        "crear archivo de esquema",
        os.path.isfile(self.directorio+"empresa/clientes.esquema")
      )
      self.comprobar(
        "crear archivo de índice",
        os.path.isfile(self.directorio+"empresa/clientes.idx")
      )

      self.comprobar(
        "obtener esquema",
        bbdd.obtenerEsquema("clientes") == ["id","activo","nombre","apellidos","email"]
      )

      self.comprobar(
        "siguiente id en tabla vacía",
        bbdd.siguienteId("clientes") == 1
      )

      id1 = bbdd.insertarDatos(
        "clientes",
        ["Jose Vicente","Carratala","jose@example.com"]
      )
      id2 = bbdd.insertarDatos(
        "clientes",
        ["Ana","Garcia","ana@example.com"]
      )
      id3 = bbdd.insertarDatos(
        "clientes",
        ["Luis","Lopez","luis@example.com"]
      )

      self.comprobar("insertar primer registro",id1 == 1)
      self.comprobar("insertar segundo registro",id2 == 2)
      self.comprobar("insertar tercer registro",id3 == 3)
      self.comprobar("siguiente id tras inserciones",bbdd.siguienteId("clientes") == 4)

      posicion1 = bbdd.buscarPosicion("clientes",id1)
      posicion2 = bbdd.buscarPosicion("clientes",id2)
      posicion3 = bbdd.buscarPosicion("clientes",id3)

      self.comprobar("posición registro 1",posicion1 == 0)
      self.comprobar("posición registro 2",posicion2 == bbdd.tamanoRegistro)
      self.comprobar("posición registro 3",posicion3 == bbdd.tamanoRegistro*2)

      self.comprobar(
        "buscar id inexistente",
        bbdd.buscarPosicion("clientes",999999) == -1
      )

      registro = bbdd.leerRegistro("clientes",id1)
      self.comprobar(
        "leer registro",
        registro == ["1","1","Jose Vicente","Carratala","jose@example.com"]
      )

      cliente = bbdd.seleccionar("clientes",id1)
      self.comprobar(
        "seleccionar devuelve diccionario",
        cliente == {
          "id":"1",
          "activo":"1",
          "nombre":"Jose Vicente",
          "apellidos":"Carratala",
          "email":"jose@example.com"
        }
      )

      _,salida = self.captura(bbdd.listarTodo,"clientes")
      self.comprobar("listarTodo incluye Jose","Jose Vicente" in salida)
      self.comprobar("listarTodo incluye Ana","Ana" in salida)
      self.comprobar("listarTodo incluye Luis","Luis" in salida)

      _,salida = self.captura(bbdd.buscarColumna,"clientes","nombre","Ana")
      self.comprobar(
        "buscar por columna",
        "Ana" in salida and "ana@example.com" in salida
      )

      _,salida = self.captura(bbdd.buscarColumna,"clientes","columna_inexistente","Ana")
      self.comprobar(
        "detectar columna inexistente",
        "no existe" in salida
      )

      tamano_antes = os.path.getsize(self.directorio+"empresa/clientes.csv")
      posicion_antes = bbdd.buscarPosicion("clientes",id2)

      bbdd.actualizar(
        "clientes",
        id2,
        ["Ana Maria","Garcia Perez","anamaria@example.com"]
      )

      tamano_despues = os.path.getsize(self.directorio+"empresa/clientes.csv")
      posicion_despues = bbdd.buscarPosicion("clientes",id2)
      actualizado = bbdd.seleccionar("clientes",id2)

      self.comprobar(
        "actualizar modifica los datos",
        actualizado["nombre"] == "Ana Maria"
        and actualizado["apellidos"] == "Garcia Perez"
        and actualizado["email"] == "anamaria@example.com"
      )
      self.comprobar(
        "actualizar mantiene tamaño del archivo",
        tamano_antes == tamano_despues
      )
      self.comprobar(
        "actualizar mantiene posición física",
        posicion_antes == posicion_despues
      )

      _,salida = self.captura(
        bbdd.actualizar,
        "clientes",
        id2,
        ["solo","dos"]
      )
      self.comprobar(
        "actualización con número incorrecto de campos",
        "Se esperaban" in salida
      )

      datos_grandes = ["A"*600,"Apellido","correo@example.com"]
      resultado,salida = self.captura(bbdd.insertarDatos,"clientes",datos_grandes)
      self.comprobar(
        "impedir insertar registro mayor que el bloque",
        resultado == None and "máximo permitido" in salida
      )

      tamano_antes = os.path.getsize(self.directorio+"empresa/clientes.csv")
      bbdd.eliminar("clientes",id3)
      tamano_despues = os.path.getsize(self.directorio+"empresa/clientes.csv")

      self.comprobar(
        "eliminar es borrado lógico",
        bbdd.leerRegistro(id3 if False else "clientes",id3) == None
      )
      self.comprobar(
        "eliminar no cambia tamaño del archivo",
        tamano_antes == tamano_despues
      )
      self.comprobar(
        "registro eliminado conserva posición en índice",
        bbdd.buscarPosicion("clientes",id3) == posicion3
      )

      _,salida = self.captura(bbdd.eliminar,"clientes",id3)
      self.comprobar(
        "detectar doble eliminación",
        "ya estaba eliminado" in salida
      )

      _,salida = self.captura(bbdd.listarTodo,"clientes")
      self.comprobar(
        "listarTodo oculta eliminados",
        "Luis" not in salida
      )

      _,salida = self.captura(bbdd.buscarColumna,"clientes","nombre","Luis")
      self.comprobar(
        "buscarColumna oculta eliminados",
        "{" not in salida
      )

      self.comprobar(
        "seleccionar id inexistente devuelve None",
        bbdd.seleccionar("clientes",999999) == None
      )

      bbdd3 = JocarsaBBDD()
      bbdd3.instalacion = self.directorio
      _,salida = self.captura(bbdd3.creaTabla,"sinbbdd","campo")
      self.comprobar(
        "impedir crear tabla sin seleccionar BBDD",
        "No se ha seleccionado" in salida
      )

      _,salida = self.captura(bbdd.creaTabla,"clientes","campo")
      self.comprobar(
        "impedir tabla duplicada",
        "ya existe" in salida
      )

      self.comprobar(
        "esquema inexistente devuelve None",
        bbdd.obtenerEsquema("tabla_inexistente") == None
      )

      print("")
      print("========================================")
      print(" RESULTADO")
      print("========================================")
      print("Pruebas correctas:",self.correctas)
      print("Pruebas incorrectas:",self.incorrectas)
      print("Total:",self.correctas+self.incorrectas)

      if self.incorrectas == 0:
        print("RESULTADO FINAL: TODAS LAS PRUEBAS HAN PASADO")
      else:
        print("RESULTADO FINAL: HAY PRUEBAS QUE REVISAR")

    except Exception as error:
      print("Se ha producido un error general durante las pruebas:")
      print(error)
    finally:
      try:
        shutil.rmtree(self.directorio)
        print("Directorio temporal eliminado correctamente")
      except Exception as error:
        print("Se ha producido un error al limpiar las pruebas:")
        print(error)


if __name__ == "__main__":
  pruebas = PruebasJocarsaBBDD()
  pruebas.ejecutar()
