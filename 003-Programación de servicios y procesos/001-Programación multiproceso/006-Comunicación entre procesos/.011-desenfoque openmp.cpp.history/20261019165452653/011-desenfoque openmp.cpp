
#include <iostream>
#include <cstdio>
#include <vector>
#include <cmath>
#include <algorithm>
#include <chrono>
#include <omp.h>
#include <jpeglib.h>

const double SIGMA = 5.0;
const int RADIO = 15;

int main() {

    auto inicio = std::chrono::steady_clock::now();

    std::cout << "Hilos disponibles: "
              << omp_get_max_threads() << "\n";

    // ABRIR IMAGEN
    FILE* archivo = fopen("josevicenterecortado.jpg", "rb");

    if (!archivo) {
        std::cerr << "No se puede abrir la imagen\n";
        return 1;
    }

    jpeg_decompress_struct info;
    jpeg_error_mgr error;

    info.err = jpeg_std_error(&error);
    jpeg_create_decompress(&info);
    jpeg_stdio_src(&info, archivo);

    jpeg_read_header(&info, TRUE);
    info.out_color_space = JCS_RGB;
    jpeg_start_decompress(&info);

    int ancho = info.output_width;
    int alto = info.output_height;
    int canales = info.output_components;

    size_t total = (size_t)ancho * alto * canales;

    std::vector<unsigned char> imagen(total);
    std::vector<float> temporal(total);
    std::vector<unsigned char> resultado(total);

    while (info.output_scanline < info.output_height) {
        JSAMPROW fila =
            &imagen[(size_t)info.output_scanline * ancho * canales];

        jpeg_read_scanlines(&info, &fila, 1);
    }

    jpeg_finish_decompress(&info);
    jpeg_destroy_decompress(&info);
    fclose(archivo);

    // CREAR KERNEL GAUSSIANO
    std::vector<double> kernel(2 * RADIO + 1);

    double suma = 0.0;

    for (int i = -RADIO; i <= RADIO; i++) {
        double valor = std::exp(
            -(i * i) / (2.0 * SIGMA * SIGMA)
        );

        kernel[i + RADIO] = valor;
        suma += valor;
    }

    for (double& valor : kernel) {
        valor /= suma;
    }

    auto inicio_calculo = std::chrono::steady_clock::now();

    // PASADA HORIZONTAL CON OPENMP
    #pragma omp parallel for schedule(static)
    for (int y = 0; y < alto; y++) {
        for (int x = 0; x < ancho; x++) {
            for (int c = 0; c < canales; c++) {

                double acumulado = 0.0;

                for (int k = -RADIO; k <= RADIO; k++) {
                    int xx = std::clamp(x + k, 0, ancho - 1);

                    size_t indice =
                        ((size_t)y * ancho + xx) * canales + c;

                    acumulado +=
                        imagen[indice] * kernel[k + RADIO];
                }

                temporal[((size_t)y * ancho + x) * canales + c]
                    = static_cast<float>(acumulado);
            }
        }
    }

    auto fin_horizontal = std::chrono::steady_clock::now();

    // PASADA VERTICAL CON OPENMP
    #pragma omp parallel for schedule(static)
    for (int y = 0; y < alto; y++) {
        for (int x = 0; x < ancho; x++) {
            for (int c = 0; c < canales; c++) {

                double acumulado = 0.0;

                for (int k = -RADIO; k <= RADIO; k++) {
                    int yy = std::clamp(y + k, 0, alto - 1);

                    size_t indice =
                        ((size_t)yy * ancho + x) * canales + c;

                    acumulado +=
                        temporal[indice] * kernel[k + RADIO];
                }

                size_t indice =
                    ((size_t)y * ancho + x) * canales + c;

                resultado[indice] = static_cast<unsigned char>(
                    std::clamp(std::round(acumulado), 0.0, 255.0)
                );
            }
        }
    }

    auto fin_calculo = std::chrono::steady_clock::now();

    // GUARDAR JPEG
    FILE* salida = fopen("gaussiano_openmp.jpg", "wb");

    if (!salida) {
        std::cerr << "No se puede crear la imagen\n";
        return 1;
    }

    jpeg_compress_struct compresor;
    jpeg_error_mgr error_compresor;

    compresor.err = jpeg_std_error(&error_compresor);
    jpeg_create_compress(&compresor);
    jpeg_stdio_dest(&compresor, salida);

    compresor.image_width = ancho;
    compresor.image_height = alto;
    compresor.input_components = 3;
    compresor.in_color_space = JCS_RGB;

    jpeg_set_defaults(&compresor);
    jpeg_set_quality(&compresor, 95, TRUE);

    jpeg_start_compress(&compresor, TRUE);

    while (compresor.next_scanline < compresor.image_height) {
        JSAMPROW fila =
            &resultado[(size_t)compresor.next_scanline * ancho * canales];

        jpeg_write_scanlines(&compresor, &fila, 1);
    }

    jpeg_finish_compress(&compresor);
    jpeg_destroy_compress(&compresor);
    fclose(salida);

    auto fin = std::chrono::steady_clock::now();

    // MEDIR TIEMPOS
    auto milisegundos = [](auto a, auto b) {
        return std::chrono::duration<double, std::milli>(
            b - a
        ).count();
    };

    std::cout << "\n--- RESULTADOS ---\n";
    std::cout << "Imagen: " << ancho << "x" << alto << "\n";
    std::cout << "Pixeles: " << (size_t)ancho * alto << "\n";
    std::cout << "Sigma: " << SIGMA << "\n";
    std::cout << "Radio: " << RADIO << "\n";
    std::cout << "Hilos OpenMP: " << omp_get_max_threads() << "\n";

    std::cout << "Tiempo horizontal: "
              << milisegundos(inicio_calculo, fin_horizontal)
              << " ms\n";

    std::cout << "Tiempo vertical: "
              << milisegundos(fin_horizontal, fin_calculo)
              << " ms\n";

    std::cout << "Tiempo calculo: "
              << milisegundos(inicio_calculo, fin_calculo)
              << " ms\n";

    std::cout << "Tiempo total: "
              << milisegundos(inicio, fin)
              << " ms\n";

    std::cout << "Guardado: gaussiano_openmp.jpg\n";

    return 0;
}
