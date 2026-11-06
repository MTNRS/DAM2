#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <sys/socket.h>
#include <netinet/in.h>

int main() {
    int servidor = socket(AF_INET, SOCK_STREAM, 0);

    struct sockaddr_in direccion;
    direccion.sin_family = AF_INET;
    direccion.sin_port = htons(8000);
    direccion.sin_addr.s_addr = INADDR_ANY;

    bind(servidor, (struct sockaddr*)&direccion, sizeof(direccion));
    listen(servidor, 5);

    printf("Servidor en http://localhost:8000\n");

    while (1) {
        int cliente = accept(servidor, NULL, NULL);

        char peticion[4096];
        recv(cliente, peticion, sizeof(peticion), 0);

        char respuesta[] =
            "HTTP/1.1 200 OK\r\n"
            "Content-Type: text/html; charset=utf-8\r\n"
            "Content-Length: 10\r\n"
            "Connection: close\r\n"
            "\r\n"
            "Hola Mundo";

        send(cliente, respuesta, strlen(respuesta), 0);
        close(cliente);
    }

    close(servidor);
    return 0;
}
