# JOCARSA Cornell CUDA — edición responsiva

Se ha reducido el trabajo por lanzamiento CUDA para mejorar la respuesta de X11, sin modificar el trazador PSSMLT ni la persistencia visual.

## Compilación (RTX 4060)

```bash
nvcc -O3 -std=c++17 -arch=sm_89 main.cu -o cornell_cuda -lX11
```

## Ejecución recomendada

```bash
./cornell_cuda --scene labyrinth --width 512 --height 512 \
 --mutations 100000000 --depth 8 --bootstrap 8192 \
 --chains 512 --batch-mutations 2 --gpu-pause-ms 2 \
 --camera-x 0 --camera-y 1.5 --camera-z -2.5 \
 --target-x 0 --target-y 1.5 --target-z 3 \
 --fov 75 --preview 1 --mouse-sensitivity 0.0004 \
 --persistence 0.96 --output laberinto.ppm
```

- `--chains`: cadenas paralelas (predeterminado 512).
- `--batch-mutations`: iteraciones por cadena en cada lanzamiento (predeterminado 2).
- `--gpu-pause-ms`: descanso de CPU entre lanzamientos para dar margen al escritorio (predeterminado 2 ms).
- La transferencia completa del framebuffer ocurre aproximadamente cada 120 ms.
- Bootstrap reducido a 256 muestras mientras la cámara se mueve (8192 en reposo).

Los kernels CUDA no son interrumpibles instantáneamente por X11. Si sigue habiendo latencia, prueba `--chains 128 --batch-mutations 1 --gpu-pause-ms 5`. Esto reduce rendimiento de renderizado, pero puede mejorar interactividad.

**Nota:** no se ha compilado con `nvcc` aquí; se requiere validar en el equipo NVIDIA. El código conserva la implementación PSSMLT existente y el efecto de persistencia visual.
