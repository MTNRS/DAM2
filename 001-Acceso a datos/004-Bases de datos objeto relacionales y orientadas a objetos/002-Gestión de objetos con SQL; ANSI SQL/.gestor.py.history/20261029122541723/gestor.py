
import yaml
import os
import uuid
import re


class GestorYAML:

    def __init__(self, carpeta="datos"):
        self.carpeta = carpeta
        os.makedirs(carpeta, exist_ok=True)

    def ruta(self, base_datos, coleccion):
        # Evitamos rutas fuera de la carpeta de datos
        for nombre in (base_datos, coleccion):
            if not re.fullmatch(r"[a-zA-Z0-9_-]+", nombre):
                raise ValueError("Nombre de base de datos o colección no válido")

        carpeta_bd = os.path.join(self.carpeta, base_datos)
        os.makedirs(carpeta_bd, exist_ok=True)

        return os.path.join(carpeta_bd, coleccion + ".yaml")

    def cargar(self, base_datos, coleccion):
        ruta = self.ruta(base_datos, coleccion)

        if not os.path.exists(ruta):
            return []

        with open(ruta, "r", encoding="utf-8") as archivo:
            return yaml.safe_load(archivo) or []

    def guardar(self, base_datos, coleccion, objeto):
        datos = self.cargar(base_datos, coleccion)

        # Asignamos identificador único
        objeto = objeto.copy()
        objeto["_id"] = str(uuid.uuid4())

        datos.append(objeto)

        ruta = self.ruta(base_datos, coleccion)

        with open(ruta, "w", encoding="utf-8") as archivo:
            yaml.safe_dump(
                datos,
                archivo,
                allow_unicode=True,
                sort_keys=False
            )

        return objeto["_id"]

    def listar(self, base_datos, coleccion):
        return self.cargar(base_datos, coleccion)

    def buscar(self, base_datos, coleccion, id):
        for objeto in self.cargar(base_datos, coleccion):
            if objeto["_id"] == id:
                return objeto
        return None

    def eliminar(self, base_datos, coleccion, id):
        datos = self.cargar(base_datos, coleccion)
        nuevos = [obj for obj in datos if obj["_id"] != id]

        if len(nuevos) == len(datos):
            return False

        with open(self.ruta(base_datos, coleccion), "w", encoding="utf-8") as archivo:
            yaml.safe_dump(nuevos, archivo, allow_unicode=True, sort_keys=False)

        return True
