import sqlite3

conexion = sqlite3.connect("curriculum1.db")
cursor = conexion.cursor()

peticion = "SELECT * FROM curriculums";

conexion.commit()

conexion.close()