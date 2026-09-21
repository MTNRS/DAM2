import csv

class JocarsaBBDD:
  def listarTodo(self):
    archivo = open("agenda.csv", mode='r', newline='')
    lector = csv.DictReader(archivo)
    for linea in lector:
      print(linea)
    archivo.close()
  def buscarColumna(self,columna,valor):
    archivo = open("agenda.csv", mode='r', newline='')
    lector = csv.DictReader(archivo)
    for linea in lector:
      if linea[columna] == valor
      	print(linea)
    archivo.close()
    
conexion = JocarsaBBDD()
conexion.listarTodo()
conexion.buscar("nombre","Laura")