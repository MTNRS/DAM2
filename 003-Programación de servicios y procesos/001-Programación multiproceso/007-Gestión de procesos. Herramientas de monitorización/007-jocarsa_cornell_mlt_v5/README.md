# JOCARSA Cornell PSSMLT v5 — solo MLT + persistencia visual

- Se ha eliminado por completo `fastPreview()` y su iluminación aproximada: **no se utiliza rasterizado para la vista previa**.
- Durante WASD y giro de ratón se reinician las cadenas para la nueva cámara y se generan muestras **PSSMLT**. Para reducir latencia se utiliza bootstrap interactivo de hasta 2048 muestras; si no encuentra un camino luminoso, se conserva la estela previa y se reintenta.
- La persistencia visual es un buffer separado del acumulador físico. En cada actualización los colores anteriores se multiplican por `--persistence`; los píxeles no negros del MLT actual se componen por encima a opacidad completa. Los píxeles negros (sin muestras) se consideran transparentes a efectos de la estela.
- La imagen PPM final se exporta desde la acumulación MLT de la última cámara, **sin estelas**.

## Compilar

```bash
sudo apt install g++ libx11-dev libgomp1
g++ -std=c++17 -O3 -march=native -fopenmp main.cpp -o cornell_mlt -lX11
```

## Ejecutar

```bash
./cornell_mlt --scene labyrinth --width 512 --height 512 \
  --mutations 100000000 --depth 8 --threads 0 \
  --camera-x 0 --camera-y 1.5 --camera-z -2.5 \
  --target-x 0 --target-y 1.5 --target-z 3 \
  --fov 75 --preview 1 --mouse-sensitivity 0.0008 \
  --persistence 0.96 --output laberinto.ppm
```

WASD: movimiento; Q/E: altura; ratón: mirar (clic izquierdo para capturar); ESC: salir.

**Limitaciones:** PSSMLT sin NEE puede tardar en encontrar luz al mover la cámara, especialmente en laberintos. En esos momentos se mantiene la imagen anterior desvaneciéndose; no se dibuja una geometría falsa. Un bootstrap completo se ejecuta al inicio; tras cambiar de cámara se usa un bootstrap corto para conservar la interacción. No se garantiza tasa de fotogramas fija.
