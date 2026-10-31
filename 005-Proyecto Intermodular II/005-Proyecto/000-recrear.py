
from pathlib import Path
import re
import unicodedata

BASE = Path("Proyecto_Intermodular")

INDICE = """
# 1. Introducción
# 2. Identificación de necesidades del sector productivo
## 2.1. Empresas del sector por sus características organizativas y por el producto o servicio que ofrecen
## 2.2. Empresas tipo: estructura organizativa y funciones de los departamentos
## 2.3. Necesidades más demandadas a las empresas
## 2.4. Oportunidades de negocio previsibles en el sector
## 2.5. Tipo de proyecto requerido para responder a las demandas previstas
## 2.6. Características específicas del proyecto según los requerimientos
## 2.7. Obligaciones fiscales, laborales y de prevención de riesgos y condiciones de aplicación
## 2.8. Posibles ayudas o subvenciones para la incorporación de nuevas tecnologías
## 2.9. Guion de trabajo para la elaboración del proyecto
# 3. Diseño del proyecto
## 3.1. Información relativa a los aspectos que van a ser tratados en el proyecto
## 3.2. Estudio de viabilidad técnica
## 3.3. Fases del proyecto, contenido y plazos de ejecución
## 3.4. Objetivos y alcance
## 3.5. Actividades necesarias para el desarrollo
## 3.6. Recursos materiales y personales necesarios
## 3.7. Necesidades de financiación
## 3.8. Documentación necesaria para el diseño
## 3.9. Aspectos que se deben controlar para garantizar la calidad
# 4. Planificación de la ejecución
## 4.1. Secuenciación de tareas según las necesidades de implementación
## 4.2. Recursos y logística necesaria para cada tarea
## 4.3. Permisos y autorizaciones
## 4.4. Procedimientos para la ejecución de las tareas
## 4.5. Riesgos inherentes a la ejecución y plan de prevención
## 4.6. Asignación de recursos materiales y humanos según los tiempos de ejecución
## 4.7. Valoración económica
## 4.8. Documentación necesaria para la ejecución
# 5. Seguimiento y control
## 5.1. Procedimiento de evaluación de las actividades realizadas
## 5.2. Indicadores de calidad
## 5.3. Registro y evaluación de incidencias
## 5.4. Procedimiento para solucionar las incidencias
## 5.5. Gestión y registro de cambios en recursos y tareas
## 5.6. Participación de los usuarios en la evaluación
## 5.7. Cumplimiento del pliego de condiciones, cuando exista
# 6. Conclusiones
# 7. Bibliografía y fuentes consultadas
"""


def limpiar_nombre(texto):
    texto = re.sub(r"^\d+(?:\.\d+)*\.?\s*", "", texto)
    texto = unicodedata.normalize("NFC", texto)
    texto = re.sub(r'[\\/:*?"<>|]', "-", texto)
    return texto.strip().rstrip(".")


def crear_introduccion(carpeta, titulo):
    archivo = carpeta / "000-Introduccion.md"
    if not archivo.exists():
        archivo.write_text(
            f"# {titulo}\n\n",
            encoding="utf-8"
        )


def generar():
    BASE.mkdir(parents=True, exist_ok=True)

    contadores = {}
    rutas = {}

    for linea in INDICE.splitlines():
        coincidencia = re.match(r"^(#+)\s+(.+)$", linea.strip())
        if not coincidencia:
            continue

        nivel = len(coincidencia.group(1))
        titulo = limpiar_nombre(coincidencia.group(2))

        contadores[nivel] = contadores.get(nivel, 0) + 1

        for n in list(contadores):
            if n > nivel:
                del contadores[n]

        nombre = f"{contadores[nivel]:03d}-{titulo}"

        padre = BASE if nivel == 1 else rutas[nivel - 1]
        carpeta = padre / nombre

        carpeta.mkdir(parents=True, exist_ok=True)
        crear_introduccion(carpeta, titulo)

        rutas[nivel] = carpeta
        print(f"Creado: {carpeta}")

    print("\nEstructura generada correctamente.")


if __name__ == "__main__":
    generar()

