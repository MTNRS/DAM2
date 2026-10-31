# JOCARSA Cornell Box — PSSMLT OpenMP + cámara libre X11

C++17, OpenMP, PSSMLT con cadenas independientes, vista previa X11 y cámara navegable.

## Compilar en Ubuntu

```bash
sudo apt install g++ libx11-dev libgomp1
g++ -std=c++17 -O3 -march=native -fopenmp main.cpp -o cornell_mlt -lX11
```

## Ejecutar

```bash
./cornell_mlt --width 512 --height 512 --mutations 100000000 --depth 8 \
  --camera-x 0 --camera-y 1 --camera-z -1 \
  --target-x 0 --target-y 1 --target-z 1 --fov 75 \
  --threads 0 --preview 1 --persistence 0.88 \
  --move-speed 1.5 --mouse-sensitivity 0.0025 --output cornell.ppm
```

## Controles

- Clic izquierdo en ventana: capturar o liberar ratón.
- Ratón capturado: orientar la cámara (yaw y pitch).
- W/S: adelante/atrás (movimiento horizontal).
- A/D: izquierda/derecha.
- Q/E: bajar/subir.
- ESC: cerrar y guardar la última vista renderizada.

La cámara inicial está centrada horizontal y verticalmente respecto a la caja (0,1,-1) y mira a (0,1,1). La caja ocupa X=[-1,1], Y=[0,2], Z=[0,2].

## Persistencia visual

El framebuffer **de visualización** conserva una estela exponencial: `display = persistence * display + (1-persistence) * current`. Una persistencia mayor deja estelas más largas; `--persistence 0` las desactiva. Al mover la cámara, se invalidan las muestras PSSMLT de la cámara anterior y se recalcula bootstrap al detenerse. La estela previa se desvanece hacia negro; el archivo PPM final NO contiene la estela (se guarda el render de la última cámara).

La vista previa se actualiza cada ~50 ms en el hilo principal. OpenMP procesa el transporte luminoso. Sin DISPLAY usar `--preview 0` (no hay navegación interactiva).

El límite `--mutations` se aplica a la vista actual y se reinicia al mover la cámara. ESC guarda las mutaciones acumuladas de la vista final aunque no se haya alcanzado el límite.

**Limitación:** la vista libre no ofrece un render rápido de rasterización durante el desplazamiento: mientras la cámara se mueve, se muestra el desvanecimiento del frame anterior; al detenerse, el PSSMLT reinicia la acumulación de la nueva vista. No es MLT bidireccional y no incluye NEE.
