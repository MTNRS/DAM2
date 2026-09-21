import time

inicio = time.perf_counter()

archivo = open("/var/www/html/agenda_ficticia_1_millon_contactos.csv")
lineas = archivo.readlines()

for linea in lineas:
    pass  # aquí procesas cada línea

fin = time.perf_counter()

print("Tiempo:", fin - inicio, "segundos")

archivo.close()