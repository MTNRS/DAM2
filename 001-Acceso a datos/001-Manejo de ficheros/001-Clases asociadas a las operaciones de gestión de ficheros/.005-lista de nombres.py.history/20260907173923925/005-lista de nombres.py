frutas = ['peras','manzanas','platanos']

archivo = open("datos.bin","wb")
archivo.write(frutas.encode('utf-8'))

archivo.close()