import yaml

datos = {
    "nombre": "Ana",
    "edad": 25,
    "activo": True
}

with open("datos.yaml", "w", encoding="utf-8") as archivo:
    yaml.safe_dump(datos, archivo, allow_unicode=True)