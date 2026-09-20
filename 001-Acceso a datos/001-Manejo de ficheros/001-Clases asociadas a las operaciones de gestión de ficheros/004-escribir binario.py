nombre = "Jose Vicente"

archivo = open("datos.bin","wb")
archivo.write(nombre.encode('utf-8'))

archivo.close()