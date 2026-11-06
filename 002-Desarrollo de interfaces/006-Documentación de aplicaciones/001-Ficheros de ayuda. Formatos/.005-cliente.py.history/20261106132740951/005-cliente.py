
import requests

url = "https://works-protection-specifies-bottles.trycloudflare.com/dam/index.php"

token = "TU_TOKEN"
modelo = "gpt-oss:20b"

pregunta = """
Crea una función Python que calcule el IVA
de una factura.
"""

datos = {
    "token": token,
    "mensaje": pregunta,
    "modelo": modelo,
    "stream": False
}

try:
    respuesta = requests.post(
        url,
        json=datos,
        timeout=600
    )

    respuesta.raise_for_status()

    resultado = respuesta.json()

    print("Modelo:", resultado["modelo"])
    print("Respuesta:")
    print(resultado["respuesta"])

except requests.exceptions.RequestException as e:
    print("Error de conexión:", e)
except (ValueError, KeyError) as e:
    print("Error procesando respuesta:", e)
