
import chromadb

# 1. Conectar a la base de datos existente
cliente = chromadb.PersistentClient(
    path="./basedatos_chroma"
)

# 2. Abrir la colección existente
coleccion = cliente.get_collection(
    name="apuntes"
)

# 3. Consultar todos los documentos y vectores
datos = coleccion.get(
    include=["documents", "embeddings"]
)

print("Total de documentos:", len(datos["ids"]))
print("=" * 60)

# 4. Recorrer todos los documentos
for i in range(len(datos["ids"])):

    identificador = datos["ids"][i]
    documento = datos["documents"][i]
    vector = datos["embeddings"][i]

    print("\nID:", identificador)
    print("Documento:", documento)

    print("Dimensiones:", len(vector))

    print("Vector completo:")
    print(vector.tolist())

    print("-" * 60)

# 5. Buscar documentos por similitud
while True:

    pregunta = input("\nIntroduce una búsqueda (salir para terminar): ")

    if pregunta.lower() == "salir":
        break

    if not pregunta.strip():
        continue

    resultados = coleccion.query(
        query_texts=[pregunta],
        n_results=min(3, coleccion.count()),
        include=["documents", "distances", "embeddings"]
    )

    print("\nRESULTADOS")
    print("=" * 60)

    for i in range(len(resultados["ids"][0])):

        print("\nID:", resultados["ids"][0][i])

        print(
            "Documento:",
            resultados["documents"][0][i]
        )

        print(
            "Distancia:",
            resultados["distances"][0][i]
        )

        vector = resultados["embeddings"][0][i]

        print("Dimensiones:", len(vector))
        print("Vector:", vector.tolist())

        print("-" * 60)
