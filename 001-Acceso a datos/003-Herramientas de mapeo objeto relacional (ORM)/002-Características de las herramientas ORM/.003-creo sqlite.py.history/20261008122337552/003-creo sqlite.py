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
    
cursor.execute("""
    CREATE TABLE IF NOT EXISTS curriculums (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT,
        apellidos TEXT,
        email TEXT,
        edad INTEGER
    )
""")
    
print(claves)
print(valores)

# Guardar cambios
conexion.commit()

# Cerrar conexión
conexion.close()
archivo.close()