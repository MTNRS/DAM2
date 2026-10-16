import json
import sqlite3

archivo = open("curriculum1.json", 'r')
contenido_json = json.load(archivo)

conexion = sqlite3.connect("curriculum1.db")
cursor = conexion.cursor()

claves = []
valores = []

for clave, valor in contenido_json.items():
    claves.append(clave)
    valores.append(valor)
    
peticion = """CREATE TABLE IF NOT EXISTS curriculums(
id INTEGER PRIMARY KEY AUTOINCREMENT,
"""
for clave in claves:
  peticion += clave+" TEXT,"
peticion = peticion[:-1] # Le quita la ultima coma
peticion += """);"""


cursor.execute(peticion)


peticion = "INSERT INTO curriculums VALUES(NULL,"
for valor in valores:
  peticion += "'"+clave+"',"
peticion = peticion[:-1] # Le quita la ultima coma
peticion += ")"
print(peticion)

# Guardar cambios
conexion.commit()

# Cerrar conexión
conexion.close()
archivo.close()