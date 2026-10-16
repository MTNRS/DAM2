import json

nombre_del_archivo = "curriculum2.json"
archivo = open(nombre_del_archivo, 'r')
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
        print("Voy a crear una tabla adicional llamada: ",clave)
        for dato in valor:
            print(dato)
    else:
        print("Esto es un dato y va como valor")

archivo.close()