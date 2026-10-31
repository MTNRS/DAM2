
#include <iostream>
#include <cstdio>
#include <jpeglib.h>

int main() {

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

    unsigned char* fila =
        new unsigned char[ancho * canales];

    std::cout << "Imagen: "
              << ancho << "x" << alto << "\n";

    while (info.output_scanline < info.output_height) {

        int y = info.output_scanline;

        JSAMPROW puntero = fila;
        jpeg_read_scanlines(&info, &puntero, 1);

        for (int x = 0; x < ancho; x++) {

            int r = fila[x * canales + 0];
            int g = fila[x * canales + 1];
            int b = fila[x * canales + 2];

            std::cout
                << "(" << x << "," << y << ") "
                << "R:" << r << " "
                << "G:" << g << " "
                << "B:" << b << "\n";
        }
    }

    delete[] fila;

    jpeg_finish_decompress(&info);
    jpeg_destroy_decompress(&info);

    fclose(archivo);

    return 0;
}
