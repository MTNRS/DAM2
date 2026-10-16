
import json
import sqlite3

nombre_del_archivo = "curriculum2.json"
nombre_de_la_tabla = nombre_del_archivo.split(".")[0]
nombre_de_la_base_de_datos = nombre_de_la_tabla + ".db"

archivo = open(nombre_del_archivo, "r", encoding="utf-8")
contenido_json = json.load(archivo)

conexion = sqlite3.connect(nombre_de_la_base_de_datos)
conexion.execute("PRAGMA foreign_keys = ON")
cursor = conexion.cursor()


# Proteger los nombres de tablas y columnas
def identificador(nombre):
    return '"' + nombre.replace('"', '""') + '"'


# Convertir valores simples a SQLite
def convertir_valor(valor):
    if isinstance(valor, bool):
        return int(valor)
    if isinstance(valor, (dict, list)):
        return json.dumps(valor, ensure_ascii=False)
    return valor


# Crear tablas e insertar datos recursivamente
def procesar_json(nombre_tabla, datos, tabla_padre=None,
                  id_padre=None, posicion=None):

    # Si recibimos un valor simple, lo tratamos como objeto
    if not isinstance(datos, dict):
        datos = {"valor": datos}

    claves = []
    valores = []

    # Separar datos simples de objetos y arrays
    for clave, valor in datos.items():
        if not isinstance(valor, (dict, list)):
            claves.append(clave)
            valores.append(convertir_valor(valor))

    # Nombre de la clave foránea
    clave_foranea = None

    if tabla_padre is not None:
        clave_foranea = tabla_padre + "_id"

    # Crear tabla
    columnas = ['"id" INTEGER PRIMARY KEY AUTOINCREMENT']

    if clave_foranea is not None:
        columnas.append(
            identificador(clave_foranea) + " INTEGER"
        )

    if posicion is not None:
        columnas.append('"posicion" INTEGER')

    for clave in claves:
        if clave not in ("id", clave_foranea, "posicion"):
            columnas.append(identificador(clave) + " TEXT")

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

    print("\nCREANDO TABLA:", nombre_tabla)
    print(peticion)

    cursor.execute(peticion)

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

    # Obtener ID del registro recién insertado
    id_actual = cursor.lastrowid

    print("INSERTADO:", nombre_tabla, "ID:", id_actual)

    # Recorrer los niveles inferiores
    for clave, valor in datos.items():

        if isinstance(valor, dict):

            # Objeto anidado
            nombre_tabla_hija = nombre_tabla + "_" + clave

            procesar_json(
                nombre_tabla_hija,
                valor,
                nombre_tabla,
                id_actual
            )

        elif isinstance(valor, list):

            # Array: cada elemento es un registro
            nombre_tabla_hija = nombre_tabla + "_" + clave

            for indice, elemento in enumerate(valor):

                procesar_json(
                    nombre_tabla_hija,
                    elemento,
                    nombre_tabla,
                    id_actual,
                    indice
                )

    return id_actual


# Ejecutar importación
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
    archivo.close()
