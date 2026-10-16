import sqlite3

conexion = sqlite3.connect("curriculum1.db")
conexion.row_factory = sqlite3.Row
cursor = conexion.cursor()

peticion = "SELECT * FROM curriculums"
cursor.execute(peticion)

fila = cursor.fetchone()

while fila is not None:
    diccionario = dict(fila)
    print(diccionario)
    fila = cursor.fetchone()

conexion.close()