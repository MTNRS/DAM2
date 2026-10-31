import requests
import sqlite3
import json
import time
import re
import math
import hashlib
from datetime import datetime, timedelta

# ==========================================
# CONFIGURACIÓN
# ==========================================

VOCABULARIO = "vocabulario.txt"
DIMENSIONES = "dimensiones.txt"
BASE_DATOS = "embeddings.db"

MODELO = "granite4.1:8b"
OLLAMA = "http://localhost:11434/api/generate"

TAMANO_LOTE = 16
REINTENTOS = 2
TIMEOUT = 600

# 0 = procesar todas las palabras
LIMITE = 0

# ==========================================
# 1. LEER ARCHIVOS
# ==========================================

with open(DIMENSIONES, "r", encoding="utf-8") as f:
    texto = f.read()

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

if LIMITE > 0:
    palabras = palabras[:LIMITE]

if not dimensiones:
    raise ValueError("No se encontraron dimensiones")

print("Dimensiones:", len(dimensiones))
print("Palabras:", len(palabras))

# ==========================================
# 2. BASE DE DATOS SQLITE
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

cursor.execute("""
CREATE TABLE IF NOT EXISTS configuracion (
    clave TEXT PRIMARY KEY,
    valor TEXT NOT NULL
)
""")

cursor.execute("""
CREATE TABLE IF NOT EXISTS errores (
    palabra TEXT PRIMARY KEY,
    error TEXT,
    fecha TEXT
)
""")

# Verificar configuración
configuracion = {
    "dimensiones": dimensiones,
    "modelo": MODELO,
    "tamano_lote": TAMANO_LOTE,
    "prompt_version": 1
}

firma = hashlib.sha256(
    json.dumps(
        configuracion,
        ensure_ascii=False
    ).encode("utf-8")
).hexdigest()

cursor.execute("""
SELECT valor FROM configuracion
WHERE clave = 'firma'
""")

fila = cursor.fetchone()

if fila and fila[0] != firma:
    raise ValueError(
        "La configuración de los embeddings ha cambiado. "
        "Utiliza una base de datos nueva para evitar mezclar vectores."
    )

cursor.execute("""
INSERT OR IGNORE INTO configuracion (clave, valor)
VALUES ('firma', ?)
""", (firma,))

conexion.commit()

cursor.execute("SELECT palabra FROM embeddings")
procesadas = {fila[0] for fila in cursor.fetchall()}

pendientes = [
    palabra
    for palabra in palabras
    if palabra not in procesadas
]

total = len(palabras)
completadas = total - len(pendientes)

print("Ya procesadas:", completadas)
print("Pendientes:", len(pendientes))

# ==========================================
# 3. CONEXIÓN OLLAMA
# ==========================================

sesion = requests.Session()


def consultar_lote(palabra, lote):

    prompt = f"""
Analiza la palabra "{palabra}" en el contexto de la informática.

Para cada dimensión asigna un número decimal entre 0 y 1.

0 significa ninguna relación.
1 significa relación máxima.

Dimensiones en este orden:
{", ".join(lote)}

Devuelve EXACTAMENTE {len(lote)} números decimales
separados por comas.

No escribas explicaciones.
No escribas nombres de dimensiones.
No escribas corchetes.
No escribas código Markdown.

Ejemplo de formato:
0.1,0.8,0.3,0.5
"""

    respuesta = sesion.post(
        OLLAMA,
        json={
            "model": MODELO,
            "prompt": prompt,
            "stream": False,
            "options": {
                "temperature": 0,
                "num_ctx": 4096,
                "num_predict": 512
            }
        },
        timeout=TIMEOUT
    )

    respuesta.raise_for_status()

    datos = respuesta.json()
    texto = datos["response"].strip()

    # Limpiar posibles formatos adicionales
    texto = texto.replace("```json", "")
    texto = texto.replace("```", "")
    texto = texto.strip().strip("[]")

    # Convertir números separados por comas
    try:
        vector = [
            float(valor.strip())
            for valor in texto.split(",")
        ]

    except ValueError:
        raise ValueError(
            f"Respuesta no numérica: {texto[:200]}"
        )

    # Validar longitud
    if len(vector) != len(lote):
        raise ValueError(
            f"Esperados {len(lote)} valores, "
            f"recibidos {len(vector)}. "
            f"Respuesta: {texto[:200]}"
        )

    # Validar rango
    if not all(
        math.isfinite(v) and 0 <= v <= 1
        for v in vector
    ):
        raise ValueError(
            "Hay valores fuera del intervalo 0-1"
        )

    return vector


# ==========================================
# 4. GENERAR VECTOR COMPLETO
# ==========================================

def generar_vector(palabra):

    vector = []

    for inicio in range(
        0,
        len(dimensiones),
        TAMANO_LOTE
    ):

        lote = dimensiones[
            inicio:inicio + TAMANO_LOTE
        ]

        for intento in range(1, REINTENTOS + 1):

            try:
                valores = consultar_lote(
                    palabra,
                    lote
                )

                vector.extend(valores)
                break

            except (
                requests.RequestException,
                ValueError,
                KeyError,
                TypeError
            ) as error:

                print(
                    f"  Lote {inicio + 1}-"
                    f"{inicio + len(lote)} | "
                    f"Intento {intento}/{REINTENTOS} | "
                    f"{error}",
                    flush=True
                )

                if intento == REINTENTOS:
                    raise RuntimeError(
                        f"Fallo en lote "
                        f"{inicio + 1}-"
                        f"{inicio + len(lote)}: "
                        f"{error}"
                    )

                time.sleep(1)

    if len(vector) != len(dimensiones):
        raise ValueError(
            f"Vector incompleto: {len(vector)} valores"
        )

    return vector


# ==========================================
# 5. FORMATO DE TIEMPO
# ==========================================

def formato_tiempo(segundos):

    segundos = int(segundos)

    dias, resto = divmod(segundos, 86400)
    horas, resto = divmod(resto, 3600)
    minutos, segundos = divmod(resto, 60)

    if dias:
        return (
            f"{dias}d "
            f"{horas:02d}h "
            f"{minutos:02d}m"
        )

    return (
        f"{horas:02d}h "
        f"{minutos:02d}m "
        f"{segundos:02d}s"
    )


# ==========================================
# 6. PROCESAMIENTO
# ==========================================

inicio_global = time.time()

tiempos = []

correctas = 0
errores = 0

print("\n========================================")
print("INICIANDO GENERACIÓN DE EMBEDDINGS")
print("========================================\n")

try:

    for indice, palabra in enumerate(pendientes, 1):

        inicio_palabra = time.time()

        try:

            # Generar vector
            vector = generar_vector(palabra)

            # Guardar en SQLite
            cursor.execute("""
                INSERT OR REPLACE INTO embeddings
                (palabra, vector, dimensiones, fecha)
                VALUES (?, ?, ?, ?)
            """, (
                palabra,
                json.dumps(vector),
                len(vector),
                datetime.now().isoformat()
            ))

            # Eliminar errores anteriores
            cursor.execute("""
                DELETE FROM errores
                WHERE palabra = ?
            """, (palabra,))

            conexion.commit()

            correctas += 1
            estado = "OK"

        except Exception as error:

            errores += 1

            cursor.execute("""
                INSERT OR REPLACE INTO errores
                (palabra, error, fecha)
                VALUES (?, ?, ?)
            """, (
                palabra,
                str(error),
                datetime.now().isoformat()
            ))

            conexion.commit()

            estado = f"ERROR: {error}"

        # ==================================
        # PROGRESO
        # ==================================

        duracion = time.time() - inicio_palabra

        tiempos.append(duracion)

        # Media móvil últimas 20 palabras
        ultimos = tiempos[-20:]

        media = sum(ultimos) / len(ultimos)

        restantes = len(pendientes) - indice

        eta_segundos = restantes * media

        velocidad = (
            60 / media
            if media > 0
            else 0
        )

        completadas_actuales = completadas + correctas

        porcentaje = (
            completadas_actuales / total * 100
            if total
            else 100
        )

        porcentaje_intentado = (
            (completadas + indice) / total * 100
            if total
            else 100
        )

        # Barra de progreso de intentos
        ancho = 25

        relleno = int(
            porcentaje_intentado / 100 * ancho
        )

        barra = (
            "█" * relleno +
            "░" * (ancho - relleno)
        )

        print(
            f"[{barra}] "
            f"{porcentaje_intentado:6.2f}% | "
            f"{indice}/{len(pendientes)} | "
            f"{palabra[:25]:25} | "
            f"{duracion:6.1f}s | "
            f"{velocidad:5.2f} pal/min | "
            f"ETA {formato_tiempo(eta_segundos)} | "
            f"{estado}",
            flush=True
        )

except KeyboardInterrupt:

    print("\n\nInterrumpido por el usuario.")
    print("Los vectores generados están guardados.")

finally:

    conexion.commit()
    conexion.close()
    sesion.close()

    duracion_total = time.time() - inicio_global

    print("\n========================================")
    print("RESUMEN")
    print("========================================")
    print("Palabras del vocabulario:", total)
    print("Ya existentes al comenzar:", completadas)
    print("Nuevas guardadas:", correctas)
    print("Errores en esta ejecución:", errores)
    print("Tiempo total:", formato_tiempo(duracion_total))
    print("Base de datos:", BASE_DATOS)