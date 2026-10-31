# Cornell Box — PSSMLT, un núcleo

Compilación (Ubuntu):

```bash
g++ -std=c++17 -O3 -march=native main.cpp -o cornell_mlt
```

Ejecución:

```bash
./cornell_mlt 512 512 10000000 8 42
```

Argumentos: ancho, alto, mutaciones, rebotes máximos, semilla. Produce `cornell.ppm` en el directorio actual.

Conversión opcional a PNG:

```bash
magick cornell.ppm cornell.png
# o: ffmpeg -i cornell.ppm cornell.png
```

El renderizador utiliza un solo hilo. Implementa Metropolis-Hastings en espacio primario de muestras, con mutaciones gaussianas envueltas y saltos globales, transporte difuso y estimación de normalización por bootstrap. Es una versión educativa de PSSMLT, no MLT bidireccional completo. Las imágenes con pocas mutaciones presentan ruido y correlación de cadena; usar decenas de millones de mutaciones para mejor calidad.
