import csv

class Jocarsa-basededatos:
  def listarTodo():
    archivo = open("agenda.csv", mode='r', newline='')
    lector = csv.DictReader(archivo)
    for linea in lector:
      print(linea)
    archivo.close()
    
conexion = Jocarsa-basededatos()
conexion.listarTodo()