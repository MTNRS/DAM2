import os
import re
from collections import Counter

CARPETA = "/var/www/html/jocarsa-formacion/recursos/transcripciones"

# Palabras que no aportan demasiado significado semántico
STOPWORDS = set("""
a al algo algunas algunos ante antes como con contra cual cuando
de del desde donde durante e el ella ellas ellos en entre era
es esa ese eso esta estaba estaban estas este esto estos fue
ha han hasta hay la las le les lo los más me mi mis muy
ni no nos o os otra otro para pero por porque que quien
se ser si sin sobre son su sus te tiene tienen tu tus
un una unas unos y ya yo aquí ahí allí así también
entonces bueno vale pues vamos tenemos esto esta
""".split())

contador = Counter()

# Recorrer todos los archivos TXT
for raiz, carpetas, archivos in os.walk(CARPETA):
    for archivo in archivos:
        if archivo.lower().endswith(".txt"):

            ruta = os.path.join(raiz, archivo)

            with open(ruta, "r", encoding="utf-8", errors="ignore") as f:
                for linea in f:

                    # Extraer únicamente líneas con marca de tiempo
                    coincidencia = re.match(
                        r"^\s*\[\d{2}:\d{2}:\d{2}\]\s*(.*)",
                        linea
                    )

                    if not coincidencia:
                        continue

                    texto = coincidencia.group(1).lower()

                    # Extraer palabras
                    palabras = re.findall(
                        r"[a-záéíóúüñ]+",
                        texto
                    )

                    for palabra in palabras:
                        if (
                            palabra not in STOPWORDS
                            and len(palabra) > 2
                        ):
                            contador[palabra] += 1

# Ordenar de mayor a menor frecuencia
print("Palabras únicas:", len(contador))
print("Palabras totales:", sum(contador.values()))

# Guardar resultados
with open("palabras_frecuencia.txt", "w", encoding="utf-8") as f:
    for palabra, frecuencia in contador.most_common():
        f.write(f"{palabra},{frecuencia}\n")

# Guardar solo vocabulario único
with open("vocabulario.txt", "w", encoding="utf-8") as f:
    for palabra, frecuencia in contador.most_common():
        f.write(palabra + "\n")

# Mostrar las 100 más frecuentes
for palabra, frecuencia in contador.most_common(100):
    print(f"{palabra:25} {frecuencia}")