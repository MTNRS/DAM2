import time

archivo = open("/var/www/html/agenda_ficticia_1_millon_contactos.csv")
lineas = archivo.readlines()

inicio = time.perf_counter()

for linea in lineas:
    pass

fin = time.perf_counter()

print("Tiempo de recorrido:", fin - inicio, "segundos")

archivo.close()