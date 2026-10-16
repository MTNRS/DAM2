
import sqlite3
import json

nombre_de_la_base_de_datos = "curriculum.db"
nombre_de_la_tabla = "curriculum"
nombre_del_archivo = "curriculum_recuperado.json"

conexion = sqlite3.connect(nombre_de_la_base_de_datos)
conexion.row_factory = sqlite3.Row
cursor = conexion.cursor()


# Proteger nombres de tablas y columnas
def identificador(nombre):
    return '"' + nombre.replace('"', '""') + '"'


# Obtener todas las tablas
cursor.execute("""
    SELECT name
    FROM sqlite_master
    WHERE type = 'table'
    AND name NOT LIKE 'sqlite_%'
""")

tablas = [fila["name"] for fila in cursor.fetchall()]


# Descubrir relaciones entre tablas
relaciones = {}

for tabla in tablas:

    cursor.execute(
        "PRAGMA foreign_key_list(" +
        identificador(tabla) + ")"
    )

    for fk in cursor.fetchall():

        tabla_padre = fk["table"]
        columna_fk = fk["from"]

        if tabla_padre not in relaciones:
            relaciones[tabla_padre] = []

        relaciones[tabla_padre].append({
            "tabla": tabla,
            "fk": columna_fk
        })


# Reconstruir un registro recursivamente
def reconstruir_registro(nombre_tabla, fila):

    diccionario = dict(fila)

    id_actual = diccionario.pop("id", None)

    # Eliminar columnas internas
    diccionario.pop("posicion", None)

    # Eliminar claves foráneas
    cursor.execute(
        "PRAGMA foreign_key_list(" +
        identificador(nombre_tabla) + ")"
    )

    for fk in cursor.fetchall():
        diccionario.pop(fk["from"], None)

    # Buscar tablas hijas
    for relacion in relaciones.get(nombre_tabla, []):

        tabla_hija = relacion["tabla"]
        columna_fk = relacion["fk"]

        peticion = (
            "SELECT * FROM " +
            identificador(tabla_hija) +
            " WHERE " +
            identificador(columna_fk) +
            " = ?"
        )

        # Comprobar si tiene columna posicion
        cursor.execute(
            "PRAGMA table_info(" +
            identificador(tabla_hija) + ")"
        )

        columnas = [c["name"] for c in cursor.fetchall()]

        es_array = "posicion" in columnas

        if es_array:
            peticion += ' ORDER BY "posicion", "id"'
        else:
            peticion += ' ORDER BY "id"'

        cursor.execute(peticion, (id_actual,))

        filas_hijas = cursor.fetchall()

        # Recuperar nombre original de la propiedad
        prefijo = nombre_tabla + "_"

        if tabla_hija.startswith(prefijo):
            nombre_propiedad = tabla_hija[len(prefijo):]
        else:
            nombre_propiedad = tabla_hija

        # Reconstruir los elementos hijos
        elementos = []

        for fila_hija in filas_hijas:

            elemento = reconstruir_registro(
                tabla_hija,
                fila_hija
            )

            # Si solo contiene "valor",
            # recuperar directamente el valor simple
            if (
                isinstance(elemento, dict)
                and len(elemento) == 1
                and "valor" in elemento
            ):
                elemento = elemento["valor"]

            elementos.append(elemento)

        # Reconstruir array u objeto
        if es_array:

            diccionario[nombre_propiedad] = elementos

        elif len(elementos) == 1:

            diccionario[nombre_propiedad] = elementos[0]

        elif len(elementos) > 1:

            diccionario[nombre_propiedad] = elementos

    return diccionario


# Leer registros principales
cursor.execute(
    "SELECT * FROM " +
    identificador(nombre_de_la_tabla) +
    ' ORDER BY "id"'
)

filas_principales = cursor.fetchall()

resultados = []

for fila in filas_principales:

    resultado = reconstruir_registro(
        nombre_de_la_tabla,
        fila
    )

    resultados.append(resultado)


# Comprobar si la raíz era un array
cursor.execute(
    "PRAGMA table_info(" +
    identificador(nombre_de_la_tabla) + ")"
)

columnas_principales = [
    columna["name"]
    for columna in cursor.fetchall()
]

if "posicion" in columnas_principales:

    resultado_final = resultados

elif len(resultados) == 1:

    resultado_final = resultados[0]

else:

    resultado_final = resultados


conexion.close()


# Guardar JSON reconstruido
with open(
    nombre_del_archivo,
    "w",
    encoding="utf-8"
) as archivo:

    json.dump(
        resultado_final,
        archivo,
        indent=4,
        ensure_ascii=False
    )


print(
    json.dumps(
        resultado_final,
        indent=4,
        ensure_ascii=False
    )
)

print("\nJSON RECUPERADO CORRECTAMENTE")
print("Archivo:", nombre_del_archivo)
