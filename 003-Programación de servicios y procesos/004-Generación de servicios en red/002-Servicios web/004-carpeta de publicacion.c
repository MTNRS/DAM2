
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <sys/stat.h>

#define PUERTO 8000
#define CARPETA "public"
#define BUFFER 4096

int main() {

    int servidor = socket(AF_INET, SOCK_STREAM, 0);

    int opcion = 1;
    setsockopt(servidor, SOL_SOCKET, SO_REUSEADDR, &opcion, sizeof(opcion));

    struct sockaddr_in direccion = {0};
    direccion.sin_family = AF_INET;
    direccion.sin_port = htons(PUERTO);
    direccion.sin_addr.s_addr = INADDR_ANY;

    bind(servidor, (struct sockaddr*)&direccion, sizeof(direccion));
    listen(servidor, 10);

    printf("Servidor iniciado en http://localhost:%d\n", PUERTO);

    while (1) {

        int cliente = accept(servidor, NULL, NULL);

        if (cliente < 0) {
            continue;
        }

        char peticion[BUFFER] = {0};
        recv(cliente, peticion, sizeof(peticion) - 1, 0);

        char metodo[16] = {0};
        char ruta[1024] = {0};

        sscanf(peticion, "%15s %1023s", metodo, ruta);

        printf("Peticion: %s %s\n", metodo, ruta);

        if (strcmp(metodo, "GET") != 0) {
            char respuesta[] =
                "HTTP/1.1 405 Method Not Allowed\r\n"
                "Content-Length: 0\r\n"
                "Connection: close\r\n\r\n";

            send(cliente, respuesta, strlen(respuesta), 0);
            close(cliente);
            continue;
        }

        if (strcmp(ruta, "/") == 0) {
            strcpy(ruta, "/index.html");
        }

        // Evitar salir de la carpeta public
        if (strstr(ruta, "..") != NULL) {
            char respuesta[] =
                "HTTP/1.1 403 Forbidden\r\n"
                "Content-Length: 0\r\n"
                "Connection: close\r\n\r\n";

            send(cliente, respuesta, strlen(respuesta), 0);
            close(cliente);
            continue;
        }

        char archivo[2048];
        snprintf(archivo, sizeof(archivo), "%s%s", CARPETA, ruta);

        FILE *f = fopen(archivo, "rb");

        if (f == NULL) {
            char contenido[] = "<h1>404 - Archivo no encontrado</h1>";
            char cabecera[512];

            snprintf(cabecera, sizeof(cabecera),
                "HTTP/1.1 404 Not Found\r\n"
                "Content-Type: text/html; charset=utf-8\r\n"
                "Content-Length: %zu\r\n"
                "Connection: close\r\n\r\n",
                strlen(contenido)
            );

            send(cliente, cabecera, strlen(cabecera), 0);
            send(cliente, contenido, strlen(contenido), 0);
            close(cliente);
            continue;
        }

        struct stat datos;
        fstat(fileno(f), &datos);

        char cabecera[512];

        snprintf(cabecera, sizeof(cabecera),
            "HTTP/1.1 200 OK\r\n"
            "Content-Type: text/html; charset=utf-8\r\n"
            "Content-Length: %lld\r\n"
            "Connection: close\r\n\r\n",
            (long long)datos.st_size
        );

        send(cliente, cabecera, strlen(cabecera), 0);

        char buffer[BUFFER];
        size_t leidos;

        while ((leidos = fread(buffer, 1, sizeof(buffer), f)) > 0) {
            size_t enviados = 0;

            while (enviados < leidos) {
                ssize_t n = send(
                    cliente,
                    buffer + enviados,
                    leidos - enviados,
                    0
                );

                if (n <= 0) {
                    break;
                }

                enviados += n;
            }

            if (enviados < leidos) {
                break;
            }
        }

        fclose(f);
        close(cliente);
    }

    close(servidor);
    return 0;
}
