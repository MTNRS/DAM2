import os
ruta = "/var/jocarsa-basededatos/"
base = "clientes"

archivo = open(ruta+base+"/personas.csv",'w')
archivo.write("1,Jose Vicente,Carratala")

archivo.close()