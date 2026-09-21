archivo = open("/var/jocarsa-basededatos/empresa/clientes.csv", "rb")
lineas = sum(bloque.count(b"\n") for bloque in iter(lambda: archivo.read(1024 * 1024), b""))
print(lineas)