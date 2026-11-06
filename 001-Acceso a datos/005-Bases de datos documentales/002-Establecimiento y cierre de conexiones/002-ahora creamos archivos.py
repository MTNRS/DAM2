from pathlib import Path
import json

# Carpeta principal
raiz = Path("jocarsa-documental")
raiz.mkdir(exist_ok=True)


# ============================================
# OBTENER CARPETAS
# ============================================

def obtener_carpetas():
    carpetas = [raiz]

    carpetas.extend(
        sorted(
            [p for p in raiz.rglob("*") if p.is_dir()],
            key=lambda p: str(p).lower()
        )
    )

    return carpetas


# ============================================
# MOSTRAR ESTRUCTURA
# ============================================

def mostrar_estructura():
    print("\n--- ESTRUCTURA DOCUMENTAL ---\n")

    print(f"📁 {raiz.name}/")

    elementos = sorted(
        raiz.rglob("*"),
        key=lambda p: str(p).lower()
    )

    for elemento in elementos:

        nivel = len(elemento.relative_to(raiz).parts)

        sangria = "    " * nivel

        if elemento.is_dir():
            icono = "📁"
        else:
            icono = "📄"

        print(f"{sangria}{icono} {elemento.name}")


# ============================================
# SELECCIONAR CARPETA
# ============================================

def seleccionar_carpeta():
    carpetas = obtener_carpetas()

    print("\n--- SELECCIONAR CARPETA ---\n")

    for indice, carpeta in enumerate(carpetas):

        nivel = len(carpeta.relative_to(raiz).parts)

        sangria = "    " * nivel

        print(f"{indice}. {sangria}{carpeta.name}/")

    try:
        indice = int(input("\nNúmero de carpeta: "))

        if indice < 0 or indice >= len(carpetas):
            print("Carpeta no válida")
            return None

        return carpetas[indice]

    except ValueError:
        print("Introduce un número válido")
        return None


# ============================================
# CREAR CARPETA
# ============================================

def crear_carpeta():
    destino = seleccionar_carpeta()

    if destino is None:
        return

    nombre = input("\nNombre de la nueva carpeta: ").strip()

    if not nombre:
        print("El nombre no puede estar vacío")
        return

    if not nombre_valido(nombre):
        print("Nombre no válido")
        return

    nueva = destino / nombre

    if nueva.exists():
        print("La carpeta ya existe")
        return

    try:
        nueva.mkdir()
        print(f"\nCarpeta creada: {nueva}")

    except OSError as error:
        print(f"Error: {error}")


# ============================================
# VALIDAR NOMBRE
# ============================================

def nombre_valido(nombre):
    return (
        bool(nombre)
        and nombre not in (".", "..")
        and "/" not in nombre
        and "\\" not in nombre
        and "\0" not in nombre
    )


# ============================================
# LEER CONTENIDO MULTILÍNEA
# ============================================

def leer_contenido():
    print("\nIntroduce el contenido del documento.")
    print("Escribe FIN en una línea para terminar.\n")

    lineas = []

    while True:
        linea = input()

        if linea == "FIN":
            break

        lineas.append(linea)

    return "\n".join(lineas)


# ============================================
# CREAR DOCUMENTO JSON
# ============================================

def crear_json(destino, nombre):
    print("\n--- DOCUMENTO JSON ---")

    print("Introduce un objeto o array JSON válido.")

    contenido = leer_contenido()

    try:
        datos = json.loads(contenido)

    except json.JSONDecodeError as error:
        print(f"\nJSON incorrecto: {error}")
        return

    archivo = destino / f"{nombre}.json"

    guardar_documento(
        archivo,
        json.dumps(
            datos,
            indent=4,
            ensure_ascii=False
        )
    )


# ============================================
# CREAR DOCUMENTO MARKDOWN
# ============================================

def crear_markdown(destino, nombre):
    print("\n--- DOCUMENTO MARKDOWN ---")

    contenido = leer_contenido()

    archivo = destino / f"{nombre}.md"

    guardar_documento(archivo, contenido)


# ============================================
# CREAR DOCUMENTO HTML
# ============================================

def crear_html(destino, nombre):
    print("\n--- DOCUMENTO HTML ---")

    print("1. Crear HTML con plantilla")
    print("2. Escribir HTML completo")

    opcion = input("\nSelecciona una opción: ")

    if opcion == "1":

        titulo = input("Título del documento: ")

        print("\nIntroduce el contenido del body.")

        contenido = leer_contenido()

        html = f"""<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{escapar_html(titulo)}</title>
</head>
<body>

{contenido}

</body>
</html>
"""

    elif opcion == "2":
        html = leer_contenido()

    else:
        print("Opción no válida")
        return

    archivo = destino / f"{nombre}.html"

    guardar_documento(archivo, html)


# ============================================
# ESCAPAR HTML
# ============================================

def escapar_html(texto):
    import html
    return html.escape(texto)


# ============================================
# GUARDAR DOCUMENTO
# ============================================

def guardar_documento(archivo, contenido):
    if archivo.exists():
        print("\nEl documento ya existe.")
        print("1. Sobrescribir")
        print("2. Cancelar")

        opcion = input("Selecciona: ")

        if opcion != "1":
            print("Operación cancelada")
            return

    try:
        archivo.write_text(
            contenido,
            encoding="utf-8"
        )

        print(f"\nDocumento guardado: {archivo}")

    except OSError as error:
        print(f"Error al guardar: {error}")


# ============================================
# CREAR DOCUMENTO
# ============================================

def crear_documento():
    destino = seleccionar_carpeta()

    if destino is None:
        return

    print("\n--- TIPO DE DOCUMENTO ---")
    print("1. JSON")
    print("2. Markdown")
    print("3. HTML")

    tipo = input("\nSelecciona el tipo: ")

    if tipo not in ("1", "2", "3"):
        print("Tipo no válido")
        return

    nombre = input("\nNombre del documento (sin extensión): ").strip()

    if not nombre_valido(nombre):
        print("Nombre no válido")
        return

    if tipo == "1":
        crear_json(destino, nombre)

    elif tipo == "2":
        crear_markdown(destino, nombre)

    elif tipo == "3":
        crear_html(destino, nombre)


# ============================================
# PROGRAMA PRINCIPAL
# ============================================

def main():

    while True:

        print("\n================================")
        print("       JOCARSA DOCUMENTAL")
        print("================================")
        print("1. Crear carpeta")
        print("2. Crear documento")
        print("3. Ver estructura")
        print("4. Salir")

        opcion = input("\nSelecciona una opción: ")

        if opcion == "1":
            crear_carpeta()

        elif opcion == "2":
            crear_documento()

        elif opcion == "3":
            mostrar_estructura()

        elif opcion == "4":
            print("\nHasta pronto")
            break

        else:
            print("Opción no válida")


if __name__ == "__main__":
    main()