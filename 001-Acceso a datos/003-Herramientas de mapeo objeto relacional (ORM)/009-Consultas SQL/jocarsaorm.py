
import sqlite3
import json
from pathlib import Path


class JocarsaORM:

    def __init__(self, base_datos="jocarsa.db"):
        self.conexion = sqlite3.connect(base_datos)
        self.conexion.row_factory = sqlite3.Row
        self.conexion.execute("PRAGMA foreign_keys = ON")

    def _id(self, nombre):
        return '"' + str(nombre).replace('"', '""') + '"'

    def _tabla_existe(self, tabla):
        fila = self.conexion.execute(
            "SELECT 1 FROM sqlite_master "
            "WHERE type='table' AND name=?",
            (tabla,)
        ).fetchone()
        return fila is not None

    def _columnas(self, tabla):
        filas = self.conexion.execute(
            f"PRAGMA table_info({self._id(tabla)})"
        ).fetchall()
        return {fila["name"] for fila in filas}

    def _tipo(self, valor):
        # TEXT admite números y booleanos, pero conservamos
        # el tipo original mediante un JSON serializado.
        return "TEXT"

    def _codificar(self, valor):
        return json.dumps(valor, ensure_ascii=False)

    def _decodificar(self, valor):
        if valor is None:
            return None
        return json.loads(valor)

    def _preparar(self, tabla, campos, padre=None):
        if not self._tabla_existe(tabla):
            self.conexion.execute(
                f"CREATE TABLE {self._id(tabla)} ("
                '"id" INTEGER PRIMARY KEY AUTOINCREMENT)'
            )

        columnas = self._columnas(tabla)

        necesarias = {
            "__jocarsa_tipo": "TEXT"
        }

        if padre:
            necesarias["__jocarsa_padre"] = "INTEGER"
            necesarias["__jocarsa_posicion"] = "INTEGER"

        for clave, valor in campos.items():
            if clave in ("id", "__jocarsa_tipo",
                         "__jocarsa_padre",
                         "__jocarsa_posicion"):
                raise ValueError(
                    f"Nombre de campo reservado: {clave}"
                )
            if not isinstance(valor, (dict, list)):
                necesarias[clave] = self._tipo(valor)

        for columna, tipo in necesarias.items():
            if columna not in columnas:
                self.conexion.execute(
                    f"ALTER TABLE {self._id(tabla)} "
                    f"ADD COLUMN {self._id(columna)} {tipo}"
                )

    def _insertar(self, tabla, datos, padre=None,
                  id_padre=None, posicion=None):

        if isinstance(datos, dict):
            tipo = "dict"
            campos = datos
        else:
            tipo = "valor"
            campos = {"__valor": datos}

        self._preparar(tabla, campos, padre)

        columnas = ["__jocarsa_tipo"]
        valores = [tipo]

        if padre:
            columnas.extend([
                "__jocarsa_padre",
                "__jocarsa_posicion"
            ])
            valores.extend([id_padre, posicion])

        for clave, valor in campos.items():
            if not isinstance(valor, (dict, list)):
                columnas.append(clave)
                valores.append(self._codificar(valor))

        sql = (
            f"INSERT INTO {self._id(tabla)} ("
            + ",".join(self._id(c) for c in columnas)
            + ") VALUES ("
            + ",".join("?" for _ in columnas)
            + ")"
        )

        cursor = self.conexion.execute(sql, valores)
        nuevo_id = cursor.lastrowid

        if tipo == "dict":
            for clave, valor in campos.items():
                if isinstance(valor, (dict, list)):
                    self._guardar_contenedor(
                        tabla, nuevo_id, clave, valor
                    )

        return nuevo_id

    def _guardar_contenedor(self, tabla, id_padre,
                            clave, valor):

        # Tabla independiente para cada propiedad anidada.
        hija = tabla + "_" + clave

        if isinstance(valor, dict):
            self._insertar(
                hija, valor, tabla, id_padre
            )
        else:
            # Marcador de lista, incluso cuando está vacía.
            marcador = self._insertar(
                hija, {"__lista": True},
                tabla, id_padre
            )

            elementos = hija + "_elementos"

            for posicion, elemento in enumerate(valor):
                self._insertar(
                    elementos, elemento,
                    hija, marcador, posicion
                )

    def guardar(self, tabla, datos):
        try:
            with self.conexion:
                return self._insertar(tabla, datos)
        except Exception:
            raise

    def _hijas(self, tabla):
        prefijo = tabla + "_"
        filas = self.conexion.execute(
            "SELECT name FROM sqlite_master "
            "WHERE type='table'"
        ).fetchall()

        resultado = []
        for fila in filas:
            nombre = fila["name"]
            if not nombre.startswith(prefijo):
                continue

            resto = nombre[len(prefijo):]

            # Solo descendientes directos de esta tabla.
            if "_" not in resto:
                resultado.append((resto, nombre))

        return resultado

    def _reconstruir(self, tabla, fila):
        datos = dict(fila)
        id_actual = datos.pop("id")

        tipo = datos.pop("__jocarsa_tipo", "dict")
        datos.pop("__jocarsa_padre", None)
        datos.pop("__jocarsa_posicion", None)

        resultado = {
            clave: self._decodificar(valor)
            for clave, valor in datos.items()
        }

        if tipo == "valor":
            return resultado.get("__valor")

        for clave, hija in self._hijas(tabla):
            if not self._tabla_existe(hija):
                continue

            filas = self.conexion.execute(
                f"SELECT * FROM {self._id(hija)} "
                'WHERE "__jocarsa_padre"=? '
                'ORDER BY "__jocarsa_posicion", "id"',
                (id_actual,)
            ).fetchall()

            if not filas:
                continue

            # Un contenedor de lista tiene __lista=True.
            primera = dict(filas[0])

            if "__lista" in primera:
                elementos_tabla = hija + "_elementos"

                elementos = self.conexion.execute(
                    f"SELECT * FROM "
                    f"{self._id(elementos_tabla)} "
                    'WHERE "__jocarsa_padre"=? '
                    'ORDER BY "__jocarsa_posicion", "id"',
                    (primera["id"],)
                ).fetchall()

                resultado[clave] = [
                    self._reconstruir(elementos_tabla, e)
                    for e in elementos
                ]
            else:
                resultado[clave] = self._reconstruir(
                    hija, filas[0]
                )

        resultado.pop("__lista", None)
        return resultado

    def obtener(self, tabla, id):
        if not self._tabla_existe(tabla):
            return None

        fila = self.conexion.execute(
            f"SELECT * FROM {self._id(tabla)} "
            'WHERE "id"=?',
            (id,)
        ).fetchone()

        if fila is None:
            return None

        return self._reconstruir(tabla, fila)

    def todos(self, tabla):
        if not self._tabla_existe(tabla):
            return []

        filas = self.conexion.execute(
            f"SELECT * FROM {self._id(tabla)} "
            'ORDER BY "id"'
        ).fetchall()

        return [
            self._reconstruir(tabla, fila)
            for fila in filas
        ]

    def _eliminar_recursivo(self, tabla, id):
        for clave, hija in self._hijas(tabla):
            filas = self.conexion.execute(
                f"SELECT id FROM {self._id(hija)} "
                'WHERE "__jocarsa_padre"=?',
                (id,)
            ).fetchall()

            for fila in filas:
                self._eliminar_recursivo(hija, fila["id"])

        self.conexion.execute(
            f"DELETE FROM {self._id(tabla)} WHERE id=?",
            (id,)
        )

    def eliminar(self, tabla, id):
        if not self._tabla_existe(tabla):
            return False

        with self.conexion:
            self._eliminar_recursivo(tabla, id)

        return True

    def actualizar(self, tabla, id, cambios):
        actual = self.obtener(tabla, id)

        if actual is None:
            return None

        if not isinstance(actual, dict):
            raise TypeError("El registro no es un objeto")

        actual.update(cambios)

        # Sustituimos el árbol completo para evitar
        # conservar relaciones antiguas.
        with self.conexion:
            self._eliminar_recursivo(tabla, id)
            nuevo_id = self._insertar(tabla, actual)

        return nuevo_id

    def importar_carpeta(self, carpeta, tabla):
        carpeta = Path(carpeta)
        archivos = sorted(carpeta.glob("*.json"))

        resultados = []

        for archivo in archivos:
            try:
                datos = json.loads(
                    archivo.read_text(encoding="utf-8")
                )

                if isinstance(datos, list):
                    ids = [
                        self.guardar(tabla, elemento)
                        for elemento in datos
                    ]
                else:
                    ids = [self.guardar(tabla, datos)]

                resultados.extend(ids)
                print("OK:", archivo.name)

            except Exception as error:
                print("ERROR:", archivo.name, error)

        return resultados

    def exportar_carpeta(self, tabla, carpeta):
        carpeta = Path(carpeta)
        carpeta.mkdir(parents=True, exist_ok=True)

        if not self._tabla_existe(tabla):
            return 0

        filas = self.conexion.execute(
            f"SELECT id FROM {self._id(tabla)}"
        ).fetchall()

        for fila in filas:
            id = fila["id"]
            datos = self.obtener(tabla, id)

            destino = carpeta / f"{tabla}_{id}.json"
            destino.write_text(
                json.dumps(
                    datos,
                    ensure_ascii=False,
                    indent=4
                ),
                encoding="utf-8"
            )

        return len(filas)

    def cerrar(self):
        self.conexion.close()
