import sqlite3
import json

nombre_de_la_base_de_datos = "curriculum2.db"
nombre_de_la_tabla = "curriculum2"
nombre_del_archivo = "curriculum2_recuperado.json"

conexion = sqlite3.connect(nombre_de_la_base_de_datos)
conexion.row_factory = sqlite3.Row
cursor = conexion.cursor()

# Obtener las tablas existentes
cursor.execute("SELECT name FROM sqlite_master WHERE type='table'")
tablas = [fila["name"] for fila in cursor.fetchall()]

# Leer tabla principal
peticion = f'SELECT * FROM "{nombre_de_la_tabla}"'
cursor.execute(peticion)

fila = cursor.fetchone()

resultados = []

while fila is not None:

    diccionario = dict(fila)
    id_principal = diccionario["id"]

    # Eliminar el ID interno de SQLite
    del diccionario["id"]

    # Recorrer las tablas secundarias
    for tabla in tablas:

        if tabla != nombre_de_la_tabla and tabla != "sqlite_sequence":

            # Comprobar si la tabla tiene una FK hacia la principal
            cursor.execute(f'PRAGMA foreign_key_list("{tabla}")')
            claves_foraneas = cursor.fetchall()

            for fk in claves_foraneas:

                if fk["table"] == nombre_de_la_tabla:

                    nombre_fk = fk["from"]

                    peticion = f'''
                    SELECT valor
                    FROM "{tabla}"
                    WHERE "{nombre_fk}" = ?
                    ORDER BY id
                    '''

                    cursor.execute(peticion, (id_principal,))

                    valores = []

                    dato = cursor.fetchone()

                    while dato is not None:

                        valor = dato["valor"]

                        # Recuperar objetos JSON guardados como texto
                        if valor is not None:
                            try:
                                if valor.startswith("{") or valor.startswith("["):
                                    valor = json.loads(valor)
                            except (json.JSONDecodeError, AttributeError):
                                pass

                        valores.append(valor)

                        dato = cursor.fetchone()

                    diccionario[tabla] = valores

    resultados.append(diccionario)

    # Volver a consultar la tabla principal para avanzar
    cursor.execute(
        f'SELECT * FROM "{nombre_de_la_tabla}" WHERE id > ? ORDER BY id LIMIT 1',
        (id_principal,)
    )

    fila = cursor.fetchone()

conexion.close()

# Si hay un solo registro, devolver un objeto JSON
# Si hay varios registros, devolver un array
if len(resultados) == 1:
    resultado_final = resultados[0]
else:
    resultado_final = resultados

# Guardar JSON
with open(nombre_del_archivo, "w", encoding="utf-8") as archivo:
    json.dump(resultado_final, archivo, indent=4, ensure_ascii=False)

print(json.dumps(resultado_final, indent=4, ensure_ascii=False))