
import os
from pathlib import Path

directorio = "/var/www/html/jocarsa-asistencias"
salida = "proyecto.md"

# Carpetas que no queremos procesar
excluir_carpetas = {
    ".git", ".get", "venv", ".venv", "env",
    "node_modules", "__pycache__", ".idea",
    ".vscode", "dist", "build", ".pytest_cache",
    ".mypy_cache", "vendor", "coverage"
}

# Extensiones que no queremos procesar
excluir_extensiones = {
    ".png", ".jpg", ".jpeg", ".gif", ".webp",
    ".ico", ".svg", ".pdf", ".zip", ".tar",
    ".gz", ".7z", ".mp3", ".mp4", ".avi",
    ".woff", ".woff2", ".ttf", ".otf",
    ".sqlite", ".sqlite3", ".db", ".pyc",
    ".exe", ".so", ".dll", ".bin"
}

# Archivos sensibles o innecesarios
excluir_archivos = {
    ".env", ".env.local", ".env.production",
    ".env.development", "secrets.json",
    "credentials.json", "id_rsa", "id_ed25519",
    salida
}

# Extensiones de archivos de texto
extensiones_texto = {
    ".py", ".php", ".js", ".ts", ".jsx", ".tsx",
    ".html", ".htm", ".css", ".scss",
    ".json", ".xml", ".yaml", ".yml",
    ".md", ".txt", ".sql", ".sh",
    ".c", ".cpp", ".h", ".hpp",
    ".java", ".rb", ".go", ".rs",
    ".ini", ".cfg", ".conf", ".toml",
    ".csv", ".gitignore", ".htaccess"
}

# Lenguajes para Markdown
lenguajes = {
    ".py": "python",
    ".php": "php",
    ".js": "javascript",
    ".ts": "typescript",
    ".html": "html",
    ".css": "css",
    ".json": "json",
    ".sql": "sql",
    ".sh": "bash",
    ".xml": "xml",
    ".yaml": "yaml",
    ".yml": "yaml",
    ".md": "markdown",
    ".cpp": "cpp",
    ".c": "c",
    ".java": "java"
}

raiz = Path(directorio).resolve()
archivos_texto = []

with open(salida, "w", encoding="utf-8") as markdown:

    markdown.write(f"# Proyecto: {raiz.name}\n\n")

    # ==========================================
    # 1. ÁRBOL JERÁRQUICO
    # ==========================================

    markdown.write("## 1. Estructura del proyecto\n\n")
    markdown.write("```text\n")
    markdown.write(f"{raiz.name}/\n")

    def generar_arbol(carpeta, prefijo=""):
        try:
            elementos = sorted(
                [
                    e for e in carpeta.iterdir()
                    if not e.is_symlink()
                    and e.name not in excluir_carpetas
                    and e.name not in excluir_archivos
                    and not (
                        e.is_file()
                        and e.suffix.lower() in excluir_extensiones
                    )
                ],
                key=lambda e: (e.is_file(), e.name.lower())
            )
        except PermissionError:
            return

        for indice, elemento in enumerate(elementos):
            ultimo = indice == len(elementos) - 1

            conector = "└── " if ultimo else "├── "
            markdown.write(
                prefijo + conector + elemento.name
                + ("/" if elemento.is_dir() else "") + "\n"
            )

            if elemento.is_dir():
                nuevo_prefijo = prefijo + (
                    "    " if ultimo else "│   "
                )
                generar_arbol(elemento, nuevo_prefijo)

            elif elemento.is_file():
                if (
                    elemento.suffix.lower() in extensiones_texto
                    or elemento.name in {
                        "Dockerfile", "Makefile",
                        ".gitignore", ".htaccess"
                    }
                ):
                    archivos_texto.append(elemento)

    generar_arbol(raiz)

    markdown.write("```\n\n")

    # ==========================================
    # 2. CONTENIDO DE LOS ARCHIVOS
    # ==========================================

    markdown.write("## 2. Contenido de los archivos\n\n")

    for archivo in archivos_texto:

        ruta_relativa = archivo.relative_to(raiz)

        print("Procesando:", ruta_relativa)

        try:
            contenido = archivo.read_text(encoding="utf-8")

            # Evitar archivos con contenido binario
            if "\x00" in contenido:
                continue

            lenguaje = lenguajes.get(
                archivo.suffix.lower(), "text"
            )

            markdown.write(f"### {ruta_relativa}\n\n")

            # Usar fences suficientemente largos
            # para no romper bloques internos
            import re
            bloques = re.findall(r"`+", contenido)
            longitud = max(
                [3] + [len(b) + 1 for b in bloques]
            )
            fence = "`" * longitud

            markdown.write(f"{fence}{lenguaje}\n")
            markdown.write(contenido)

            if not contenido.endswith("\n"):
                markdown.write("\n")

            markdown.write(f"{fence}\n\n")

        except (UnicodeDecodeError, PermissionError, OSError) as error:
            print("Omitido:", archivo, error)

print(f"\nMarkdown generado: {salida}")
print(f"Archivos de texto encontrados: {len(archivos_texto)}")
