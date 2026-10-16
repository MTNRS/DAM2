import json
import sqlite3

nombre_del_archivo = "curriculum2.json"
nombre_de_la_tabla = nombre_del_archivo.split(".")[0]
nombre_de_la_base_de_datos = nombre_de_la_tabla + ".db"

archivo = open(nombre_del_archivo, 'r')
contenido_json = json.load(archivo)

conexion = sqlite3.connect(nombre_de_la_base_de_datos)
conexion.execute("PRAGMA foreign_keys = ON")
cursor = conexion.cursor()

claves = []
valores = []

# Separar claves y valores simples
for clave, valor in contenido_json.items():
    if type(valor) != list:
        claves.append(clave)
        valores.append(valor)

# Crear tabla principal
peticion = """
CREATE TABLE IF NOT EXISTS curriculums(
id INTEGER PRIMARY KEY AUTOINCREMENT,
"""

for clave in claves:
    peticion += '"' + clave + '" TEXT,'

peticion = peticion[:-1]
peticion += ");"

print(peticion)
cursor.execute(peticion)

# Insertar datos en tabla principal
columnas = ",".join('"' + clave + '"' for clave in claves)
marcadores = ",".join("?" for valor in valores)

peticion = f"INSERT INTO curriculums ({columnas}) VALUES ({marcadores})"

cursor.execute(peticion, valores)

# Recuperar el ID del curriculum insertado
id_curriculum = cursor.lastrowid

# Crear tablas adicionales para los arrays
for clave, valor in contenido_json.items():

    if type(valor) == list:

        print("Esto es un array y va a otra tabla")
        print("Voy a crear una tabla adicional llamada:", clave)

        nombre_tabla = clave

        # Crear tabla relacionada
        peticion = f"""
        CREATE TABLE IF NOT EXISTS "{nombre_tabla}"(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            curriculum_id INTEGER,
            valor TEXT,
            FOREIGN KEY (curriculum_id)
                REFERENCES curriculums(id)
        );
        """

        cursor.execute(peticion)

        # Insertar elementos del array
        for dato in valor:

            if type(dato) == dict:
                dato = json.dumps(dato, ensure_ascii=False)

            peticion = f"""
            INSERT INTO "{nombre_tabla}"
            (curriculum_id, valor)
            VALUES (?, ?)
            """

            cursor.execute(peticion, (id_curriculum, dato))

            print(dato)

    else:
        print("Esto es un dato y va como valor")

# Guardar cambios
conexion.commit()

# Cerrar conexión
conexion.close()
archivo.close()