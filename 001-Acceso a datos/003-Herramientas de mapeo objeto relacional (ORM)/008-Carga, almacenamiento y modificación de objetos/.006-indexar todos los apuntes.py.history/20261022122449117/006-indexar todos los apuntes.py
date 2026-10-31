
import os
import time
import shutil
import chromadb
import onnxruntime as ort

from datetime import timedelta
from chromadb.utils.embedding_functions import DefaultEmbeddingFunction

# ==========================================
# CONFIGURACIÓN
# ==========================================

CARPETA = "/var/www/html/jocarsa-formacion/recursos/apuntes"
BASE_DATOS = "/var/www/html/jocarsa-formacion/recursos/chroma"

COLECCION = "apuntes"

TAMANO = 2000
SOLAPAMIENTO = 200
LOTE = 100

# ==========================================
# FORZAR CPU EN ONNX RUNTIME
# ==========================================

# Evitar que ONNX intente utilizar TensorRT o CUDA
# en el modelo de embeddings predeterminado.

ort.get_available_providers()

from chromadb.utils.embedding_functions import ONNXMiniLM_L6_V2

embedding_function = ONNXMiniLM_L6_V2(
    preferred_providers=["CPUExecutionProvider"]
)

# ==========================================
# FUNCIONES
# ==========================================

def fragmentar(texto):
    paso = TAMANO - SOLAPAMIENTO

    for inicio in range(0, len(texto), paso):
        fragmento = texto[inicio:inicio + TAMANO]

        if fragmento.strip():
            yield fragmento


def formatear_tiempo(segundos):
    return str(timedelta(seconds=int(max(0, segundos))))


def progreso(actual, total, inicio):
    transcurrido = time.time() - inicio

    velocidad = actual / transcurrido if transcurrido > 0 else 0

    restante = (
        (total - actual) / velocidad
        if velocidad > 0 else 0
    )

    porcentaje = actual / total * 100 if total else 100

    print(
        f"\r"
        f"Progreso: {porcentaje:6.2f}% | "
        f"{actual}/{total} fragmentos | "
        f"Velocidad: {velocidad:.2f} frag/s | "
        f"Transcurrido: {formatear_tiempo(transcurrido)} | "
        f"Restante: {formatear_tiempo(restante)}",
        end="",
        flush=True
    )


# ==========================================
# 1. ANALIZAR DOCUMENTOS
# ==========================================

print("====================================")
print("ANALIZANDO ARCHIVOS MARKDOWN")
print("====================================")

archivos_markdown = []
total_fragmentos = 0
total_caracteres = 0

for raiz, carpetas, archivos in os.walk(CARPETA):

    for archivo in archivos:

        if not archivo.lower().endswith(".md"):
            continue

        ruta = os.path.join(raiz, archivo)

        try:
            with open(ruta, "r", encoding="utf-8") as f:
                contenido = f.read()

            cantidad = sum(1 for _ in fragmentar(contenido))

            if cantidad == 0:
                continue

            archivos_markdown.append(ruta)
            total_fragmentos += cantidad
            total_caracteres += len(contenido)

        except Exception as e:
            print("Error:", ruta, e)


print("Archivos:", len(archivos_markdown))
print("Caracteres:", f"{total_caracteres:,}")
print("Fragmentos:", f"{total_fragmentos:,}")

if total_fragmentos == 0:
    raise SystemExit("No hay fragmentos para indexar.")

# ==========================================
# 2. RECREAR COLECCIÓN
# ==========================================

print("\nRecreando colección ChromaDB...")

cliente = chromadb.PersistentClient(
    path=BASE_DATOS
)

# Borrar solamente la colección anterior
try:
    cliente.delete_collection(name=COLECCION)
    print("Colección anterior eliminada.")
except ValueError:
    print("No existía colección anterior.")

coleccion = cliente.create_collection(
    name=COLECCION,
    embedding_function=embedding_function
)

# ==========================================
# 3. INDEXAR DOCUMENTOS
# ==========================================

print("\n====================================")
print("INICIANDO INDEXACIÓN")
print("====================================")

inicio = time.time()

procesados = 0
archivos_procesados = 0

documentos = []
metadatos = []
ids = []


def guardar_lote():
    global procesados

    if not documentos:
        return

    coleccion.upsert(
        documents=documentos,
        metadatas=metadatos,
        ids=ids
    )

    procesados += len(documentos)

    documentos.clear()
    metadatos.clear()
    ids.clear()

    progreso(procesados, total_fragmentos, inicio)


for ruta in archivos_markdown:

    try:
        with open(ruta, "r", encoding="utf-8") as f:
            contenido = f.read()

        for i, fragmento in enumerate(fragmentar(contenido)):

            documentos.append(fragmento)

            metadatos.append({
                "archivo": os.path.basename(ruta),
                "ruta": ruta,
                "fragmento": i
            })

            ids.append(f"{archivos_procesados}_{i}")

            if len(documentos) >= LOTE:
                guardar_lote()

        archivos_procesados += 1

    except Exception as e:
        print("\nError procesando:", ruta, e)

# Guardar último lote
guardar_lote()

# ==========================================
# 4. RESUMEN FINAL
# ==========================================

duracion = time.time() - inicio

print("\n\n====================================")
print("INDEXACIÓN FINALIZADA")
print("====================================")

print("Archivos procesados:", archivos_procesados)
print("Fragmentos indexados:", procesados)
print("Fragmentos en ChromaDB:", coleccion.count())
print("Tiempo total:", formatear_tiempo(duracion))
print("Velocidad media:", round(procesados / max(duracion, 0.001), 2), "frag/s")
print("Base de datos:", BASE_DATOS)
