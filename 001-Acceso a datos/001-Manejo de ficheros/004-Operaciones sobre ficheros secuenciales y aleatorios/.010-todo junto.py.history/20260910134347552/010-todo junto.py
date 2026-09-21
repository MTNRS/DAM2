import random
import time
import csv

ruta = "/var/www/html/agenda_ficticia_1_millon_contactos.csv"

# 1. Contar líneas
archivo = open(ruta, "rb")
cantidad = sum(
    bloque.count(b"\n")
    for bloque in iter(lambda: archivo.read(1024 * 1024), b"")
)
archivo.close()

print("Líneas:", cantidad)


# 2. Crear índices
# La línea 1 contiene las cabeceras, así que empezamos en 2
indices = list(range(2, cantidad + 1))


# 3. Barajar
random.shuffle(indices)


# 4. Buscar en orden aleatorio
inicio = time.perf_counter()

contador = 0

for indice in indices:

    archivo = open(ruta, "r")

    for numero, linea in enumerate(archivo, start=1):

        if numero == indice:

            datos = next(csv.reader([linea]))

            # suponiendo:
            # id,nombre,apellidos,email,...
            if datos[1] == "Laura":
                contador += 1

            break

    archivo.close()


fin = time.perf_counter()

print("Tiempo:", fin - inicio, "segundos")
print("Personas llamadas Laura:", contador)