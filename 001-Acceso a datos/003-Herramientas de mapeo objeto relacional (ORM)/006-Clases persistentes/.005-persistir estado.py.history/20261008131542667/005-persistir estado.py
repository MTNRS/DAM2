
import random
import cv2
import numpy as np
import math
import sqlite3
import os

ANCHO = 500
ALTO = 500

BASE_DATOS = "simulacion.db"
TABLA_PRINCIPAL = "simulacion"


# ==========================================
# CLASE RECOGIBLE
# ==========================================

class Recogible:
    def __init__(self, x, y, radio, color):
        self.x = x
        self.y = y
        self.radio = radio
        self.color = tuple(color)

    def pintar(self, imagen):
        cv2.circle(
            imagen,
            (int(self.x), int(self.y)),
            self.radio,
            self.color,
            -1
        )

    def convertir_diccionario(self):
        return {
            "x": self.x,
            "y": self.y,
            "radio": self.radio,
            "color": list(self.color)
        }

    @classmethod
    def desde_diccionario(cls, datos):
        return cls(
            float(datos["x"]),
            float(datos["y"]),
            int(datos["radio"]),
            tuple(datos["color"])
        )


# ==========================================
# CLASE SER
# ==========================================

class Ser:
    def __init__(self, x, y, angulo, radio, color):
        self.x = x
        self.y = y
        self.angulo = angulo
        self.radio = radio
        self.color = tuple(color)
        self.velocidad = 3
        self.recogidos = []

    def mover(self):
        self.x += math.cos(self.angulo) * self.velocidad
        self.y += math.sin(self.angulo) * self.velocidad

    def colision(self):
        if self.x - self.radio <= 0 or self.x + self.radio >= ANCHO:
            self.angulo = math.pi - self.angulo
            self.x = max(
                self.radio,
                min(ANCHO - self.radio, self.x)
            )

        if self.y - self.radio <= 0 or self.y + self.radio >= ALTO:
            self.angulo = -self.angulo
            self.y = max(
                self.radio,
                min(ALTO - self.radio, self.y)
            )

    def recoger(self, recogibles):
        for recogible in recogibles[:]:
            distancia = math.hypot(
                self.x - recogible.x,
                self.y - recogible.y
            )

            if distancia < self.radio + recogible.radio:
                self.recogidos.append(recogible)
                recogibles.remove(recogible)

    def pintar(self, imagen):
        # Dibujar la cola primero
        for i, recogible in enumerate(self.recogidos):
            distancia = (
                self.radio +
                recogible.radio +
                i * recogible.radio * 2
            )

            recogible.x = (
                self.x -
                math.cos(self.angulo) * distancia
            )
            recogible.y = (
                self.y -
                math.sin(self.angulo) * distancia
            )

            recogible.pintar(imagen)

        cv2.circle(
            imagen,
            (int(self.x), int(self.y)),
            self.radio,
            self.color,
            -1
        )

    def convertir_diccionario(self):
        return {
            "x": self.x,
            "y": self.y,
            "angulo": self.angulo,
            "radio": self.radio,
            "color": list(self.color),
            "velocidad": self.velocidad,
            "recogidos": [
                r.convertir_diccionario()
                for r in self.recogidos
            ]
        }

    @classmethod
    def desde_diccionario(cls, datos):
        ser = cls(
            float(datos["x"]),
            float(datos["y"]),
            float(datos["angulo"]),
            int(datos["radio"]),
            tuple(datos["color"])
        )

        ser.velocidad = float(datos["velocidad"])

        ser.recogidos = [
            Recogible.desde_diccionario(r)
            for r in datos.get("recogidos", [])
        ]

        return ser


# ==========================================
# PERSISTENCIA SQLITE RECURSIVA
# ==========================================

def identificador(nombre):
    return '"' + str(nombre).replace('"', '""') + '"'


def obtener_columnas(cursor, tabla):
    cursor.execute(
        "PRAGMA table_info(" + identificador(tabla) + ")"
    )
    return {fila["name"] for fila in cursor.fetchall()}


def preparar_tabla(
    cursor, tabla, claves,
    tabla_padre=None, tiene_posicion=False
):
    fk = tabla_padre + "_id" if tabla_padre else None

    columnas = ['"id" INTEGER PRIMARY KEY AUTOINCREMENT']

    if fk:
        columnas.append(identificador(fk) + " INTEGER")

    if tiene_posicion:
        columnas.append('"posicion" INTEGER')

    if fk:
        columnas.append(
            "FOREIGN KEY (" + identificador(fk) +
            ") REFERENCES " + identificador(tabla_padre) +
            "(id) ON DELETE CASCADE"
        )

    cursor.execute(
        "CREATE TABLE IF NOT EXISTS " +
        identificador(tabla) +
        " (" + ",".join(columnas) + ")"
    )

    existentes = obtener_columnas(cursor, tabla)

    for clave in claves:
        if clave not in existentes and clave not in (
            "id", "posicion", fk
        ):
            cursor.execute(
                "ALTER TABLE " + identificador(tabla) +
                " ADD COLUMN " + identificador(clave) +
                " TEXT"
            )


def procesar_objeto(
    cursor, tabla, datos,
    tabla_padre=None, id_padre=None, posicion=None
):
    if not isinstance(datos, dict):
        datos = {"valor": datos}

    simples = {}

    for clave, valor in datos.items():
        if not isinstance(valor, (dict, list)):
            simples[clave] = valor

    preparar_tabla(
        cursor, tabla, simples.keys(),
        tabla_padre, posicion is not None
    )

    columnas = []
    valores = []

    if tabla_padre:
        columnas.append(tabla_padre + "_id")
        valores.append(id_padre)

    if posicion is not None:
        columnas.append("posicion")
        valores.append(posicion)

    for clave, valor in simples.items():
        columnas.append(clave)
        valores.append(valor)

    if columnas:
        sql = (
            "INSERT INTO " + identificador(tabla) +
            " (" +
            ",".join(identificador(c) for c in columnas) +
            ") VALUES (" +
            ",".join("?" for _ in valores) +
            ")"
        )
        cursor.execute(sql, valores)
    else:
        cursor.execute(
            "INSERT INTO " + identificador(tabla) +
            " DEFAULT VALUES"
        )

    id_actual = cursor.lastrowid

    for clave, valor in datos.items():
        tabla_hija = tabla + "_" + clave

        if isinstance(valor, dict):
            procesar_objeto(
                cursor, tabla_hija, valor,
                tabla, id_actual
            )

        elif isinstance(valor, list):
            for i, elemento in enumerate(valor):
                procesar_objeto(
                    cursor, tabla_hija, elemento,
                    tabla, id_actual, i
                )

    return id_actual


def obtener_tablas(cursor):
    cursor.execute(
        "SELECT name FROM sqlite_master "
        "WHERE type='table' AND name NOT LIKE 'sqlite_%'"
    )
    return [fila["name"] for fila in cursor.fetchall()]


def reconstruir_objeto(cursor, tabla, fila, tablas):
    datos = dict(fila)
    id_actual = datos.pop("id")
    datos.pop("posicion", None)

    cursor.execute(
        "PRAGMA foreign_key_list(" + identificador(tabla) + ")"
    )
    for fk in cursor.fetchall():
        datos.pop(fk["from"], None)

    for tabla_hija in tablas:
        cursor.execute(
            "PRAGMA foreign_key_list(" +
            identificador(tabla_hija) + ")"
        )

        fks = cursor.fetchall()

        for fk in fks:
            if fk["table"] != tabla:
                continue

            clave = tabla_hija[len(tabla) + 1:]

            columnas = obtener_columnas(cursor, tabla_hija)
            es_lista = "posicion" in columnas

            orden = (
                '"posicion", "id"'
                if es_lista else '"id"'
            )

            cursor.execute(
                "SELECT * FROM " + identificador(tabla_hija) +
                " WHERE " + identificador(fk["from"]) +
                " = ? ORDER BY " + orden,
                (id_actual,)
            )

            filas = cursor.fetchall()

            elementos = [
                reconstruir_objeto(
                    cursor, tabla_hija, f, tablas
                )
                for f in filas
            ]

            if es_lista:
                elementos = [
                    e["valor"]
                    if isinstance(e, dict)
                    and list(e.keys()) == ["valor"]
                    else e
                    for e in elementos
                ]
                datos[clave] = elementos

            elif len(elementos) == 1:
                datos[clave] = elementos[0]

            elif len(elementos) > 1:
                datos[clave] = elementos

    return datos


# ==========================================
# GUARDAR PARTIDA
# ==========================================

def guardar_partida(seres, recogibles):
    datos = {
        "ancho": ANCHO,
        "alto": ALTO,
        "seres": [
            ser.convertir_diccionario()
            for ser in seres
        ],
        "recogibles": [
            r.convertir_diccionario()
            for r in recogibles
        ]
    }

    conexion = sqlite3.connect(BASE_DATOS)
    conexion.row_factory = sqlite3.Row
    conexion.execute("PRAGMA foreign_keys = ON")
    cursor = conexion.cursor()

    try:
        # Sustituir la partida anterior
        cursor.execute(
            "SELECT name FROM sqlite_master "
            "WHERE type='table' AND name LIKE 'simulacion%'"
        )

        tablas = [fila["name"] for fila in cursor.fetchall()]

        # Borrar primero las tablas más profundas
        for tabla in sorted(
            tablas, key=lambda t: t.count("_"), reverse=True
        ):
            cursor.execute(
                "DROP TABLE " + identificador(tabla)
            )

        procesar_objeto(
            cursor,
            TABLA_PRINCIPAL,
            datos
        )

        conexion.commit()
        print("PARTIDA GUARDADA CORRECTAMENTE")

    except Exception:
        conexion.rollback()
        raise

    finally:
        conexion.close()


# ==========================================
# CARGAR PARTIDA
# ==========================================

def cargar_partida():
    if not os.path.exists(BASE_DATOS):
        return None

    conexion = sqlite3.connect(BASE_DATOS)
    conexion.row_factory = sqlite3.Row
    cursor = conexion.cursor()

    try:
        tablas = obtener_tablas(cursor)

        if TABLA_PRINCIPAL not in tablas:
            return None

        cursor.execute(
            "SELECT * FROM " +
            identificador(TABLA_PRINCIPAL) +
            " ORDER BY id DESC LIMIT 1"
        )

        fila = cursor.fetchone()

        if fila is None:
            return None

        datos = reconstruir_objeto(
            cursor,
            TABLA_PRINCIPAL,
            fila,
            tablas
        )

        seres = [
            Ser.desde_diccionario(s)
            for s in datos.get("seres", [])
        ]

        recogibles = [
            Recogible.desde_diccionario(r)
            for r in datos.get("recogibles", [])
        ]

        print("PARTIDA RECUPERADA")
        print("Seres:", len(seres))
        print("Recogibles:", len(recogibles))

        return seres, recogibles

    finally:
        conexion.close()


# ==========================================
# INICIALIZAR SIMULACIÓN
# ==========================================

partida = cargar_partida()

if partida is not None:
    seres, recogibles = partida

else:
    seres = []
    recogibles = []

    for i in range(5):
        seres.append(
            Ser(
                random.randint(20, 480),
                random.randint(20, 480),
                random.uniform(0, 2 * math.pi),
                10,
                (0, 0, 255)
            )
        )

    for i in range(100):
        recogibles.append(
            Recogible(
                random.randint(10, 490),
                random.randint(10, 490),
                4,
                (0, 180, 0)
            )
        )

    print("NUEVA SIMULACIÓN")


# ==========================================
# BUCLE PRINCIPAL
# ==========================================

while True:
    imagen = np.ones(
        (ALTO, ANCHO, 3),
        dtype=np.uint8
    ) * 255

    for recogible in recogibles:
        recogible.pintar(imagen)

    for ser in seres:
        ser.mover()
        ser.colision()
        ser.recoger(recogibles)
        ser.pintar(imagen)

    cv2.putText(
        imagen,
        f"Recogibles restantes: {len(recogibles)}",
        (10, 20),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.5,
        (0, 0, 0),
        1
    )

    cv2.putText(
        imagen,
        "ESC = Guardar y salir",
        (10, ALTO - 15),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.5,
        (0, 0, 0),
        1
    )

    cv2.imshow("Simulacion", imagen)

    tecla = cv2.waitKey(16) & 0xFF

    if tecla == 27:
        guardar_partida(seres, recogibles)
        break

cv2.destroyAllWindows()
