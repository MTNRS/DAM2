import requests
import sqlite3
import json
import time
import re
from pathlib import Path
from datetime import datetime, timedelta

# ==========================================
# CONFIGURACIÓN
# ==========================================

VOCABULARIO = "vocabulario.txt"
DIMENSIONES = "dimensiones.txt"
BASE_DATOS = "embeddings.db"

MODELO = "granite4.1:8b"
OLLAMA = "http://localhost:11434/api/generate"

TAMANO_LOTE = 32
REINTENTOS = 3
TIMEOUT = 600

# ==========================================
# 1. LEER ARCHIVOS
# ==========================================

with open(DIMENSIONES, "r", encoding="utf-8") as f:
    texto = f.read()

# Acepta dimensiones separadas por comas o saltos de línea
dimensiones = [
    d.strip()
    for d in re.split(r"[,\n]+", texto)
    if d.strip()
]

with open(VOCABULARIO, "r", encoding="utf-8") as f:
    palabras = list(dict.fromkeys(
        linea.strip().lower()
        for linea in f
        if linea.strip()
    ))

print(f"Dimensiones: {len(dimensiones)}")
print(f"Palabras: {len(palabras)}")

if not dimensiones:
    raise ValueError("El archivo de dimensiones está vacío")

# ==========================================
# 2. SQLITE
# ==========================================

conexion = sqlite3.connect(BASE_DATOS)
cursor = conexion.cursor()

cursor.execute("""
CREATE TABLE IF NOT EXISTS embeddings (
    palabra TEXT PRIMARY KEY,
    vector TEXT NOT NULL,
    dimensiones INTEGER NOT NULL,
    fecha TEXT NOT NULL
)
""")

conexion.commit()

# Comprobar que no se ha cambiado la definición de dimensiones
cursor.execute("""
CREATE TABLE IF NOT EXISTS configuracion (
    clave TEXT PRIMARY KEY,
    valor TEXT NOT NULL
)
""")

firma = json.dumps(dimensiones, ensure_ascii=False)

cursor.execute(
    "SELECT valor FROM configuracion WHERE clave = 'dimensiones'"
)
fila = cursor.fetchone()

if fila and fila[0] != firma:
    raise ValueError(
        "Las dimensiones han cambiado respecto a la base de datos. "
        "Utiliza otra base de datos para evitar mezclar vectores."
    )

cursor.execute(
    "INSERT OR IGNORE INTO configuracion VALUES (?, ?)",
    ("dimensiones", firma)
)

cursor.execute(
    "SELECT palabra FROM embeddings"
)

procesadas = {fila[0] for fila in cursor.fetchall()}

pendientes = [
    palabra for palabra in palabras
    if palabra not in procesadas
]

total = len(palabras)
completadas = total - len(pendientes)

conexion.commit()

print(f"Ya procesadas: {completadas}")
print(f"Pendientes: {len(pendientes)}")

# ==========================================
# 3. CONSULTAR OLLAMA
# ==========================================

sesion = requests.Session()

def consultar_lote(palabra, lote):

    prompt = f"""
Analiza la palabra "{palabra}" en el contexto de la informática.

Asigna un valor decimal entre 0 y 1 para cada dimensión.

0 = ninguna relación
1 = relación máxima

Dimensiones en orden:
{json.dumps(lote, ensure_ascii=False)}

Devuelve exclusivamente un array JSON de exactamente
{len(lote)} números decimales.

No incluyas explicaciones.
"""

    respuesta = sesion.post(
        OLLAMA,
        json={
            "model": MODELO,
            "prompt": prompt,
            "stream": False,
            "format": "json",
            "options": {
                "temperature": 0,
                "num_ctx": 8192,
                "num_predict": 1024
            }
        },
        timeout=TIMEOUT
    )

    respuesta.raise_for_status()

    contenido = respuesta.json()["response"]
    resultado = json.loads(contenido)

    # Admitir arrays y objetos JSON
    if isinstance(resultado, dict):
        for clave in ("vector", "valores", "dimensiones", "scores"):
            if clave in resultado:
                resultado = resultado[clave]
                break

    if not isinstance(resultado, list):
        raise ValueError("Ollama no devolvió una lista")

    vector = [float(v) for v in resultado]

    if len(vector) != len(lote):
        raise ValueError(
            f"Esperados {len(lote)}, recibidos {len(vector)}"
        )

    if not all(0 <= v <= 1 for v in vector):
        raise ValueError("Valores fuera del rango 0-1")

    return vector


def generar_vector(palabra):

    vector = []

    for inicio in range(0, len(dimensiones), TAMANO_LOTE):

        lote = dimensiones[inicio:inicio + TAMANO_LOTE]

        for intento in range(1, REINTENTOS + 1):
            try:
                valores = consultar_lote(palabra, lote)
                vector.extend(valores)
                break

            except (
                requests.RequestException,
                ValueError,
                KeyError,
                TypeError
            ) as error:

                if intento == REINTENTOS:
                    raise RuntimeError(
                        f"Error en dimensiones "
                        f"{inicio + 1}-{inicio + len(lote)}: {error}"
                    )

                time.sleep(2)

    return vector


# ==========================================
# 4. PROGRESO Y ETA
# ==========================================

inicio_global = time.time()
tiempos = []

correctas = 0
errores = 0

try:

    for indice, palabra in enumerate(pendientes, 1):

        inicio_palabra = time.time()

        try:
            vector = generar_vector(palabra)

            cursor.execute("""
                INSERT OR REPLACE INTO embeddings
                (palabra, vector, dimensiones, fecha)
                VALUES (?, ?, ?, ?)
            """, (
                palabra,
                json.dumps(vector, ensure_ascii=False),
                len(vector),
                datetime.now().isoformat()
            ))

            conexion.commit()
            correctas += 1

            estado = "OK"

        except Exception as error:
            errores += 1
            estado = f"ERROR: {error}"

        duracion = time.time() - inicio_palabra
        tiempos.append(duracion)

        # Media móvil de las últimas 20 palabras
        media = sum(tiempos[-20:]) / len(tiempos[-20:])

        restantes = len(pendientes) - indice
        eta_segundos = restantes * media

        eta = str(
            timedelta(seconds=int(eta_segundos))
        )

        progreso = (completadas + correctas) / total * 100

        velocidad = 60 / media if media else 0

        print(
            f"[{completadas + correctas}/{total}] "
            f"{progreso:6.2f}% | "
            f"{palabra:25} | "
            f"{duracion:6.1f}s | "
            f"{velocidad:5.2f} palabras/min | "
            f"ETA {eta} | "
            f"{estado}",
            flush=True
        )

except KeyboardInterrupt:
    print("\nProceso interrumpido. Los resultados están guardados.")

finally:
    conexion.commit()
    conexion.close()
    sesion.close()

    print("\n====================================")
    print("PROCESO FINALIZADO")
    print("====================================")
    print("Guardadas en esta ejecución:", correctas)
    print("Errores:", errores)
    print("Tiempo:", str(timedelta(
        seconds=int(time.time() - inicio_global)
    )))