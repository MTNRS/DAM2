
import os
import hashlib
import chromadb

# CONFIGURACIÓN
CARPETA = "/var/www/html/jocarsa-formacion/recursos/apuntes"
BASE_DATOS = "/var/www/html/jocarsa-formacion/recursos/chroma"

TAMANO = 2000
SOLAPAMIENTO = 200
LOTE = 100

# CONEXIÓN CHROMADB
cliente = chromadb.PersistentClient(path=BASE_DATOS)

coleccion = cliente.get_or_create_collection(
    name="apuntes"
)

# DIVIDIR TEXTO EN FRAGMENTOS
def fragmentar(texto):
    paso = TAMANO - SOLAPAMIENTO

    for inicio in range(0, len(texto), paso):
        fragmento = texto[inicio:inicio + TAMANO]

        if fragmento.strip():
            yield fragmento


# RECORRER ARCHIVOS
total_archivos = 0
total_fragmentos = 0

for raiz, carpetas, archivos in os.walk(CARPETA):

    for archivo in archivos:

        if not archivo.lower().endswith(".md"):
            continue

        ruta = os.path.join(raiz, archivo)

        try:
            with open(ruta, "r", encoding="utf-8") as f:
                contenido = f.read()

        except Exception as e:
            print("Error leyendo:", ruta, e)
            continue

        fragmentos = list(fragmentar(contenido))

        if not fragmentos:
            continue

        documentos = []
        metadatos = []
        ids = []

        for i, fragmento in enumerate(fragmentos):

            identificador = hashlib.sha256(
                f"{ruta}:{i}".encode("utf-8")
            ).hexdigest()

            documentos.append(fragmento)

            metadatos.append({
                "archivo": archivo,
                "ruta": ruta,
                "fragmento": i
            })

            ids.append(identificador)

        # INSERTAR EN LOTES
        for i in range(0, len(documentos), LOTE):

            coleccion.upsert(
                documents=documentos[i:i + LOTE],
                metadatas=metadatos[i:i + LOTE],
                ids=ids[i:i + LOTE]
            )

        total_archivos += 1
        total_fragmentos += len(fragmentos)

        print(
            f"[{total_archivos}] {ruta} "
            f"({len(fragmentos)} fragmentos)",
            flush=True
        )

# RESUMEN
print("\n================================")
print("INDEXACIÓN FINALIZADA")
print("================================")
print("Archivos:", total_archivos)
print("Fragmentos procesados:", total_fragmentos)
print("Fragmentos en ChromaDB:", coleccion.count())
