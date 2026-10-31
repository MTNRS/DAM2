
import chromadb

from chromadb.utils.embedding_functions import ONNXMiniLM_L6_V2

BASE_DATOS = "/var/www/html/jocarsa-formacion/recursos/chroma"

embedding_function = ONNXMiniLM_L6_V2(
    preferred_providers=["CPUExecutionProvider"]
)

cliente = chromadb.PersistentClient(
    path=BASE_DATOS
)

coleccion = cliente.get_collection(
    name="apuntes",
    embedding_function=embedding_function
)

print("Fragmentos disponibles:", coleccion.count())

while True:

    pregunta = input("\nBuscar: ").strip()

    if pregunta.lower() == "salir":
        break

    if not pregunta:
        continue

    if coleccion.count() == 0:
        print("No hay documentos indexados.")
        continue

    resultados = coleccion.query(
        query_texts=[pregunta],
        n_results=1,
        include=["documents", "metadatas", "distances"]
    )

    if not resultados["documents"][0]:
        print("Sin resultados.")
        continue

    documento = resultados["documents"][0][0]
    metadata = resultados["metadatas"][0][0]
    distancia = resultados["distances"][0][0]

    print("\nArchivo:", metadata["ruta"])
    print("Distancia:", round(distancia, 4))
    print("\n", documento)
