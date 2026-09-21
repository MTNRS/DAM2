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
  def creaTabla(self,nombre,esquema):
    archivo = open(self.instalacion+self.basededatos+"/"+nombre+".csv",'w')
    archivo.write("id,"+esquema)
    archivo.close()
  def insertarDatos(self,tabla,datos):
    archivo = open(self.instalacion+self.basededatos+"/"+tabla+".csv",'r')
    archivo.write(datos)
    archivo.close()
    
conexion = JocarsaBBDD()
#conexion.creaBaseDatos("empresa")
conexion.usaBaseDatos("empresa")
#conexion.creaTabla("clientes","nombre,apellidos,telefono")


