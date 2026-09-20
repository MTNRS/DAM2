archivo = open("/var/www/html/agenda_ficticia_1_millon_contactos.csv", "r")

for numero, linea in enumerate(archivo, start=1):
    if numero == 500:
        print(linea)
        break

archivo.close()