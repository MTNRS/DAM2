import json

archivo = open("curriculum1.json", 'r')
contenido_json = json.load(archivo)

claves = []
valores = []

for clave, valor in contenido_json.items():
    claves.append(clave)
    valores.append(valor)
    
print(claves)
print(valores)

archivo.close()