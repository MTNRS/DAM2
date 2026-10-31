
#include <iostream>
#include <cstdio>
#include <vector>
#include <chrono>
#include <jpeglib.h>

int main() {

    auto inicio = std::chrono::high_resolution_clock::now();

    // ABRIR IMAGEN ORIGINAL
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

    std::vector<unsigned char> imagen(
        ancho * alto * canales
    );

    // LEER IMAGEN COMPLETA
    while (info.output_scanline < info.output_height) {
        JSAMPROW fila =
            &imagen[info.output_scanline * ancho * canales];

        jpeg_read_scanlines(&info, &fila, 1);
    }

    jpeg_finish_decompress(&info);
    jpeg_destroy_decompress(&info);
    fclose(archivo);

    // CALCULAR NEGATIVO
    auto inicio_calculo = std::chrono::high_resolution_clock::now();

    for (size_t i = 0; i < imagen.size(); i++) {
        imagen[i] = 255 - imagen[i];
    }

    auto fin_calculo = std::chrono::high_resolution_clock::now();

    // GUARDAR IMAGEN NEGATIVA
    FILE* salida = fopen("negativo.jpg", "wb");

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
            &imagen[compresor.next_scanline * ancho * canales];

        jpeg_write_scanlines(&compresor, &fila, 1);
    }

    jpeg_finish_compress(&compresor);
    jpeg_destroy_compress(&compresor);
    fclose(salida);

    auto fin = std::chrono::high_resolution_clock::now();

    // MEDIR TIEMPOS
    double tiempo_calculo =
        std::chrono::duration<double, std::milli>(
            fin_calculo - inicio_calculo
        ).count();

    double tiempo_total =
        std::chrono::duration<double, std::milli>(
            fin - inicio
        ).count();

    std::cout << "Imagen: " << ancho << "x" << alto << "\n";
    std::cout << "Pixeles: " << ancho * alto << "\n";
    std::cout << "Negativo guardado: negativo.jpg\n";
    std::cout << "Tiempo calculo: " << tiempo_calculo << " ms\n";
    std::cout << "Tiempo total: " << tiempo_total << " ms\n";

    return 0;
}
