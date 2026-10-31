#!/usr/bin/env python3

import psutil
import csv
from datetime import datetime
import os

archivo = "/home/josevicente/log/registros.csv"

# Obtener datos
fecha = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
cpu = psutil.cpu_percent(interval=1)
ram = psutil.virtual_memory().percent
disco = psutil.disk_usage('/').percent

# Comprobar si existe el archivo
existe = os.path.exists(archivo)

# Abrir en modo append
with open(archivo, "a", newline="", encoding="utf-8") as f:
    escritor = csv.writer(f)

    # Escribir cabecera solo si el archivo es nuevo
    if not existe or os.path.getsize(archivo) == 0:
        escritor.writerow(["fecha", "cpu", "ram", "disco"])

    # Añadir nueva fila
    escritor.writerow([fecha, cpu, ram, disco])

print("Registro guardado correctamente")