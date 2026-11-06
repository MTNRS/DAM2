
#include <stdio.h>
#include <stdlib.h>
#include <stdint.h>
#include <string.h>

#define BUFFER_SIZE 65536

void jocarsa_descodificador(
    const uint8_t *entrada,
    const uint8_t *clave,
    uint8_t *salida,
    size_t longitud
) {
    for (size_t i = 0; i < longitud; i++) {
        salida[i] = (uint8_t)(
            (entrada[i] - clave[i] + 256) % 256
        );
    }
}

int main(int argc, char *argv[]) {
    if (argc != 4) {
        fprintf(stderr,
            "Uso: %s <entrada_cifrada> <clave> <salida>\n",
            argv[0]
        );
        return 1;
    }

    FILE *entrada = fopen(argv[1], "rb");
    FILE *archivo_clave = fopen(argv[2], "rb");

    if (!entrada || !archivo_clave) {
        perror("Error abriendo archivos");
        if (entrada) fclose(entrada);
        if (archivo_clave) fclose(archivo_clave);
        return 1;
    }

    FILE *salida = fopen(argv[3], "wb");

    if (!salida) {
        perror("Error creando salida");
        fclose(entrada);
        fclose(archivo_clave);
        return 1;
    }

    uint8_t buffer[BUFFER_SIZE];
    uint8_t clave[BUFFER_SIZE];
    uint8_t descifrado[BUFFER_SIZE];

    size_t leidos;

    while ((leidos = fread(buffer, 1, BUFFER_SIZE, entrada)) > 0) {
        if (fread(clave, 1, leidos, archivo_clave) != leidos) {
            fprintf(stderr, "Clave insuficiente\n");
            fclose(entrada);
            fclose(archivo_clave);
            fclose(salida);
            return 1;
        }

        jocarsa_descodificador(buffer, clave, descifrado, leidos);

        if (fwrite(descifrado, 1, leidos, salida) != leidos) {
            fprintf(stderr, "Error escribiendo salida\n");
            fclose(entrada);
            fclose(archivo_clave);
            fclose(salida);
            return 1;
        }
    }

    if (ferror(entrada) || ferror(archivo_clave)) {
        fprintf(stderr, "Error leyendo archivos\n");
        fclose(entrada);
        fclose(archivo_clave);
        fclose(salida);
        return 1;
    }

    if (fgetc(archivo_clave) != EOF) {
        fprintf(stderr, "La clave es más larga que el archivo\n");
        fclose(entrada);
        fclose(archivo_clave);
        fclose(salida);
        return 1;
    }

    fclose(entrada);
    fclose(archivo_clave);

    if (fclose(salida) != 0) {
        perror("Error cerrando salida");
        return 1;
    }

    printf("Archivo descifrado: %s\n", argv[3]);
    return 0;
}
