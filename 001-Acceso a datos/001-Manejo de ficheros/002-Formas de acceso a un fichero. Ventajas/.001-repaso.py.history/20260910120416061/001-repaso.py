# Crear

archivo = open("agenda.txt",'w')
archivo.write("Este es un texto")
archivo.close()

# leer

archivo = open("agenda.txt",'w')
lineas = archivo.readlines()
print(lineas)
archivo.close()

