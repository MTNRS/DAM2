# JOCARSA Cornell PSSMLT v4 — laberinto interactivo OpenMP

Renderizador educativo C++17/OpenMP, X11, navegación WASD y persistencia visual continua.

## Compilar

```bash
sudo apt install g++ libx11-dev libgomp1
g++ -std=c++17 -O3 -march=native -fopenmp main.cpp -o cornell_mlt -lX11
```

## Ejecutar

```bash
./cornell_mlt --scene labyrinth --width 512 --height 512 --mutations 100000000 --depth 8 --threads 0 --camera-x 0 --camera-y 1.5 --camera-z -2.5 --target-x 0 --target-y 1.5 --target-z 3 --fov 75 --preview 1 --mouse-sensitivity 0.0008 --persistence 0.88 --output laberinto.ppm
```

Escena original: `--scene cornell`. Para ayuda: `./cornell_mlt --help`.

WASD: movimiento horizontal; Q/E: bajar/subir; ratón: orientación con X invertida; clic: captura/liberación; ESC: guardar y salir.

## Implementación

- La escena `labyrinth` contiene paredes escalonadas, pasillos, obstáculos y paneles emisivos de colores, además de luminarias laterales.
- OpenMP paraleliza cadenas Metropolis independientes. Cada cambio de cámara invalida las cadenas y exige nuevo bootstrap.
- Durante el movimiento se renderiza una **vista rápida geométrica** en cada actualización, en lugar de congelar la última vista; al parar vuelve el render físicamente basado PSSMLT.
- El buffer temporal usa `max(imagen_actual, persistencia * imagen_anterior)` por canal, por lo que las estelas antiguas se apagan pero la nueva vista no se oscurece. El PPM final no incluye estelas.
- La vista rápida emplea sombreado aproximado, **no iluminación indirecta físicamente correcta**. El rebote de luz se calcula en la imagen PSSMLT acumulada cuando la cámara permanece quieta.
- PSSMLT no incluye NEE; con emisores pequeños y geometría compleja puede requerir muchas muestras o mostrar ruido. Para resultados robustos conviene incorporar NEE/MIS y una estrategia de bootstrap más eficiente.

## Nota de rendimiento

El raycast de previsualización recorre todos los polígonos. Resoluciones elevadas o equipos con pocos núcleos pueden reducir la fluidez de la navegación.
