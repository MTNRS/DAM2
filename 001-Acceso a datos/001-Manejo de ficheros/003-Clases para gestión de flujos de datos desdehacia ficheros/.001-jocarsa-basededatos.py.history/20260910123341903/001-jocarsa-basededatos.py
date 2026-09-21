import csv

class JocarsaBBDD:
  def listarTodo():
    archivo = open("agenda.csv", mode='r', newline='')
    lector = csv.DictReader(archivo)
    for linea in lector:
      print(linea)
    archivo.close()
    
conexion = JocarsaBBDD()
conexion.listarTodo()