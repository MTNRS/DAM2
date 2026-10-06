#!/usr/bin/env python3

import sys
import json
import re
import time
import urllib.request
import urllib.error
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

# Número de subtítulos enviados juntos.
# 20 da contexto suficiente sin hacer prompts demasiado grandes.
TAMANO_BLOQUE = 20

# Contexto máximo de Ollama.
# IMPORTANTE:
# Granite estaba intentando usar 131072 tokens y generaba
# una KV cache enorme en RAM.
NUM_CTX = 4096

# Mantener modelo cargado entre traducciones
KEEP_ALIVE = "30m"

# Timeout de cada petición
TIMEOUT = 600

# Número de intentos por bloque
MAX_REINTENTOS = 3


# ============================================================
# CONVERTIR VTT EN SUBTÍTULOS
# ============================================================

def leer_vtt(ruta):

    contenido = ruta.read_text(
        encoding="utf-8"
    )

    # Normalizamos saltos de línea
    contenido = contenido.replace(
        "\r\n",
        "\n"
    )

    contenido = contenido.replace(
        "\r",
        "\n"
    )

    # Dividir por bloques separados por líneas vacías
    bloques = re.split(
        r"\n\s*\n",
        contenido.strip()
    )

    subtitulos = []

    for bloque in bloques:

        bloque = bloque.strip()

        if not bloque:
            continue

        lineas = bloque.split("\n")

        # Ignorar cabecera WEBVTT
        if lineas[0].strip().startswith("WEBVTT"):
            continue

        # Buscar línea que contiene -->
        indice_tiempo = None

        for i, linea in enumerate(lineas):

            if "-->" in linea:

                indice_tiempo = i

                break

        # No es un bloque de subtítulos
        if indice_tiempo is None:
            continue

        tiempo = lineas[
            indice_tiempo
        ].strip()

        # Todo lo que viene después de la línea temporal
        # forma parte del texto.
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
# COMPROBAR OLLAMA
# ============================================================

def comprobar_ollama():

    url = "http://127.0.0.1:11434/api/tags"

    try:

        with urllib.request.urlopen(
            url,
            timeout=10
        ) as respuesta:

            return respuesta.status == 200

    except Exception:

        return False


# ============================================================
# LLAMAR A OLLAMA
# ============================================================

def llamar_ollama(prompt):

    datos = {

        "model": MODELO,

        "prompt": prompt,

        "stream": False,

        # Mantener Granite cargado mientras traducimos
        # todos los idiomas.
        "keep_alive": KEEP_ALIVE,

        "options": {

            # Traducción lo más determinista posible
            "temperature": 0,

            # MUY IMPORTANTE:
            # limitar contexto para evitar el OOM.
            "num_ctx": NUM_CTX
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
        timeout=TIMEOUT
    ) as respuesta:

        contenido = respuesta.read().decode(
            "utf-8"
        )

        resultado = json.loads(
            contenido
        )

    if "response" not in resultado:

        raise RuntimeError(
            "Ollama no devolvió el campo 'response'"
        )

    return resultado[
        "response"
    ].strip()


# ============================================================
# EXTRAER JSON DE LA RESPUESTA
# ============================================================

def extraer_json(texto):

    texto = texto.strip()

    # El modelo puede decidir envolver el resultado en:
    #
    # ```json
    # [...]
    # ```
    #
    # Lo eliminamos.

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

    # Buscamos el primer [
    # y el último ]

    inicio = texto.find("[")
    final = texto.rfind("]")

    if inicio == -1 or final == -1:

        raise ValueError(
            "Ollama no devolvió un array JSON válido.\n\n"
            "Respuesta recibida:\n"
            + texto[:1000]
        )

    json_texto = texto[
        inicio:final + 1
    ]

    try:

        return json.loads(
            json_texto
        )

    except json.JSONDecodeError as error:

        raise ValueError(
            "No se pudo interpretar el JSON devuelto "
            "por Ollama:\n"
            + json_texto[:1000]
        ) from error


# ============================================================
# TRADUCIR UN BLOQUE
# ============================================================

def traducir_bloque(
    bloque,
    idioma
):

    elementos = []

    for i, subtitulo in enumerate(
        bloque
    ):

        elementos.append({

            "id": i,

            "text": subtitulo[
                "texto"
            ]
        })

    entrada_json = json.dumps(
        elementos,
        ensure_ascii=False,
        indent=2
    )

    prompt = f"""
You are a professional subtitle translator.

Translate the following subtitles from Spanish to {idioma}.

CONTEXT:

The subtitles come from a technical programming and computer science course.
The speaker is a programming teacher explaining concepts, source code,
web development, databases, multimedia and software development.

Technical terminology may include:

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
framework
library
HTTP
URL
JSON
XML
code
source code

TRANSLATION RULES:

1. Translate ONLY the value of "text".

2. NEVER change "id".

3. Return EXACTLY the same number of elements.

4. Preserve programming terminology when appropriate.

5. Do not translate programming identifiers, variable names,
   function names or literal source-code expressions unnecessarily.

6. Produce natural, fluent {idioma}.

7. These are subtitles, so keep translations reasonably concise.

8. Use surrounding subtitles as context.

9. Do NOT explain your translation.

10. Do NOT add comments.

11. Do NOT add markdown.

12. Return ONLY valid JSON.

13. The output MUST have exactly this structure:

[
  {{
    "id": 0,
    "text": "translated text"
  }},
  {{
    "id": 1,
    "text": "translated text"
  }}
]

INPUT:

{entrada_json}
"""

    respuesta = llamar_ollama(
        prompt
    )

    resultado = extraer_json(
        respuesta
    )

    # ========================================================
    # VALIDAR CANTIDAD
    # ========================================================

    if len(resultado) != len(bloque):

        raise ValueError(

            f"Se esperaban {len(bloque)} "
            f"subtítulos pero Ollama devolvió "
            f"{len(resultado)}"

        )

    # ========================================================
    # VALIDAR IDS
    # ========================================================

    traducciones = {}

    for elemento in resultado:

        if not isinstance(
            elemento,
            dict
        ):

            raise ValueError(
                "Ollama devolvió un elemento "
                "que no es un objeto JSON"
            )

        if "id" not in elemento:

            raise ValueError(
                "Falta 'id' en un elemento"
            )

        if "text" not in elemento:

            raise ValueError(
                "Falta 'text' en un elemento"
            )

        identificador = int(
            elemento["id"]
        )

        traducciones[
            identificador
        ] = str(
            elemento["text"]
        )

    # ========================================================
    # REORDENAR
    # ========================================================

    salida = []

    for i in range(
        len(bloque)
    ):

        if i not in traducciones:

            raise ValueError(
                f"Falta el subtítulo con id {i}"
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

    total = len(
        subtitulos
    )

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
            f"    "
            f"{inicio + 1:04d}-{final:04d}"
            f" / "
            f"{total:04d}",
            flush=True
        )

        exito = False

        ultimo_error = None

        # ====================================================
        # REINTENTOS
        # ====================================================

        for intento in range(
            1,
            MAX_REINTENTOS + 1
        ):

            try:

                traducido = traducir_bloque(
                    bloque,
                    idioma
                )

                traducciones.extend(
                    traducido
                )

                exito = True

                break

            except Exception as error:

                ultimo_error = error

                print(
                    f"        Error intento "
                    f"{intento}/{MAX_REINTENTOS}: "
                    f"{error}"
                )

                # Si Ollama se ha caído,
                # esperamos antes de reintentar.

                if intento < MAX_REINTENTOS:

                    print(
                        "        Esperando 3 segundos..."
                    )

                    time.sleep(3)

                    if not comprobar_ollama():

                        print(
                            "        Ollama no responde."
                        )

                    else:

                        print(
                            "        Ollama responde. "
                            "Reintentando..."
                        )

        # ====================================================
        # SI TODOS LOS INTENTOS FALLAN
        # ====================================================

        if not exito:

            raise RuntimeError(
                "\nNo se pudo traducir el bloque "
                f"{inicio + 1}-{final} "
                f"después de {MAX_REINTENTOS} intentos.\n"
                f"Último error: {ultimo_error}"
            )

    return traducciones


# ============================================================
# GUARDAR VTT
# ============================================================

def guardar_vtt(
    ruta,
    subtitulos,
    traducciones
):

    if len(subtitulos) != len(
        traducciones
    ):

        raise ValueError(
            "El número de traducciones no coincide "
            "con el número de subtítulos."
        )

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

            # IMPORTANTE:
            # copiamos literalmente el timestamp original.
            archivo.write(
                subtitulo["tiempo"]
            )

            archivo.write(
                "\n"
            )

            archivo.write(
                traduccion.strip()
            )

            archivo.write(
                "\n\n"
            )


# ============================================================
# MOSTRAR CONFIGURACIÓN
# ============================================================

def mostrar_configuracion():

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
        "Modelo       :",
        MODELO
    )

    print(
        "Contexto     :",
        NUM_CTX,
        "tokens"
    )

    print(
        "Bloque       :",
        TAMANO_BLOQUE,
        "subtítulos"
    )

    print(
        "Keep alive   :",
        KEEP_ALIVE
    )

    print(
        "Reintentos   :",
        MAX_REINTENTOS
    )

    print()


# ============================================================
# MAIN
# ============================================================

def main():

    mostrar_configuracion()

    # ========================================================
    # COMPROBAR ARGUMENTOS
    # ========================================================

    if len(sys.argv) < 2:

        print(
            "Uso:"
        )

        print()

        print(
            "python3 012-traducir.py archivo.vtt"
        )

        print()

        sys.exit(1)

    ruta = Path(
        sys.argv[1]
    )

    # ========================================================
    # COMPROBAR ARCHIVO
    # ========================================================

    if not ruta.exists():

        print(
            "ERROR: No existe el archivo:"
        )

        print(
            ruta
        )

        sys.exit(1)

    if ruta.suffix.lower() != ".vtt":

        print(
            "ERROR: El archivo debe ser .vtt"
        )

        sys.exit(1)

    # ========================================================
    # COMPROBAR OLLAMA
    # ========================================================

    print(
        "Comprobando Ollama..."
    )

    if not comprobar_ollama():

        print()

        print(
            "ERROR: Ollama no responde en:"
        )

        print(
            "http://127.0.0.1:11434"
        )

        print()

        print(
            "Comprueba:"
        )

        print()

        print(
            "systemctl status ollama"
        )

        sys.exit(1)

    print(
        "Ollama: OK"
    )

    print()

    # ========================================================
    # LEER VTT
    # ========================================================

    print(
        "Archivo:",
        ruta
    )

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
            "ERROR: No se encontraron subtítulos."
        )

        sys.exit(1)

    # ========================================================
    # MOSTRAR PRIMER SUBTÍTULO
    # ========================================================

    print(
        "Primer subtítulo:"
    )

    print(
        "   ",
        subtitulos[0]["tiempo"]
    )

    print(
        "   ",
        subtitulos[0]["texto"]
    )

    print()

    # ========================================================
    # TRADUCIR TODOS LOS IDIOMAS
    # ========================================================

    archivos_generados = []

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

        # ----------------------------------------------------
        # NOMBRE DEL ARCHIVO
        # ----------------------------------------------------

        salida = ruta.with_name(

            ruta.stem
            +
            "."
            +
            codigo
            +
            ".vtt"

        )

        print(
            "Salida:",
            salida
        )

        print()

        # ----------------------------------------------------
        # TRADUCIR
        # ----------------------------------------------------

        traducciones = traducir_subtitulos(
            subtitulos,
            idioma
        )

        # ----------------------------------------------------
        # GUARDAR
        # ----------------------------------------------------

        guardar_vtt(
            salida,
            subtitulos,
            traducciones
        )

        archivos_generados.append(
            salida
        )

        print()

        print(
            "    OK:",
            salida
        )

        print()

    # ========================================================
    # FIN
    # ========================================================

    print(
        "=" * 60
    )

    print(
        "PROCESO FINALIZADO"
    )

    print(
        "=" * 60
    )

    print()

    print(
        "Archivos generados:"
    )

    print()

    for archivo in archivos_generados:

        print(
            "  ",
            archivo
        )

    print()


if __name__ == "__main__":

    try:

        main()

    except KeyboardInterrupt:

        print()
        print()
        print(
            "Proceso cancelado por el usuario."
        )

        sys.exit(130)

    except Exception as error:

        print()
        print(
            "=" * 60
        )

        print(
            "ERROR"
        )

        print(
            "=" * 60
        )

        print(
            error
        )

        sys.exit(1)