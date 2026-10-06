#!/usr/bin/env python3

import json
import os
import shutil
import sys
import time


class Colores:
    RESET = "\033[0m"
    NEGRITA = "\033[1m"
    SUAVE = "\033[2m"

    ROJO = "\033[91m"
    VERDE = "\033[92m"
    AMARILLO = "\033[93m"
    AZUL = "\033[94m"
    MAGENTA = "\033[95m"
    CYAN = "\033[96m"
    BLANCO = "\033[97m"

    FONDO_AZUL = "\033[44m"
    FONDO_VERDE = "\033[42m"


class InstaladorJocarsaBBDD:

    def __init__(self):
        self.directorioInstalador = os.path.dirname(os.path.abspath(__file__))
        self.archivoConfiguracion = os.path.join(
            self.directorioInstalador,
            "config.json"
        )
        self.ancho = 72


    def limpiar(self):
        os.system("cls" if os.name == "nt" else "clear")


    def linea(self, caracter="─"):
        print(
            Colores.CYAN
            + caracter * self.ancho
            + Colores.RESET
        )


    def centrar(self, texto, color=Colores.BLANCO):
        print(
            color
            + texto.center(self.ancho)
            + Colores.RESET
        )


    def titulo(self):
        self.limpiar()

        print(Colores.CYAN + "╔" + "═" * (self.ancho - 2) + "╗" + Colores.RESET)
        print(
            Colores.CYAN
            + "║"
            + Colores.RESET
            + (
                Colores.NEGRITA
                + Colores.BLANCO
                + "JOCARSA BBDD".center(self.ancho - 2)
                + Colores.RESET
            )
            + Colores.CYAN
            + "║"
            + Colores.RESET
        )
        print(
            Colores.CYAN
            + "║"
            + Colores.RESET
            + (
                Colores.SUAVE
                + "Instalador y configurador".center(self.ancho - 2)
                + Colores.RESET
            )
            + Colores.CYAN
            + "║"
            + Colores.RESET
        )
        print(Colores.CYAN + "╚" + "═" * (self.ancho - 2) + "╝" + Colores.RESET)
        print()


    def mensaje(self, simbolo, texto, color):
        print(
            "  "
            + color
            + simbolo
            + Colores.RESET
            + "  "
            + texto
        )


    def exito(self, texto):
        self.mensaje("✔", texto, Colores.VERDE)


    def aviso(self, texto):
        self.mensaje("!", texto, Colores.AMARILLO)


    def error(self, texto):
        self.mensaje("✘", texto, Colores.ROJO)


    def info(self, texto):
        self.mensaje("●", texto, Colores.CYAN)


    def preguntaSiNo(self, texto, defecto=None):
        while True:

            if defecto is True:
                opciones = "[S/n]"
            elif defecto is False:
                opciones = "[s/N]"
            else:
                opciones = "[s/n]"

            respuesta = input(
                "\n  "
                + Colores.AMARILLO
                + "?"
                + Colores.RESET
                + "  "
                + texto
                + " "
                + Colores.SUAVE
                + opciones
                + Colores.RESET
                + " "
            ).strip().lower()

            if respuesta == "" and defecto is not None:
                return defecto

            if respuesta in ["s", "si", "sí", "y", "yes"]:
                return True

            if respuesta in ["n", "no"]:
                return False

            self.aviso("Escribe 's' para sí o 'n' para no.")


    def preguntarTexto(self, texto, defecto):
        respuesta = input(
            "  "
            + Colores.AMARILLO
            + "›"
            + Colores.RESET
            + "  "
            + texto
            + "\n     "
            + Colores.SUAVE
            + "Valor por defecto: "
            + str(defecto)
            + Colores.RESET
            + "\n     > "
        ).strip()

        if respuesta == "":
            return defecto

        return respuesta


    def preguntarEntero(self, texto, defecto):
        while True:
            valor = self.preguntarTexto(texto, defecto)

            try:
                valor = int(valor)

                assert valor > 1, \
                    "El tamaño del registro debe ser mayor que 1"

                return valor

            except Exception as error:
                self.error(str(error))


    def normalizarRuta(self, ruta):
        ruta = os.path.expanduser(ruta)
        ruta = os.path.abspath(ruta)

        if not ruta.endswith(os.sep):
            ruta += os.sep

        return ruta


    def cargarConfiguracionActual(self):
        try:
            archivo = open(
                self.archivoConfiguracion,
                "r",
                encoding="utf-8"
            )
            configuracion = json.load(archivo)
            archivo.close()

            return configuracion

        except Exception as error:
            self.aviso(
                "El config.json existente no se ha podido leer correctamente."
            )
            self.error(str(error))
            return {}


    def mostrarConfiguracion(self, configuracion):
        print()
        self.linea()
        self.centrar(
            "CONFIGURACIÓN",
            Colores.NEGRITA + Colores.BLANCO
        )
        self.linea()

        instalacion = configuracion.get(
            "instalacion",
            "(sin definir)"
        )
        tamano = configuracion.get(
            "tamanoRegistro",
            "(sin definir)"
        )

        print()
        print(
            "  "
            + Colores.CYAN
            + "Directorio de datos : "
            + Colores.RESET
            + str(instalacion)
        )
        print(
            "  "
            + Colores.CYAN
            + "Tamaño de registro  : "
            + Colores.RESET
            + str(tamano)
            + " bytes"
        )
        print()


    def crearConfiguracion(self, configuracionAnterior=None):
        if configuracionAnterior is None:
            configuracionAnterior = {}

        instalacionDefecto = configuracionAnterior.get(
            "instalacion",
            "/var/jocarsa-basededatos/"
        )

        tamanoDefecto = configuracionAnterior.get(
            "tamanoRegistro",
            512
        )

        print()
        self.info("Vamos a configurar JocarsaBBDD.")
        print()

        instalacion = self.preguntarTexto(
            "Directorio donde se almacenarán las bases de datos:",
            instalacionDefecto
        )

        instalacion = self.normalizarRuta(instalacion)

        print()

        tamanoRegistro = self.preguntarEntero(
            "Tamaño fijo de cada registro, en bytes:",
            tamanoDefecto
        )

        configuracion = {
            "instalacion": instalacion,
            "tamanoRegistro": tamanoRegistro
        }

        self.mostrarConfiguracion(configuracion)

        if not self.preguntaSiNo(
            "¿Guardar esta configuración?",
            True
        ):
            self.aviso("Configuración cancelada.")
            return None

        return configuracion


    def guardarConfiguracion(self, configuracion):
        try:
            temporal = self.archivoConfiguracion + ".tmp"

            archivo = open(
                temporal,
                "w",
                encoding="utf-8"
            )

            json.dump(
                configuracion,
                archivo,
                indent=2,
                ensure_ascii=False
            )

            archivo.write("\n")
            archivo.close()

            os.replace(
                temporal,
                self.archivoConfiguracion
            )

            self.exito("config.json guardado correctamente.")
            return True

        except Exception as error:
            self.error("No se ha podido guardar config.json.")
            self.error(str(error))
            return False


    def crearDirectorioDatos(self, configuracion):
        try:
            ruta = configuracion["instalacion"]

            if os.path.isdir(ruta):
                self.exito(
                    "El directorio de datos ya existe: " + ruta
                )
                return True

            self.info(
                "El directorio de datos todavía no existe: " + ruta
            )

            if not self.preguntaSiNo(
                "¿Quieres crearlo ahora?",
                True
            ):
                self.aviso(
                    "No se ha creado el directorio de datos."
                )
                return True

            os.makedirs(
                ruta,
                exist_ok=True
            )

            self.exito(
                "Directorio creado: " + ruta
            )

            return True

        except PermissionError:
            self.error(
                "No hay permisos para crear el directorio."
            )

            if os.name != "nt":
                self.info(
                    "Prueba a ejecutar el instalador con sudo:"
                )
                print(
                    "\n     "
                    + Colores.NEGRITA
                    + "sudo python3 instalar.py"
                    + Colores.RESET
                )

            return False

        except Exception as error:
            self.error(
                "No se ha podido crear el directorio de datos."
            )
            self.error(str(error))
            return False


    def comprobarBiblioteca(self):
        ruta = os.path.join(
            self.directorioInstalador,
            "JocarsaBBDD.py"
        )

        if os.path.isfile(ruta):
            self.exito("Biblioteca JocarsaBBDD.py encontrada.")
            return True

        self.error(
            "No se encuentra JocarsaBBDD.py junto al instalador."
        )
        return False


    def pausa(self):
        print()
        input(
            "  "
            + Colores.SUAVE
            + "Pulsa ENTER para finalizar..."
            + Colores.RESET
        )


    def ejecutar(self):
        try:
            self.titulo()

            self.info(
                "Directorio del instalador: "
                + self.directorioInstalador
            )

            print()
            self.linea()

            if not self.comprobarBiblioteca():
                self.pausa()
                return

            print()

            if os.path.isfile(self.archivoConfiguracion):

                self.aviso(
                    "Se ha encontrado un archivo config.json existente."
                )

                configuracionActual = self.cargarConfiguracionActual()

                if configuracionActual:
                    self.mostrarConfiguracion(
                        configuracionActual
                    )

                sobrescribir = self.preguntaSiNo(
                    "¿Quieres sobrescribir config.json?",
                    False
                )

                if sobrescribir:

                    configuracion = self.crearConfiguracion(
                        configuracionActual
                    )

                    if configuracion is None:
                        self.pausa()
                        return

                    if not self.guardarConfiguracion(
                        configuracion
                    ):
                        self.pausa()
                        return

                else:

                    self.exito(
                        "Se conserva el config.json existente."
                    )

                    configuracion = configuracionActual

                    if not configuracion:
                        self.error(
                            "El archivo existente no contiene "
                            "una configuración válida."
                        )
                        self.pausa()
                        return

            else:

                self.aviso(
                    "No existe config.json."
                )

                self.info(
                    "Se creará una nueva configuración."
                )

                configuracion = self.crearConfiguracion()

                if configuracion is None:
                    self.pausa()
                    return

                if not self.guardarConfiguracion(
                    configuracion
                ):
                    self.pausa()
                    return

            print()
            self.linea()

            if not self.crearDirectorioDatos(
                configuracion
            ):
                self.pausa()
                return

            print()
            self.linea("═")
            print()

            self.centrar(
                "✔ INSTALACIÓN COMPLETADA",
                Colores.NEGRITA + Colores.VERDE
            )

            print()

            self.centrar(
                "JocarsaBBDD está preparada para utilizarse.",
                Colores.BLANCO
            )

            print()
            self.linea("═")

            self.pausa()

        except KeyboardInterrupt:
            print()
            print()
            self.aviso(
                "Instalación cancelada por el usuario."
            )

        except Exception as error:
            print()
            self.error(
                "Se ha producido un error durante la instalación:"
            )
            self.error(str(error))


if __name__ == "__main__":
    instalador = InstaladorJocarsaBBDD()
    instalador.ejecutar()
