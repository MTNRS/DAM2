archivo = open("agenda.txt",'r') # Flag indica el modo de apertura
lineas = archivo.readlines()

for linea in lineas:
	print(linea)
  
archivo.close()