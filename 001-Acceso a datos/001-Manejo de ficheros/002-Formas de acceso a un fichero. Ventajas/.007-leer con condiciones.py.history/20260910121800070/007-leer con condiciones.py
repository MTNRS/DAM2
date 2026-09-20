import csv

columna = "nombre"
columna = "Laura"

archivo = open("agenda.csv", mode='r', newline='')
lector = csv.DictReader(archivo)
for linea in lector:
	if linea[columna] == columna:
		print(linea['nombre'])
archivo.close()