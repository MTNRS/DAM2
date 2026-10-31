import chromadb

cliente = chromadb.PersistentClient(path="./basedatos_chroma")
coleccion = cliente.get_collection(name="apuntes")

while True:
    pregunta = input("Pregunta: ")

    if pregunta.lower() == "salir":
        break

    resultado = coleccion.query(
        query_texts=[pregunta],
        n_results=1
    )

    print("Respuesta:", resultado["documents"][0][0])