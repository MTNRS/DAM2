import yaml

with open("datos.yaml", "r", encoding="utf-8") as archivo:
    datos = yaml.safe_load(archivo)

print(datos["persona"]["nombre"])