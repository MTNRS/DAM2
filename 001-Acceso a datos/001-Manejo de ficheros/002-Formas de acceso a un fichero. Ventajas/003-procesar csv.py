import csv

archivo = open("agenda.csv", mode='r', newline='')
lector = csv.reader(archivo)
for linea in lector:
	print(linea)
archivo.close()