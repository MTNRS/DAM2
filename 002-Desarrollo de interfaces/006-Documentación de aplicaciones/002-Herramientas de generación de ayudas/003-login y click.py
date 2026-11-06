
import os
import re
import time
import hashlib
from pathlib import Path
from collections import deque
from urllib.parse import urlparse, urldefrag

from playwright.sync_api import sync_playwright

# ============================================
# CONFIGURACIÓN
# ============================================

URL = "http://localhost/jocarsa-asistencia/"

USUARIO = "jocarsa"
PASSWORD = "jocarsa"

SELECTOR_USUARIO = 'input[name="username"]'
SELECTOR_PASSWORD = 'input[type="password"]'
SELECTOR_LOGIN = 'button[type="submit"]'

MAX_PAGINAS = 100
MAX_PROFUNDIDAD = 5
ESPERA_MS = 800

DOMINIO = urlparse(URL).hostname

CARPETA = Path("capturas") / DOMINIO
CARPETA.mkdir(parents=True, exist_ok=True)

visitadas = set()
pendientes = deque()
descubiertas = set()

# Palabras que identifican posibles acciones
# peligrosas, tanto en rutas como en controles.
PELIGROSAS = re.compile(
    r"crear|nuevo|nueva|añadir|agregar|insertar|"
    r"guardar|save|create|add|new|"
    r"eliminar|borrar|suprimir|delete|remove|"
    r"editar|modificar|actualizar|update|edit|"
    r"enviar|send|submit|publicar|publish|"
    r"importar|import|exportar|export|"
    r"restaurar|reset|vaciar|limpiar|"
    r"aprobar|rechazar|confirmar|procesar|"
    r"pagar|transferir|descargar|download|"
    r"logout|salir|cerrar.sesi[oó]n",
    re.IGNORECASE
)

EXTENSIONES_EXCLUIDAS = (
    ".pdf", ".zip", ".rar", ".csv", ".xlsx",
    ".png", ".jpg", ".jpeg", ".svg",
    ".mp4", ".mp3", ".xml"
)

# ============================================
# FUNCIONES
# ============================================

def es_segura(url):
    if not url:
        return False

    url, _ = urldefrag(url)
    partes = urlparse(url)

    if partes.scheme not in ("http", "https"):
        return False

    if partes.hostname != DOMINIO:
        return False

    if partes.path.lower().endswith(EXTENSIONES_EXCLUIDAS):
        return False

    if PELIGROSAS.search(url):
        return False

    return True


def nombre_archivo(url, sufijo=""):
    partes = urlparse(url)

    nombre = partes.path.strip("/").replace("/", "_")

    if not nombre:
        nombre = "inicio"

    nombre = re.sub(r"[^a-zA-Z0-9_-]", "_", nombre)
    nombre = nombre[:80]

    hash_url = hashlib.sha256(
        (url + sufijo).encode()
    ).hexdigest()[:10]

    return f"{nombre}_{hash_url}.png"


def capturar(pagina, sufijo=""):
    archivo = CARPETA / nombre_archivo(pagina.url, sufijo)

    pagina.screenshot(
        path=str(archivo),
        full_page=True,
        animations="disabled",
        timeout=20000
    )

    print("  Captura:", archivo)

    return archivo


def descubrir_enlaces(pagina, profundidad):
    if profundidad >= MAX_PROFUNDIDAD:
        return

    enlaces = pagina.locator("a[href]").evaluate_all(
        """elementos => elementos.map(a => ({
            url: a.href,
            texto: (a.innerText || "").trim(),
            download: a.hasAttribute("download")
        }))"""
    )

    for enlace in enlaces:
        destino = enlace["url"]

        if enlace["download"]:
            continue

        if PELIGROSAS.search(enlace["texto"]):
            continue

        if not es_segura(destino):
            continue

        destino, _ = urldefrag(destino)

        if destino in descubiertas:
            continue

        descubiertas.add(destino)
        pendientes.append((destino, profundidad + 1))


def explorar_pestanas(pagina):
    """
    Solo pestañas declaradas explícitamente
    como controles de interfaz ARIA.

    No pulsa botones genéricos.
    """

    pestanas = pagina.locator('[role="tab"]')

    cantidad = min(pestanas.count(), 20)

    for indice in range(cantidad):
        try:
            pestana = pagina.locator('[role="tab"]').nth(indice)

            if not pestana.is_visible():
                continue

            texto = pestana.inner_text().strip()

            if PELIGROSAS.search(texto):
                continue

            # Solo pestañas sin navegación ni submit.
            if pestana.evaluate(
                """el => Boolean(
                    el.closest('form') ||
                    el.closest('a[href]') ||
                    el.hasAttribute('formaction')
                )"""
            ):
                continue

            pestana.click(timeout=3000)

            pagina.wait_for_timeout(ESPERA_MS)

            capturar(pagina, f"pestana_{indice}")

        except Exception as error:
            print("  Pestaña omitida:", error)


# ============================================
# NAVEGADOR
# ============================================

with sync_playwright() as p:

    navegador = p.chromium.launch(
        headless=True
    )

    contexto = navegador.new_context(
        viewport={"width": 1920, "height": 1080},
        accept_downloads=False,
        service_workers="block"
    )

    # Bloquear cualquier petición que no sea
    # GET o HEAD, salvo durante el login.
    permitir_login = True

    def proteger_peticion(route):
        if permitir_login:
            route.continue_()
            return

        metodo = route.request.method.upper()

        if metodo not in ("GET", "HEAD"):
            print("BLOQUEADA:", metodo, route.request.url)
            route.abort()
            return

        if route.request.is_navigation_request():
            if not es_segura(route.request.url):
                print("URL BLOQUEADA:", route.request.url)
                route.abort()
                return

        route.continue_()

    contexto.route("**/*", proteger_peticion)

    pagina = contexto.new_page()

    # ========================================
    # LOGIN
    # ========================================

    print("Iniciando sesión...")

    pagina.goto(
        URL,
        wait_until="domcontentloaded"
    )

    pagina.locator(SELECTOR_USUARIO).fill(USUARIO)
    pagina.locator(SELECTOR_PASSWORD).fill(PASSWORD)

    pagina.locator(SELECTOR_LOGIN).click()

    pagina.wait_for_load_state("domcontentloaded")

    # Desde este momento se bloquean POST,
    # PUT, PATCH, DELETE, etc.
    permitir_login = False

    print("Login enviado. URL:", pagina.url)

    # ========================================
    # EXPLORACIÓN
    # ========================================

    if es_segura(pagina.url):
        pendientes.append((pagina.url, 0))
        descubiertas.add(pagina.url)
    else:
        print("Destino inicial no permitido")

    while pendientes and len(visitadas) < MAX_PAGINAS:

        url, profundidad = pendientes.popleft()

        if url in visitadas:
            continue

        visitadas.add(url)

        print(
            f"\n[{len(visitadas)}/{MAX_PAGINAS}]",
            url
        )

        try:
            pagina.goto(
                url,
                wait_until="domcontentloaded",
                timeout=20000
            )

            pagina.wait_for_timeout(ESPERA_MS)

            if not es_segura(pagina.url):
                continue

            capturar(pagina)

            descubrir_enlaces(pagina, profundidad)

            explorar_pestanas(pagina)

            time.sleep(1)

        except Exception as error:
            print("ERROR:", error)

    navegador.close()

print("\nExploración finalizada")
print("Páginas visitadas:", len(visitadas))
print("Capturas:", CARPETA.resolve())
