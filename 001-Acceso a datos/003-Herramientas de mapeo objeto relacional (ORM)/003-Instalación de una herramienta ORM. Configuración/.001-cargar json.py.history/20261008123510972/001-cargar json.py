import json

archivo = open("curriculum2.json",'r')
contenido_json = json.load(archivo)

print(contenido_json)

archivo.close()