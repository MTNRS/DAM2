# Cornell Box PSSMLT — cámara configurable y framebuffer en vivo

Renderizador CPU secuencial (un único hilo) con vista previa X11 en Ubuntu. Sin OpenGL ni SDL.

## Compilar

```bash
sudo apt install g++ libx11-dev
 g++ -std=c++17 -O3 -march=native main.cpp -o cornell_mlt -lX11
```

## Ejecutar

```bash
./cornell_mlt 512 512 10000000 8 42 65 1
```

Parámetros posicionales: `ancho alto mutaciones rebotes semilla FOV_grados preview(1|0)`.

- `FOV_grados`: ángulo de visión **vertical**, de 1 a 169 grados. Por defecto 55. Un valor mayor abre la cámara y hace que la caja ocupe menos imagen. Prueba 45, 65, 80.
- `preview`: 1 abre ventana X11 con framebuffer progresivo; 0 desactiva ventana (servidores sin pantalla).
- La ventana se refresca aproximadamente cada 0,5 segundos. La imagen se guarda al final en `cornell.ppm`.
- La vista previa usa el estimador acumulado normalizado por las mutaciones completadas.
- Solo se usa el hilo principal para render y ventana.

Para convertir la imagen: `ffmpeg -i cornell.ppm cornell.png`.

Nota: se trata de PSSMLT educativo con superficies difusas, no MLT bidireccional completo. Si se cierra externamente la ventana de previsualización, es preferible mantenerla abierta hasta finalizar el render.
