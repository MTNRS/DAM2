import os

carpeta = "/var/www/html/jocarsa-formacion/recursos/apuntes"

for raiz, carpetas, archivos in os.walk(carpeta):
    for archivo in archivos:
        if archivo.lower().endswith(".md"):
            ruta = os.path.join(raiz, archivo)

            with open(ruta, "r", encoding="utf-8") as f:
                contenido = f.read()

            print(ruta)