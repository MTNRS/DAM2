
import json
import sqlite3
import os

nombre_del_archivo = "curriculum.json"
nombre_de_la_tabla = os.path.splitext(
    os.path.basename(nombre_del_archivo)
)[0]
nombre_de_la_base_de_datos = nombre_de_la_tabla + ".db"

with open(nombre_del_archivo, "r", encoding="utf-8") as archivo:
    contenido_json = json.load(archivo)

conexion = sqlite3.connect(nombre_de_la_base_de_datos)
conexion.execute("PRAGMA foreign_keys = ON")
cursor = conexion.cursor()


# Proteger identificadores SQL
def identificador(nombre):
    return '"' + str(nombre).replace('"', '""') + '"'


# Convertir valores JSON a SQLite
def convertir_valor(valor):
    if isinstance(valor, bool):
        return int(valor)
    if isinstance(valor, (dict, list)):
        return json.dumps(valor, ensure_ascii=False)
    return valor


# Consultar columnas existentes
def obtener_columnas(nombre_tabla):
    cursor.execute(
        "PRAGMA table_info(" + identificador(nombre_tabla) + ")"
    )
    return {fila[1] for fila in cursor.fetchall()}


# Crear tabla o ampliar sus columnas
def preparar_tabla(nombre_tabla, claves, tabla_padre=None,
                   tiene_posicion=False):

    clave_foranea = None

    if tabla_padre is not None:
        clave_foranea = tabla_padre + "_id"

    # Crear estructura inicial
    columnas = ['"id" INTEGER PRIMARY KEY AUTOINCREMENT']

    if clave_foranea is not None:
        columnas.append(
            identificador(clave_foranea) + " INTEGER"
        )

    if tiene_posicion:
        columnas.append('"posicion" INTEGER')

    if clave_foranea is not None:
        columnas.append(
            "FOREIGN KEY (" + identificador(clave_foranea) +
            ") REFERENCES " + identificador(tabla_padre) + "(id)"
        )

    peticion = (
        "CREATE TABLE IF NOT EXISTS " +
        identificador(nombre_tabla) +
        " (" + ",".join(columnas) + ")"
    )

    cursor.execute(peticion)

    # Detectar columnas existentes
    columnas_existentes = obtener_columnas(nombre_tabla)

    # Columnas que necesitamos
    necesarias = {}

    if clave_foranea is not None:
        necesarias[clave_foranea] = "INTEGER"

    if tiene_posicion:
        necesarias["posicion"] = "INTEGER"

    for clave in claves:
        if clave not in ("id", clave_foranea, "posicion"):
            necesarias[clave] = "TEXT"

    # Añadir columnas que todavía no existen
    for columna, tipo in necesarias.items():

        if columna not in columnas_existentes:

            peticion = (
                "ALTER TABLE " +
                identificador(nombre_tabla) +
                " ADD COLUMN " +
                identificador(columna) +
                " " + tipo
            )

            cursor.execute(peticion)

            print("NUEVA COLUMNA:", nombre_tabla, columna)

            columnas_existentes.add(columna)


# Procesamiento recursivo
def procesar_json(nombre_tabla, datos, tabla_padre=None,
                  id_padre=None, posicion=None):

    # Convertir valores simples en objetos
    if not isinstance(datos, dict):
        datos = {"valor": datos}

    claves = []
    valores = []

    # Separar valores simples de estructuras anidadas
    for clave, valor in datos.items():

        if not isinstance(valor, (dict, list)):
            claves.append(clave)
            valores.append(convertir_valor(valor))

    clave_foranea = None

    if tabla_padre is not None:
        clave_foranea = tabla_padre + "_id"

    # Crear o actualizar tabla
    preparar_tabla(
        nombre_tabla,
        claves,
        tabla_padre,
        posicion is not None
    )

    # Preparar INSERT
    columnas_insertar = []
    valores_insertar = []

    if clave_foranea is not None:
        columnas_insertar.append(clave_foranea)
        valores_insertar.append(id_padre)

    if posicion is not None:
        columnas_insertar.append("posicion")
        valores_insertar.append(posicion)

    for clave, valor in zip(claves, valores):

        if clave not in ("id", clave_foranea, "posicion"):
            columnas_insertar.append(clave)
            valores_insertar.append(valor)

    if columnas_insertar:

        columnas_sql = ",".join(
            identificador(c) for c in columnas_insertar
        )

        marcadores = ",".join(
            "?" for _ in valores_insertar
        )

        peticion = (
            "INSERT INTO " + identificador(nombre_tabla) +
            " (" + columnas_sql + ") VALUES (" +
            marcadores + ")"
        )

        cursor.execute(peticion, valores_insertar)

    else:

        cursor.execute(
            "INSERT INTO " + identificador(nombre_tabla) +
            " DEFAULT VALUES"
        )

    id_actual = cursor.lastrowid

    print("INSERTADO:", nombre_tabla, "ID:", id_actual)

    # Procesar estructuras anidadas
    for clave, valor in datos.items():

        nombre_tabla_hija = nombre_tabla + "_" + clave

        if isinstance(valor, dict):

            procesar_json(
                nombre_tabla_hija,
                valor,
                nombre_tabla,
                id_actual
            )

        elif isinstance(valor, list):

            for indice, elemento in enumerate(valor):

                procesar_json(
                    nombre_tabla_hija,
                    elemento,
                    nombre_tabla,
                    id_actual,
                    indice
                )

    return id_actual


# Importación
try:

    if isinstance(contenido_json, list):

        for indice, elemento in enumerate(contenido_json):

            procesar_json(
                nombre_de_la_tabla,
                elemento,
                posicion=indice
            )

    else:

        procesar_json(
            nombre_de_la_tabla,
            contenido_json
        )

    conexion.commit()

    print("\nIMPORTACIÓN COMPLETADA CORRECTAMENTE")

except Exception as error:

    conexion.rollback()
    print("\nERROR:", error)
    raise

finally:

    conexion.close()
