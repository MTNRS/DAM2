archivo = open("prueba.txt",'w')
frutas = ['manzanas','platanos','naranjas']
cadena = ""
for fruta in frutas:
  cadena += fruta+","
archivo.write(frutas)
archivo.close()