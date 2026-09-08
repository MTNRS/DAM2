frutas = ['peras','manzanas','platanos']

archivo = open("datos.bin","wb")
archivo.write(b''.join(map(str.encode, frutas)))

archivo.close()