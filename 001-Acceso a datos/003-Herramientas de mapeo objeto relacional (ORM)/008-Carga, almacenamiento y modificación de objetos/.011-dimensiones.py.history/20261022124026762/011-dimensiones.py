import requests
import json

# Leer dimensiones
with open("009-256 dimensiones informaticas.md", "r", encoding="utf-8") as archivo:
    linea = archivo.readline()
    dimensiones = [d.strip() for d in linea.split(",") if d.strip()]

# Introducir palabra
palabra = input("Introduce una palabra: ")

# Crear prompt
prompt = f"""
Analiza la palabra "{palabra}" en el contexto de la informática.

Para cada dimensión, asigna un valor decimal entre 0 y 1:
0 = ninguna relación
1 = relación máxima

Dimensiones, en este orden:
{", ".join(dimensiones)}

Devuelve exclusivamente un array JSON de {len(dimensiones)} números.
No incluyas explicaciones ni texto adicional.
"""

# Consultar Ollama
respuesta = requests.post(
    "http://localhost:11434/api/generate",
    json={
        "model": "granite4.1:8b",
        "prompt": prompt,
        "stream": False,
        "format": "json",
        "options": {
            "temperature": 0,
            "num_ctx": 8192
        }
    },
    timeout=600
)

respuesta.raise_for_status()

# Convertir respuesta en vector
resultado = json.loads(respuesta.json()["response"])

if isinstance(resultado, dict):
    resultado = resultado.get("vector", resultado.get("valores", []))

vector = [float(valor) for valor in resultado]

# Validar
if len(vector) != len(dimensiones):
    raise ValueError(
        f"Se esperaban {len(dimensiones)} dimensiones, "
        f"pero se recibieron {len(vector)}"
    )

if not all(0 <= valor <= 1 for valor in vector):
    raise ValueError("Hay valores fuera del intervalo [0, 1]")

# Mostrar resultado
print("\nPalabra:", palabra)
print("Dimensiones:", len(vector))
print("Vector:")
print(json.dumps(vector, ensure_ascii=False))