import requests

# Leer dimensiones
with open("009-256 dimensiones informaticas.md", "r") as archivo:
    dimensiones = archivo.readline().strip().split(", ")

# Introducir palabra
palabra = input("Introduce una palabra: ")

# Crear prompt
prompt = f"""
Para la palabra "{palabra}", asigna un valor entre 0 y 1
a cada una de estas dimensiones informáticas:

{", ".join(dimensiones)}

Devuelve exactamente {len(dimensiones)} números decimales
separados por comas, respetando el orden.
Sin explicaciones, sin corchetes, sin texto adicional.
"""

# Consultar Ollama
respuesta = requests.post(
    "http://localhost:11434/api/generate",
    json={
        "model": "granite4.1:8b",
        "prompt": prompt,
        "stream": False,
        "options": {
            "temperature": 0,
            "num_predict": 4096
        }
    },
    timeout=600
)

respuesta.raise_for_status()

# Mostrar resultado
vector = respuesta.json()["response"].strip()

print(vector)