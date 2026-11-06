from pathlib import Path

# Carpeta principal
raiz = Path("jocarsa-documental")
raiz.mkdir(exist_ok=True)


def obtener_carpetas():
    # Obtener todas las carpetas existentes
    carpetas = [raiz]

    carpetas.extend(
        sorted(
            [p for p in raiz.rglob("*") if p.is_dir()],
            key=lambda p: str(p).lower()
        )
    )

    return carpetas


def mostrar_carpetas():
    carpetas = obtener_carpetas()

    print("\n--- CARPETAS DISPONIBLES ---\n")

    for indice, carpeta in enumerate(carpetas):
        nivel = len(carpeta.relative_to(raiz).parts)
        sangria = "    " * nivel

        print(f"{indice}. {sangria}{carpeta.name}/")

    return carpetas


def crear_carpeta():
    carpetas = mostrar_carpetas()

    print("\n¿Dentro de qué carpeta quieres crear la nueva?")
    seleccion = input("Introduce el número: ")

    try:
        indice = int(seleccion)
        destino = carpetas[indice]

        if indice < 0:
            raise ValueError

    except (ValueError, IndexError):
        print("Selección no válida")
        return

    nombre = input("\nNombre de la nueva carpeta: ").strip()

    if not nombre:
        print("El nombre no puede estar vacío")
        return

    if nombre in (".", "..") or "/" in nombre or "\\" in nombre:
        print("Nombre de carpeta no válido")
        return

    nueva_carpeta = destino / nombre

    if nueva_carpeta.exists():
        print("Esa carpeta ya existe")
        return

    try:
        nueva_carpeta.mkdir()
        print(f"\nCarpeta creada: {nueva_carpeta}")

    except OSError as error:
        print(f"Error al crear la carpeta: {error}")


# Programa principal
while True:

    print("\n==============================")
    print("      JOCARSA DOCUMENTAL")
    print("==============================")
    print("1. Crear carpeta")
    print("2. Ver estructura")
    print("3. Salir")

    opcion = input("\nSelecciona una opción: ")

    if opcion == "1":
        crear_carpeta()

    elif opcion == "2":
        mostrar_carpetas()

    elif opcion == "3":
        print("Hasta pronto")
        break

    else:
        print("Opción no válida")