import json

archivo = open("curriculum2.json", 'r')
contenido_json = json.load(archivo)

claves = []
valores = []

for clave, valor in contenido_json.items():
    claves.append(clave)
    valores.append(valor)
    
for valor in valores:
  print(type(valor))
  if type(valor) == list:
  	print("Esto es un array y va a otra tabla")
  else:
    print("Esto es un dato y va como valor")

archivo.close()