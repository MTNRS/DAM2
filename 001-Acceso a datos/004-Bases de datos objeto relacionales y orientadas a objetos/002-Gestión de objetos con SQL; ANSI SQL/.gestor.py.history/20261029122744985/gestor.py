
import os
import re
import uuid
import yaml


class GestorYAML:

    def __init__(self):
        self.carpeta = "datos"
        os.makedirs(self.carpeta, exist_ok=True)

    def ruta(self, base_datos, coleccion):
        # Validamos los nombres para evitar rutas no deseadas
        for nombre in (base_datos, coleccion):
            if not isinstance(nombre, str) or not re.fullmatch(
                r"[a-zA-Z0-9_-]+", nombre
            ):
                raise ValueError("Nombre de base de datos o colección no válido")

        ruta = os.path.join(self.carpeta, base_datos, coleccion)
        os.makedirs(ruta, exist_ok=True)
        return ruta

    def guardar(self, base_datos, coleccion, objeto):
        ruta = self.ruta(base_datos, coleccion)

        # Generamos un identificador único
        identificador = str(uuid.uuid4())

        # Copiamos el objeto para no modificar el original
        documento = objeto.copy()
        documento["_id"] = identificador

        archivo = os.path.join(ruta, identificador + ".yaml")

        with open(archivo, "x", encoding="utf-8") as fichero:
            yaml.safe_dump(
                documento,
                fichero,
                allow_unicode=True,
                sort_keys=False
            )

        return identificador

    def listar(self, base_datos, coleccion):
        ruta = self.ruta(base_datos, coleccion)
        documentos = []

        for archivo in sorted(os.listdir(ruta)):
            if archivo.endswith(".yaml"):
                with open(
                    os.path.join(ruta, archivo),
                    "r",
                    encoding="utf-8"
                ) as fichero:
                    documentos.append(yaml.safe_load(fichero))

        return documentos

    def buscar(self, base_datos, coleccion, identificador):
        ruta = self.ruta(base_datos, coleccion)

        try:
            identificador = str(uuid.UUID(identificador))
        except (ValueError, AttributeError, TypeError):
            return None

        archivo = os.path.join(ruta, identificador + ".yaml")

        if not os.path.exists(archivo):
            return None

        with open(archivo, "r", encoding="utf-8") as fichero:
            return yaml.safe_load(fichero)

    def actualizar(self, base_datos, coleccion, identificador, objeto):
        ruta = self.ruta(base_datos, coleccion)

        try:
            identificador = str(uuid.UUID(identificador))
        except (ValueError, AttributeError, TypeError):
            return False

        archivo = os.path.join(ruta, identificador + ".yaml")

        if not os.path.exists(archivo):
            return False

        documento = objeto.copy()
        documento["_id"] = identificador

        # Escritura mediante archivo temporal
        temporal = archivo + "." + str(uuid.uuid4()) + ".tmp"

        try:
            with open(temporal, "w", encoding="utf-8") as fichero:
                yaml.safe_dump(
                    documento,
                    fichero,
                    allow_unicode=True,
                    sort_keys=False
                )

            os.replace(temporal, archivo)
        finally:
            if os.path.exists(temporal):
                os.remove(temporal)

        return True

    def eliminar(self, base_datos, coleccion, identificador):
        ruta = self.ruta(base_datos, coleccion)

        try:
            identificador = str(uuid.UUID(identificador))
        except (ValueError, AttributeError, TypeError):
            return False

        archivo = os.path.join(ruta, identificador + ".yaml")

        if os.path.exists(archivo):
            os.remove(archivo)
            return True

        return False

    def bases_de_datos(self):
        return sorted([
            nombre for nombre in os.listdir(self.carpeta)
            if os.path.isdir(os.path.join(self.carpeta, nombre))
        ])

    def colecciones(self, base_datos):
        # Reutilizamos la validación de nombres
        self.ruta(base_datos, "_validacion")
        ruta = os.path.join(self.carpeta, base_datos)

        return sorted([
            nombre for nombre in os.listdir(ruta)
            if os.path.isdir(os.path.join(ruta, nombre))
            and nombre != "_validacion"
        ])
