
from playwright.sync_api import sync_playwright

with sync_playwright() as p:
    navegador = p.chromium.launch(headless=True)

    pagina = navegador.new_page(
        viewport={"width": 1920, "height": 1080},
        device_scale_factor=1
    )

    pagina.goto(
        "https://www.jocarsa.com",
        wait_until="networkidle"
    )

    pagina.screenshot(
        path="captura.png",
        full_page=True
    )

    navegador.close()

print("Captura guardada en captura.png")
