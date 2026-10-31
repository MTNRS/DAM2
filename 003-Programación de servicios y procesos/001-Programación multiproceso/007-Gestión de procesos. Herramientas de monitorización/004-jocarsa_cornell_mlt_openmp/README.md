# Cornell Box — PSSMLT OpenMP (C++17)

Renderizador educativo Primary Sample Space Metropolis Light Transport con cadenas independientes OpenMP, cámara 3D configurable y framebuffer X11 progresivo.

## Ubuntu

```bash
sudo apt install g++ libx11-dev libgomp1
g++ -std=c++17 -O3 -march=native -fopenmp main.cpp -o cornell_mlt -lX11
```

## Ejecución solicitada

```bash
./cornell_mlt --width 512 --height 512 --mutations 100000000 --depth 8 \
  --camera-x 0.3 --camera-y 1.2 --camera-z -1 \
  --target-x 0 --target-y 1 --target-z 1 --fov 75 --preview 1 \
  --threads 0 --output cornell.ppm
```

`--threads 0` usa el número de hilos predeterminado de OpenMP. `--threads 1` ejecuta una cadena en un solo hilo; `--threads 8` solicita ocho hilos.

`--camera-x/y/z` indica posición y `--target-x/y/z` el punto de enfoque. X horizontal, Y vertical, Z profundidad. Caja: X [-1,1], Y [0,2], Z [0,2]; frente abierto Z=0.

`--mutations` es el **total** entre todos los hilos, no por hilo. `--bootstrap` también es un total global. Las semillas de las cadenas son independientes y derivadas de `--seed`.

`--preview 1` muestra el framebuffer X11, actualizado aproximadamente cada 0,5 s (dependiendo del tiempo por lote). `--preview 0` permite renderizar sin DISPLAY.

```bash
./cornell_mlt --help
ffmpeg -i cornell.ppm cornell.png
```

## Arquitectura

- Bootstrap paralelo con estimación global de importancia y selección por reservorio ponderado por cadena.
- Una cadena de Markov, RNG y framebuffer independiente por hilo OpenMP.
- Mutaciones gaussianas envueltas y saltos independientes; aceptación Metropolis-Hastings.
- Sin bloqueos ni atomics por píxel. Sincronización y composición periódicas en hilo principal.
- Framebuffer X11 exclusivamente desde el hilo principal; progreso, aceptación y mutaciones/s.
- Imagen PPM final con tonemapping Reinhard + gamma.

**Limitaciones:** PSSMLT educativo difuso, sin NEE ni MLT bidireccional. La distribución de trayectorias puede generar ruido y correlaciones. El resultado exacto depende del número de hilos y de la semilla. La ventana X11 no ha sido validada visualmente en este entorno.
