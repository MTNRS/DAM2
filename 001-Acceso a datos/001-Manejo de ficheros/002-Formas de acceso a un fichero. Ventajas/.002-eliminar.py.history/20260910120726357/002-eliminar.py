# Crear

archivo = open("agenda.txt",'w')
archivo.write("Este es un texto\n")
archivo.close()

# leer

archivo = open("agenda.txt",'r')
lineas = archivo.readlines()
print(lineas)
archivo.close()

# añadir

archivo = open("agenda.txt",'a')
archivo.write("Este es un texto\n")
archivo.close()

# leer

archivo = open("agenda.txt",'r')
lineas = archivo.readlines()
print(lineas)
archivo.close()