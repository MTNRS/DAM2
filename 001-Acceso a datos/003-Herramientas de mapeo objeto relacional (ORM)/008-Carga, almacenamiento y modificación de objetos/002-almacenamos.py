# pip3 install chromadb


import chromadb

# 1. Crear una base de datos persistente
cliente = chromadb.PersistentClient(
    path="./basedatos_chroma"
)

# 2. Crear una colección
coleccion = cliente.get_or_create_collection(
    name="apuntes"
)

# 3. Documentos de ejemplo
documentos = [
    "Python es un lenguaje de programación interpretado.",
    "JavaScript permite crear páginas web interactivas.",
    "PHP se ejecuta en el servidor y genera contenido web.",
    "SQLite es una base de datos relacional ligera.",
    "HTML permite estructurar el contenido de una página web.",
    "CSS permite modificar los colores y estilos de una web.",
    "ChromaDB es una base de datos vectorial.",
    "Los embeddings representan textos mediante vectores numéricos.",
    "Una red neuronal aprende ajustando sus pesos.",
    "El aprendizaje automático permite encontrar patrones en datos."
]

# 4. Insertar documentos
coleccion.upsert(
    documents=documentos,
    ids=[str(i) for i in range(len(documentos))]
)

print("Documentos almacenados:", coleccion.count())

# 5. Buscar por similitud semántica
while True:

    pregunta = input("\n¿Qué quieres buscar? ")

    if pregunta.lower() == "salir":
        break

    resultados = coleccion.query(
        query_texts=[pregunta],
        n_results=3
    )

    print("\nResultados más similares:\n")

    for documento, distancia in zip(
        resultados["documents"][0],
        resultados["distances"][0]
    ):
        print("Documento:", documento)
        print("Distancia:", round(distancia, 4))
        print("---")
