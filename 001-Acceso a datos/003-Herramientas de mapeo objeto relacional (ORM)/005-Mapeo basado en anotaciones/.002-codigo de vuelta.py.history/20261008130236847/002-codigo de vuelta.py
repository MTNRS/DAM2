
import sqlite3
import json
import os

# CONFIGURACIÓN
nombre_de_la_base_de_datos = "curriculums.db"
nombre_de_la_tabla = "curriculum"
carpeta_salida = "curriculums_recuperados"

os.makedirs(carpeta_salida, exist_ok=True)

conexion = sqlite3.connect(nombre_de_la_base_de_datos)
conexion.row_factory = sqlite3.Row
cursor = conexion.cursor()


# Proteger identificadores SQL
def identificador(nombre):
    return '"' + str(nombre).replace('"', '""') + '"'


# Obtener todas las tablas
cursor.execute(
    "SELECT name FROM sqlite_master "
    "WHERE type='table' AND name NOT LIKE 'sqlite_%'"
)

tablas = [fila["name"] for fila in cursor.fetchall()]


# Obtener columnas de una tabla
def obtener_columnas(nombre_tabla):

    cursor.execute(
        "PRAGMA table_info(" + identificador(nombre_tabla) + ")"
    )

    return [fila["name"] for fila in cursor.fetchall()]


# Obtener las tablas hijas mediante claves foráneas
def obtener_tablas_hijas(nombre_tabla):

    hijas = []

    for tabla in tablas:

        if tabla == nombre_tabla:
            continue

        cursor.execute(
            "PRAGMA foreign_key_list(" +
            identificador(tabla) + ")"
        )

        for fk in cursor.fetchall():

            if fk["table"] == nombre_tabla:

                hijas.append({
                    "tabla": tabla,
                    "clave_foranea": fk["from"]
                })

    return hijas


# Reconstruir JSON recursivamente
def reconstruir_json(nombre_tabla, fila):

    datos = dict(fila)
    id_actual = datos["id"]

    # Eliminar campos internos de SQLite
    datos.pop("id", None)
    datos.pop("posicion", None)

    # Eliminar claves foráneas
    cursor.execute(
        "PRAGMA foreign_key_list(" +
        identificador(nombre_tabla) + ")"
    )

    for fk in cursor.fetchall():
        datos.pop(fk["from"], None)

    # Buscar tablas hijas
    hijas = obtener_tablas_hijas(nombre_tabla)

    for hija in hijas:

        tabla_hija = hija["tabla"]
        clave_foranea = hija["clave_foranea"]

        # Recuperar nombre original de la clave JSON
        prefijo = nombre_tabla + "_"

        if tabla_hija.startswith(prefijo):
            clave_json = tabla_hija[len(prefijo):]
        else:
            clave_json = tabla_hija

        columnas = obtener_columnas(tabla_hija)

        # Respetar orden original de los arrays
        orden = (
            ' ORDER BY "posicion", "id"'
            if "posicion" in columnas
            else ' ORDER BY "id"'
        )

        peticion = (
            "SELECT * FROM " + identificador(tabla_hija) +
            " WHERE " + identificador(clave_foranea) +
            " = ?" + orden
        )

        cursor.execute(peticion, (id_actual,))
        filas_hijas = cursor.fetchall()

        elementos = []

        for fila_hija in filas_hijas:

            elemento = reconstruir_json(
                tabla_hija,
                fila_hija
            )

            # Recuperar valores simples de arrays
            if (
                isinstance(elemento, dict)
                and list(elemento.keys()) == ["valor"]
            ):
                elemento = elemento["valor"]

            elementos.append(elemento)

        # Diferenciar objetos de arrays
        if "posicion" in columnas:

            datos[clave_json] = elementos

        else:

            if len(elementos) == 1:
                datos[clave_json] = elementos[0]

            elif len(elementos) > 1:
                datos[clave_json] = elementos

    return datos


# EXPORTACIÓN MASIVA
try:

    if nombre_de_la_tabla not in tablas:
        raise ValueError(
            "No existe la tabla principal: " +
            nombre_de_la_tabla
        )

    columnas_principal = obtener_columnas(nombre_de_la_tabla)

    orden = (
        ' ORDER BY "posicion", "id"'
        if "posicion" in columnas_principal
        else ' ORDER BY "id"'
    )

    cursor.execute(
        "SELECT * FROM " +
        identificador(nombre_de_la_tabla) +
        orden
    )

    registros = cursor.fetchall()

    total = len(registros)

    print("BASE DE DATOS:", nombre_de_la_base_de_datos)
    print("TABLA PRINCIPAL:", nombre_de_la_tabla)
    print("REGISTROS ENCONTRADOS:", total)
    print("-" * 60)

    correctos = 0
    errores = 0

    for indice, fila in enumerate(registros, 1):

        try:

            id_registro = fila["id"]

            print(
                "[" + str(indice) + "/" + str(total) + "]",
                "RECUPERANDO ID:", id_registro
            )

            contenido_json = reconstruir_json(
                nombre_de_la_tabla,
                fila
            )

            nombre_archivo = (
                nombre_de_la_tabla +
                "_" + str(id_registro) + ".json"
            )

            ruta_salida = os.path.join(
                carpeta_salida,
                nombre_archivo
            )

            with open(
                ruta_salida,
                "w",
                encoding="utf-8"
            ) as archivo:

                json.dump(
                    contenido_json,
                    archivo,
                    ensure_ascii=False,
                    indent=4
                )

            correctos += 1

            print("EXPORTADO:", ruta_salida)

        except Exception as error:

            errores += 1

            print("ERROR:", error)

    print("\n" + "=" * 60)
    print("EXPORTACIÓN FINALIZADA")
    print("Registros encontrados:", total)
    print("Archivos generados:", correctos)
    print("Errores:", errores)
    print("Carpeta:", carpeta_salida)
    print("=" * 60)

finally:

    conexion.close()
