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

  def buscarColumna(self,tabla,columna,valor):
    archivo = open(self.instalacion+self.basededatos+"/"+tabla+".csv", mode='r', newline='')
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
    serial = JocarsaSerializador()
    cadena = serial.serializar(datos)
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

  def actualizar(self,tabla,id,datos):
    archivo = open(self.instalacion+self.basededatos+"/"+tabla+".csv",'r')
    lineas = archivo.readlines()
    archivo.close()

    serial = JocarsaSerializador()
    nuevaslineas = []

    for linea in lineas:
      linea = linea.strip()
      elementos = serial.desserializar(linea)

      if elementos[0] == str(id):
        elementos = [str(id)]+datos
        linea = serial.serializar(elementos)

      nuevaslineas.append(linea)

    archivo = open(self.instalacion+self.basededatos+"/"+tabla+".csv",'w')

    for linea in nuevaslineas:
      archivo.write(linea+"\n")

    archivo.close()


conexion = JocarsaBBDD()
#conexion.creaBaseDatos("empresa")
conexion.usaBaseDatos("empresa")
#conexion.creaTabla("clientes","nombre,apellidos,telefono")
#conexion.insertarDatos("clientes",["1","Jose Vicente","Carratala",54354])
#conexion.eliminar("clientes","nombre","Jose Vicente")
#conexion.actualizar("clientes",1,["Jose Vicente","Carratala Sanchis","666777888"])
conexion.listarTodo("clientes")