
from playwright.sync_api import sync_playwright
from urllib.parse import urljoin, urlparse, urldefrag
from pathlib import Path
from collections import deque
import hashlib
import time

# CONFIGURACIÓN
URL_INICIAL = "https://www.jocarsa.com"
MAX_PAGINAS = 100
MAX_PROFUNDIDAD = 5
ESPERA = 1

# Preparar directorio
dominio = urlparse(URL_INICIAL).hostname
carpeta = Path(dominio)
carpeta.mkdir(parents=True, exist_ok=True)

visitadas = set()
pendientes = deque([(URL_INICIAL, 0)])
descubiertas = {URL_INICIAL}

EXTENSIONES_EXCLUIDAS = (
    ".pdf", ".zip", ".rar", ".jpg", ".jpeg",
    ".png", ".gif", ".svg", ".webp",
    ".mp4", ".mp3", ".css", ".js", ".xml"
)


def normalizar_url(url):
    url, _ = urldefrag(url)
    partes = urlparse(url)

    if partes.scheme not in ("http", "https"):
        return None

    if partes.hostname != dominio:
        return None

    if partes.path.lower().endswith(EXTENSIONES_EXCLUIDAS):
        return None

    return url


def nombre_archivo(url):
    partes = urlparse(url)

    ruta = partes.path.strip("/").replace("/", "_")

    if not ruta:
        ruta = "inicio"

    # Evitar nombres demasiado largos
    ruta = "".join(
        c if c.isalnum() or c in "-_" else "_"
        for c in ruta
    )[:80]

    # Hash para evitar colisiones
    identificador = hashlib.md5(
        url.encode("utf-8")
    ).hexdigest()[:8]

    return f"{ruta}_{identificador}.png"


with sync_playwright() as p:

    navegador = p.chromium.launch(headless=True)

    contexto = navegador.new_context(
        viewport={"width": 1920, "height": 1080},
        device_scale_factor=1
    )

    pagina = contexto.new_page()
    pagina.set_default_timeout(15000)

    while pendientes and len(visitadas) < MAX_PAGINAS:

        url, profundidad = pendientes.popleft()

        if url in visitadas:
            continue

        visitadas.add(url)

        print(f"\n[{len(visitadas)}/{MAX_PAGINAS}] {url}")

        try:
            respuesta = pagina.goto(
                url,
                wait_until="domcontentloaded",
                timeout=30000
            )

            if respuesta and respuesta.status >= 400:
                print(f"  HTTP {respuesta.status}")
                continue

            # Esperar a que termine de renderizar
            pagina.wait_for_timeout(1500)

            url_final = normalizar_url(pagina.url)

            if not url_final:
                print("  Redirección fuera del dominio")
                continue

            # Captura completa
            archivo = carpeta / nombre_archivo(url_final)

            pagina.screenshot(
                path=str(archivo),
                full_page=True,
                animations="disabled",
                timeout=30000
            )

            print(f"  Captura: {archivo}")

            # Registrar URL
            with open(
                carpeta / "urls.txt",
                "a",
                encoding="utf-8"
            ) as f:
                f.write(f"{url_final}\t{archivo.name}\n")

            # Buscar enlaces internos
            if profundidad < MAX_PROFUNDIDAD:

                enlaces = pagina.locator("a[href]").evaluate_all(
                    """elementos => elementos.map(
                        a => a.href
                    )"""
                )

                nuevos = 0

                for enlace in enlaces:

                    enlace = normalizar_url(enlace)

                    if not enlace:
                        continue

                    if enlace in descubiertas:
                        continue

                    descubiertas.add(enlace)

                    pendientes.append((
                        enlace,
                        profundidad + 1
                    ))

                    nuevos += 1

                print(f"  Nuevos enlaces: {nuevos}")

            time.sleep(ESPERA)

        except Exception as error:
            print(f"  ERROR: {error}")

    navegador.close()

print("\nPROCESO FINALIZADO")
print(f"Páginas intentadas: {len(visitadas)}")
print(f"Directorio: {carpeta.resolve()}")
