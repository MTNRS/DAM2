frutas = ['peras','manzanas','platanos']

archivo = open("datos.bin","wb")
archivo.write(nombre.encode('utf-8'))

archivo.close()