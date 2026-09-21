import csv

archivo = open("agenda.csv", mode='r', newline='')
lector = csv.DictReader(archivo)
for linea in lector:
	print(linea['nombre'])
archivo.close()