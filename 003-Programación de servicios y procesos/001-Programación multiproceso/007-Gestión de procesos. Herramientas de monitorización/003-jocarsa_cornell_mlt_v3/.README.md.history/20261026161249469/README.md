# Cornell Box — PSSMLT (C++17, un núcleo)

Renderizador educativo de **Primary Sample Space Metropolis Light Transport** con cámara 3D configurable y framebuffer X11 en tiempo real.

## Instalar y compilar (Ubuntu)

```bash
sudo apt install g++ libx11-dev
g++ -std=c++17 -O3 -march=native main.cpp -o cornell_mlt -lX11
```

## Ejemplo

```bash
./cornell_mlt --width 512 --height 512 --mutations 10000000 --depth 8 \
  --camera-x 0.3 --camera-y 1.2 --camera-z -1 \
  --target-x 0 --target-y 1 --target-z 1 --fov 75 --preview 1 \
  --output cornell.ppm
```

`--camera-x/y/z` define la **posición** de cámara; `--target-x/y/z` define **hacia dónde mira**. X es horizontal, Y vertical y Z profundidad hacia el fondo de la caja. La caja ocupa X ∈ [-1,1], Y ∈ [0,2], Z ∈ [0,2]. La abertura frontal está en Z=0.

```bash
./cornell_mlt --help
```

El framebuffer X11 se actualiza cada ~0,5 segundos. El render sigue funcionando sin DISPLAY si `--preview 0`. El archivo final es PPM binario; convertir con `ffmpeg -i cornell.ppm cornell.png`.

## Notas técnicas

- Un solo hilo, sin `std::thread`, OpenMP ni GPU.
- PSSMLT con mutaciones gaussianas envueltas y saltos independientes.
- Bootstrap con estimación de normalización y selección por reservorio ponderado.
- Acumulación de propuestas aceptadas/rechazadas, exposición y tonemapping Reinhard + gamma.
- Geometría difusa y fuente emisiva; **no** es MLT bidireccional completo.
- No hay muestreo explícito de la fuente (NEE), por lo que puede presentar bastante ruido al principio.
