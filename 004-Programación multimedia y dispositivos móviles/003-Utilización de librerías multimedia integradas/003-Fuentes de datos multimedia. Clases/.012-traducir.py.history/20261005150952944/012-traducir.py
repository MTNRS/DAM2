#!/usr/bin/env python3

import sys
import json
import re
import urllib.request
from pathlib import Path


# ============================================================
# CONFIGURACIÓN
# ============================================================

OLLAMA_URL = "http://127.0.0.1:11434/api/generate"

MODELO = "granite4.2:latest"

IDIOMAS = {
    "en": "English",
    "fr": "French",
    "de": "German",
    "it": "Italian",
    "pt": "Portuguese"
}

# Número de subtítulos enviados juntos al modelo.
# Así el modelo dispone de contexto.
TAMANO_BLOQUE = 30


# ============================================================
# LEER VTT
# ============================================================

def leer_vtt(ruta):

    contenido = ruta.read_text(
        encoding="utf-8"
    )

    contenido = contenido.replace(
        "\r\n",
        "\n"
    )

    bloques = re.split(
        r"\n\s*\n",
        contenido.strip()
    )

    subtitulos = []

    for bloque in bloques:

        lineas = bloque.split("\n")

        # Ignorar cabecera WEBVTT
        if lineas[0].strip().startswith("WEBVTT"):
            continue

        indice_tiempo = None

        for i, linea in enumerate(lineas):

            if "-->" in linea:
                indice_tiempo = i
                break

        if indice_tiempo is None:
            continue

        tiempo = lineas[indice_tiempo].strip()

        texto = "\n".join(
            lineas[indice_tiempo + 1:]
        ).strip()

        if not texto:
            continue

        subtitulos.append({
            "tiempo": tiempo,
            "texto": texto
        })

    return subtitulos


# ============================================================
# LLAMAR A OLLAMA
# ============================================================

def llamar_ollama(prompt):

    datos = {
        "model": MODELO,
        "prompt": prompt,
        "stream": False,

        # Queremos traducción determinista
        "options": {
            "temperature": 0
        }
    }

    cuerpo = json.dumps(
        datos
    ).encode("utf-8")

    peticion = urllib.request.Request(
        OLLAMA_URL,
        data=cuerpo,
        headers={
            "Content-Type": "application/json"
        },
        method="POST"
    )

    with urllib.request.urlopen(
        peticion,
        timeout=600
    ) as respuesta:

        resultado = json.loads(
            respuesta.read().decode("utf-8")
        )

    return resultado["response"].strip()


# ============================================================
# EXTRAER JSON DE LA RESPUESTA
# ============================================================

def extraer_json(texto):

    texto = texto.strip()

    # Quitar posible bloque markdown
    texto = re.sub(
        r"^```(?:json)?\s*",
        "",
        texto,
        flags=re.IGNORECASE
    )

    texto = re.sub(
        r"\s*```$",
        "",
        texto
    )

    # Buscar array JSON
    inicio = texto.find("[")
    final = texto.rfind("]")

    if inicio == -1 or final == -1:
        raise ValueError(
            "Ollama no devolvió un array JSON"
        )

    return json.loads(
        texto[inicio:final + 1]
    )


# ============================================================
# TRADUCIR BLOQUE
# ============================================================

def traducir_bloque(
    bloque,
    idioma
):

    elementos = []

    for i, subtitulo in enumerate(bloque):

        elementos.append({
            "id": i,
            "text": subtitulo["texto"]
        })

    entrada_json = json.dumps(
        elementos,
        ensure_ascii=False,
        indent=2
    )

    prompt = f"""
You are translating subtitles from Spanish to {idioma}.

These subtitles come from a technical programming course.

Important terminology may include:

HTML
CSS
JavaScript
Python
PHP
SQL
API
JSON
DOM
input
button
form
array
object
function
variable
class
method
database
server
client
frontend
backend

RULES:

1. Translate ONLY the value of "text".
2. NEVER change the "id".
3. Return EXACTLY the same number of elements.
4. Preserve technical terminology when appropriate.
5. Produce natural {idioma}.
6. Do not explain anything.
7. Do not add markdown.
8. Return ONLY valid JSON.
9. Keep each translation reasonably concise because these are subtitles.
10. Use the surrounding subtitles as context for ambiguous sentences.

Input:

{entrada_json}

Return this exact structure:

[
  {{
    "id": 0,
    "text": "translated text"
  }}
]
"""

    respuesta = llamar_ollama(
        prompt
    )

    resultado = extraer_json(
        respuesta
    )

    if len(resultado) != len(bloque):

        raise ValueError(
            f"Se esperaban {len(bloque)} "
            f"subtítulos pero Ollama devolvió "
            f"{len(resultado)}"
        )

    # Comprobamos IDs
    traducciones = {}

    for elemento in resultado:

        traducciones[
            int(elemento["id"])
        ] = elemento["text"]

    salida = []

    for i in range(len(bloque)):

        if i not in traducciones:

            raise ValueError(
                f"Falta el subtítulo {i}"
            )

        salida.append(
            traducciones[i]
        )

    return salida


# ============================================================
# TRADUCIR TODOS LOS SUBTÍTULOS
# ============================================================

def traducir_subtitulos(
    subtitulos,
    idioma
):

    traducciones = []

    total = len(subtitulos)

    for inicio in range(
        0,
        total,
        TAMANO_BLOQUE
    ):

        final = min(
            inicio + TAMANO_BLOQUE,
            total
        )

        bloque = subtitulos[
            inicio:final
        ]

        print(
            f"    {inicio + 1:04d}-{final:04d}"
            f" / {total:04d}",
            flush=True
        )

        try:

            traducido = traducir_bloque(
                bloque,
                idioma
            )

            traducciones.extend(
                traducido
            )

        except Exception as error:

            print()
            print(
                "    ERROR:",
                error
            )

            print(
                "    Reintentando bloque..."
            )

            # Segundo intento
            traducido = traducir_bloque(
                bloque,
                idioma
            )

            traducciones.extend(
                traducido
            )

    return traducciones


# ============================================================
# CREAR VTT
# ============================================================

def guardar_vtt(
    ruta,
    subtitulos,
    traducciones
):

    with ruta.open(
        "w",
        encoding="utf-8"
    ) as archivo:

        archivo.write(
            "WEBVTT\n\n"
        )

        for subtitulo, traduccion in zip(
            subtitulos,
            traducciones
        ):

            archivo.write(
                subtitulo["tiempo"]
            )

            archivo.write("\n")

            archivo.write(
                traduccion.strip()
            )

            archivo.write(
                "\n\n"
            )


# ============================================================
# MAIN
# ============================================================

def main():

    if len(sys.argv) < 2:

        print(
            "Uso:"
        )

        print(
            "python3 traducir_subtitulos.py archivo.vtt"
        )

        sys.exit(1)


    ruta = Path(
        sys.argv[1]
    )


    if not ruta.exists():

        print(
            "No existe:",
            ruta
        )

        sys.exit(1)


    if ruta.suffix.lower() != ".vtt":

        print(
            "El archivo debe ser .vtt"
        )

        sys.exit(1)


    print()
    print(
        "=" * 60
    )

    print(
        "TRADUCTOR AUTOMÁTICO DE SUBTÍTULOS"
    )

    print(
        "=" * 60
    )

    print(
        "Archivo:",
        ruta
    )

    print(
        "Modelo:",
        MODELO
    )


    # --------------------------------------------------------
    # LEER ORIGINAL
    # --------------------------------------------------------

    subtitulos = leer_vtt(
        ruta
    )


    print(
        "Subtítulos:",
        len(subtitulos)
    )

    print()


    if not subtitulos:

        print(
            "No se encontraron subtítulos."
        )

        sys.exit(1)


    # --------------------------------------------------------
    # TRADUCIR IDIOMAS
    # --------------------------------------------------------

    for codigo, idioma in IDIOMAS.items():

        print(
            "=" * 60
        )

        print(
            f"{codigo.upper()} -> {idioma}"
        )

        print(
            "=" * 60
        )


        traducciones = traducir_subtitulos(
            subtitulos,
            idioma
        )


        # archivo.vtt
        #
        # pasa a:
        #
        # archivo.en.vtt

        salida = ruta.with_name(
            ruta.stem +
            "." +
            codigo +
            ".vtt"
        )


        guardar_vtt(
            salida,
            subtitulos,
            traducciones
        )


        print(
            "    OK:",
            salida
        )

        print()


    print(
        "=" * 60
    )

    print(
        "PROCESO FINALIZADO"
    )

    print(
        "=" * 60
    )


if __name__ == "__main__":
    main()