import random
import time
import csv

ruta = "/var/jocarsa-basededatos/empresa/clientes.csv"

# ============================================
# 1. CREAR ÍNDICE DE POSICIONES
# ============================================

archivo = open(ruta, "rb")

posiciones = []

# Saltamos cabecera
archivo.readline()

while True:
    posicion = archivo.tell()
    linea = archivo.readline()

    if not linea:
        break

    posiciones.append(posicion)

archivo.close()

print("Registros:", len(posiciones))


# ============================================
# 2. BARAJAR POSICIONES
# ============================================

random.shuffle(posiciones)


# ============================================
# 3. ACCESO ALEATORIO REAL
# ============================================

inicio = time.perf_counter()

archivo = open(ruta, "rb")

contador = 0

for posicion in posiciones:

    archivo.seek(posicion)

    linea = archivo.readline().decode("utf-8")

    datos = next(csv.reader([linea]))

    # Suponiendo:
    # id,nombre,apellidos,email,...
    if datos[1] == "Laura":
        contador += 1

archivo.close()

fin = time.perf_counter()

print("Tiempo:", fin - inicio, "segundos")
print("Personas llamadas Laura:", contador)