
#define _GNU_SOURCE
#include <stdio.h>
#include <stdlib.h>
#include <stdint.h>
#include <sys/random.h>
#include <errno.h>
#include <string.h>

#define BUFFER_SIZE 65536

int generar_clave(uint8_t *clave, size_t longitud) {
    size_t total = 0;

    while (total < longitud) {
        ssize_t n = getrandom(clave + total, longitud - total, 0);

        if (n < 0) {
            if (errno == EINTR) continue;
            return 0;
        }

        if (n == 0) return 0;
        total += (size_t)n;
    }

    return 1;
}

void jocarsa_codificador(
    const uint8_t *entrada,
    const uint8_t *clave,
    uint8_t *salida,
    size_t longitud
) {
    for (size_t i = 0; i < longitud; i++) {
        salida[i] = (uint8_t)(
            (entrada[i] + clave[i]) % 256
        );
    }
}

int main(int argc, char *argv[]) {
    if (argc != 4) {
        fprintf(stderr,
            "Uso: %s <entrada> <salida> <clave>\n",
            argv[0]
        );
        return 1;
    }

    FILE *entrada = fopen(argv[1], "rb");
    if (!entrada) {
        perror("Error abriendo entrada");
        return 1;
    }

    FILE *salida = fopen(argv[2], "wb");
    FILE *archivo_clave = fopen(argv[3], "wb");

    if (!salida || !archivo_clave) {
        perror("Error creando archivos");
        fclose(entrada);
        if (salida) fclose(salida);
        if (archivo_clave) fclose(archivo_clave);
        return 1;
    }

    uint8_t buffer[BUFFER_SIZE];
    uint8_t clave[BUFFER_SIZE];
    uint8_t cifrado[BUFFER_SIZE];

    size_t leidos;

    while ((leidos = fread(buffer, 1, BUFFER_SIZE, entrada)) > 0) {
        if (!generar_clave(clave, leidos)) {
            fprintf(stderr, "Error generando clave\n");
            fclose(entrada);
            fclose(salida);
            fclose(archivo_clave);
            return 1;
        }

        jocarsa_codificador(buffer, clave, cifrado, leidos);

        if (fwrite(cifrado, 1, leidos, salida) != leidos ||
            fwrite(clave, 1, leidos, archivo_clave) != leidos) {
            fprintf(stderr, "Error escribiendo archivos\n");
            fclose(entrada);
            fclose(salida);
            fclose(archivo_clave);
            return 1;
        }
    }

    if (ferror(entrada) || fclose(salida) != 0 ||
        fclose(archivo_clave) != 0) {
        fprintf(stderr, "Error de lectura o escritura\n");
        fclose(entrada);
        return 1;
    }

    fclose(entrada);

    printf("Archivo cifrado: %s\n", argv[2]);
    printf("Clave generada: %s\n", argv[3]);

    return 0;
}
