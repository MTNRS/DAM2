# JOCARSA | CUDA PSSMLT (versión experimental)

Versión GPU de Cornell/laberinto. Cada hilo CUDA mantiene una cadena Metropolis independiente, con estado Philox; el kernel ejecuta mutaciones en paralelo y acumula aportaciones con `atomicAdd`. La ventana X11 sigue en CPU, con WASD, ratón invertido horizontalmente, ESC y persistencia visual temporal. **No hay previsualización OpenGL ni rasterizado**.

## Compilación Ubuntu con CUDA Toolkit de NVIDIA

```bash
nvcc -O3 -std=c++17 -arch=sm_89 main.cu -o cornell_cuda -lX11
```

`sm_89` corresponde a Ada (RTX 4060). Para RTX 5060/5060 Ti Blackwell, utiliza un toolkit con soporte para `sm_120` y cambia a `-arch=sm_120`. Si la arquitectura no está soportada, actualiza CUDA Toolkit. También necesitas `libx11-dev`.

## Ejemplo

```bash
./cornell_cuda --scene labyrinth --width 512 --height 512 \
  --mutations 100000000 --depth 8 --bootstrap 100000 --chains 4096 \
  --camera-x 0 --camera-y 1.5 --camera-z -2.5 \
  --target-x 0 --target-y 1.5 --target-z 3 --fov 75 \
  --preview 1 --mouse-sensitivity 0.0004 --persistence 0.96 \
  --output laberinto.ppm
```

`--chains` controla cadenas paralelas en GPU; `--threads` se acepta pero no controla CUDA. `--preview 0` desactiva X11. La imagen final PPM no contiene estelas. El número de mutaciones puede superar ligeramente el objetivo para completar el último lote GPU.

## Limitaciones

- PSSMLT difuso educativo, no MLT bidireccional; no NEE, por lo que escenas indirectas pueden tardar en iluminarse.
- Se reinician las cadenas cuando cambia la cámara, pero el framebuffer de persistencia no se borra.
- El bootstrap y las copias GPU-CPU son síncronas, y pueden reducir la fluidez al mover la cámara.
- Memoria de cadena significativa (muestras de hasta 66 dimensiones); ajustar `--chains` si es necesario.
- No ha sido posible compilar ni ejecutar esta variante aquí porque este entorno no tiene `nvcc` ni GPU NVIDIA. Se requiere validación en el equipo CUDA de destino.
