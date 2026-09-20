import time
import csv

inicio = time.perf_counter()

archivo = open("/var/www/html/agenda_ficticia_1_millon_contactos.csv")
lineas = csv.DictReader(archivo)

contador = 0

for linea in lineas:
    if linea["nombre"] == "Laura":
        contador += 1

fin = time.perf_counter()

print("Tiempo:", fin - inicio, "segundos")
print("Personas llamadas Laura:", contador)

archivo.close()