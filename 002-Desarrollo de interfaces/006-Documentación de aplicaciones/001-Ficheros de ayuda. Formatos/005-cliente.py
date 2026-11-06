
import requests

url = "https://works-protection-specifies-bottles.trycloudflare.com/dam/index.php"

datos = {
    "token": "jocarsa_phi4_2026_ClaveSegura_8f3a91b7",
    "mensaje": "Hola, ¿qué tal?",
    "modelo": "phi4-mini",
    "stream": False
}

try:
    respuesta = requests.post(
        url,
        json=datos,
        timeout=600
    )

    print("HTTP:", respuesta.status_code)
    print("Content-Type:", respuesta.headers.get("Content-Type"))
    print("Respuesta completa:")
    print(respuesta.text[:10000])

    respuesta.raise_for_status()

except requests.exceptions.RequestException as e:
    print("Error de conexión:", e)
