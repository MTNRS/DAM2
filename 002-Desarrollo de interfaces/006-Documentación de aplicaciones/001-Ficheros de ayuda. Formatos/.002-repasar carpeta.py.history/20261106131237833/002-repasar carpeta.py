import os

directorio = "/var/www/html/jocarsa-ventas"

for ruta, carpetas, archivos in os.walk(directorio):
    print("Ruta:", ruta)
    print("Carpetas:", carpetas)
    print("Archivos:", archivos)