# JOCARSA Cornell CUDA v7 — adaptive batches + meg_labyrinth

Experimental CUDA PSSMLT renderer. Requires NVIDIA CUDA toolkit and X11.

## Build (RTX 4060 laptop)

```bash
nvcc -O3 -std=c++17 -arch=sm_89 main.cu -o cornell_cuda -lX11
```

## Run

```bash
./cornell_cuda --scene meg_labyrinth --width 512 --height 512 \
 --mutations 100000000 --depth 8 --bootstrap 8192 \
 --chains 1024 --batch-mutations 8 --gpu-pause-ms 0 \
 --camera-x 0 --camera-y 1.5 --camera-z -2.5 \
 --target-x 0 --target-y 1.5 --target-z 3 \
 --fov 75 --preview 1 --mouse-sensitivity 0.0004 \
 --persistence 0.96 --output meg_laberinto.ppm
```

Controls: WASD, Q/E, mouse after left click, ESC saves and quits.

## Changes

- New `meg_labyrinth`: 28x36 world with alternating walls, colored baffles, recessed wall emitters, ceiling lights and monoliths.
- Automatic batch controller: starts from `--batch-mutations` and adjusts iterations toward roughly 7–12 ms GPU kernels, capped at 256. A CPU-side X11 event poll occurs between kernel launches.
- Default GPU pause is 0 ms. Use `--gpu-pause-ms 1` if the display driver still stutters.
- Use `--chains 512` if the complex scene is sluggish. More chains are not always faster, because every bounce tests all scene quads.
- `--scene cornell` and `--scene labyrinth` remain supported.

## Caveats

- No NVIDIA CUDA compiler or GPU is available in the build environment; this version has not been compiled or benchmarked on real hardware.
- The initial bright patches are inherent to sparse PSSMLT samples, especially without next-event estimation (NEE). Higher bootstrap counts and more mutations help but do not eliminate them. NEE and MIS would be the next major image-quality upgrade.
- The adaptive controller synchronizes after each kernel to measure elapsed time. It prioritizes input responsiveness, not maximum possible GPU throughput. The much larger scene increases cost per mutation.
- The persistence effect is a display-only temporal trail, not physically correct integration across camera poses.
