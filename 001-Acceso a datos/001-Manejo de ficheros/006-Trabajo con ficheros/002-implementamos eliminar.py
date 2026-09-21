import csv
import os

class JocarsaBBDD:
  def __init__(self):
    self.instalacion = "/var/jocarsa-basededatos/"
    self.basededatos = ""
    
  def listarTodo(self,tabla):
    archivo = open(self.instalacion+self.basededatos+"/"+tabla+".csv", mode='r', newline='')
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
    archivo.write("id,"+esquema+"\n")
    archivo.close()
    
  def insertarDatos(self,tabla,datos):
    archivo = open(self.instalacion+self.basededatos+"/"+tabla+".csv",'a')
    cadena = ",".join(map(str, datos))
    archivo.write(cadena+"\n")
    archivo.close()
    
  def eliminar(self,tabla,columna,valor):
    archivo = open(self.instalacion+self.basededatos+"/"+tabla+".csv",'r')
    lector = csv.DictReader(archivo)
    cabeceras = lector.fieldnames
    lineas = []
    
    for linea in lector:
      if linea[columna] != valor:
        lineas.append(linea)
        
    archivo.close()
    
    archivo = open(self.instalacion+self.basededatos+"/"+tabla+".csv",'w',newline='')
    escritor = csv.DictWriter(archivo,fieldnames=cabeceras)
    escritor.writeheader()
    
    for linea in lineas:
      escritor.writerow(linea)
      
    archivo.close()


conexion = JocarsaBBDD()
#conexion.creaBaseDatos("empresa")
conexion.usaBaseDatos("empresa")
#conexion.creaTabla("clientes","nombre,apellidos,telefono")
#conexion.insertarDatos("clientes",["Jose Vicente","Carratala",54354])

conexion.eliminar("clientes","nombre","Jose Vicente")

conexion.listarTodo("clientes")