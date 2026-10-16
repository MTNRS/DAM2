import json

archivo = open("curriculum1.json", 'r')
contenido_json = json.load(archivo)

for clave, valor in contenido_json.items():
    print("Clave:", clave, "- Valor:", valor)

archivo.close()