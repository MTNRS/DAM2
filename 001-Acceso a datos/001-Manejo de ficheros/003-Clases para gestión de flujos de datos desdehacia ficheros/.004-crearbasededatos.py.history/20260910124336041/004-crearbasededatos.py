import csv
import os

class JocarsaBBDD:
  def __init__(self):
    self.instalacion = "/var/jocarsa-basededatos/"
    self.basededatos = ""
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
  def creaBaseDatos(self,nombre):
    os.mkdir(self.instalacion+self.basededatos+nombre)
  def usaBaseDatos(self,nombre):
    self.basededatos = nombre
    
conexion = JocarsaBBDD()
conexion.creaBaseDatos("empresa")
conexion.usaBaseDatos("empresa")

