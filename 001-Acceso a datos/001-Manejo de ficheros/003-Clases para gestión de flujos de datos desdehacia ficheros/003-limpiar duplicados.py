import csv

class JocarsaBBDD:
  def __init__(self,basededatos):
    self.instalacion = "/var/jocarsa-basededatos/"
    self.basededatos = basededatos
  def listarTodo(self):
    archivo = open(self.instalacion+self.basededatos, mode='r', newline='')
    lector = csv.DictReader(archivo)
    for linea in lector:
      print(linea)
    archivo.close()
  def buscarColumna(self,columna,valor):
    archivo = open(self.instalacion+self.basededatos, mode='r', newline='')
    lector = csv.DictReader(archivo)
    for linea in lector:
      if linea[columna] == valor:
      	print(linea)
    archivo.close()
    
conexion = JocarsaBBDD()
conexion.listarTodo()
conexion.buscarColumna("nombre","Laura")