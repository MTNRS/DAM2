
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <sys/stat.h>
#include <limits.h>
#include <errno.h>

#define PUERTO 8000
#define CARPETA "public"
#define BUFFER 4096
#define MAX_PROFUNDIDAD 10

// Añadir texto a un buffer dinámico
int agregar(char **destino, size_t *longitud,
            const char *texto, size_t cantidad) {

    if (cantidad > SIZE_MAX - *longitud - 1) {
        return 0;
    }

    char *nuevo = realloc(*destino, *longitud + cantidad + 1);

    if (nuevo == NULL) {
        return 0;
    }

    *destino = nuevo;

    memcpy(*destino + *longitud, texto, cantidad);
    *longitud += cantidad;
    (*destino)[*longitud] = '\0';

    return 1;
}

// Leer un archivo completo
char *leer_archivo(const char *ruta) {

    FILE *archivo = fopen(ruta, "rb");

    if (archivo == NULL) {
        return NULL;
    }

    char *contenido = malloc(1);

    if (contenido == NULL) {
        fclose(archivo);
        return NULL;
    }

    contenido[0] = '\0';
    size_t longitud = 0;
    char buffer[BUFFER];
    size_t leidos;

    while ((leidos = fread(buffer, 1, sizeof(buffer), archivo)) > 0) {

        if (!agregar(&contenido, &longitud, buffer, leidos)) {
            free(contenido);
            fclose(archivo);
            return NULL;
        }
    }

    if (ferror(archivo)) {
        free(contenido);
        fclose(archivo);
        return NULL;
    }

    fclose(archivo);
    return contenido;
}

// Comprobar que una ruta está dentro de public
int ruta_permitida(const char *ruta) {

    char raiz[PATH_MAX];
    char real[PATH_MAX];

    if (realpath(CARPETA, raiz) == NULL ||
        realpath(ruta, real) == NULL) {
        return 0;
    }

    size_t longitud = strlen(raiz);

    return strncmp(raiz, real, longitud) == 0 &&
           (real[longitud] == '/' || real[longitud] == '\0');
}

// Procesar las directivas de inclusión
char *procesar_html(const char *ruta, int profundidad) {

    if (profundidad > MAX_PROFUNDIDAD) {
        return strdup("<!-- Profundidad maxima de inclusion -->");
    }

    if (!ruta_permitida(ruta)) {
        return NULL;
    }

    char *original = leer_archivo(ruta);

    if (original == NULL) {
        return NULL;
    }

    char *resultado = malloc(1);

    if (resultado == NULL) {
        free(original);
        return NULL;
    }

    resultado[0] = '\0';
    size_t longitud = 0;

    const char *cursor = original;
    const char *inicio_directiva = "<#incluir \"";
    const char *fin_directiva = "\" #>";

    while (*cursor) {

        const char *inicio = strstr(cursor, inicio_directiva);

        if (inicio == NULL) {
            agregar(&resultado, &longitud, cursor, strlen(cursor));
            break;
        }

        agregar(&resultado, &longitud, cursor, inicio - cursor);

        const char *nombre_inicio = inicio + strlen(inicio_directiva);
        const char *nombre_fin = strstr(nombre_inicio, fin_directiva);

        if (nombre_fin == NULL) {
            agregar(&resultado, &longitud, inicio, strlen(inicio));
            break;
        }

        char nombre[PATH_MAX];
        size_t tamano = nombre_fin - nombre_inicio;

        if (tamano >= sizeof(nombre)) {
            cursor = nombre_fin + strlen(fin_directiva);
            continue;
        }

        memcpy(nombre, nombre_inicio, tamano);
        nombre[tamano] = '\0';

        // Resolver el archivo respecto al HTML actual
        char directorio[PATH_MAX];
        snprintf(directorio, sizeof(directorio), "%s", ruta);

        char *ultima_barra = strrchr(directorio, '/');

        if (ultima_barra != NULL) {
            *ultima_barra = '\0';
        } else {
            strcpy(directorio, CARPETA);
        }

        char ruta_incluida[PATH_MAX];

        int n = snprintf(
            ruta_incluida,
            sizeof(ruta_incluida),
            "%s/%s",
            directorio,
            nombre
        );

        if (n > 0 && (size_t)n < sizeof(ruta_incluida)) {

            char *incluido = procesar_html(
                ruta_incluida,
                profundidad + 1
            );

            if (incluido != NULL) {
                agregar(
                    &resultado,
                    &longitud,
                    incluido,
                    strlen(incluido)
                );

                free(incluido);
            } else {
                const char *error = "<!-- Archivo incluido no encontrado -->";

                agregar(
                    &resultado,
                    &longitud,
                    error,
                    strlen(error)
                );
            }
        }

        cursor = nombre_fin + strlen(fin_directiva);
    }

    free(original);
    return resultado;
}

// Enviar todos los bytes de una respuesta
int enviar_todo(int cliente, const char *datos, size_t longitud) {

    size_t enviados = 0;

    while (enviados < longitud) {

        ssize_t n = send(
            cliente,
            datos + enviados,
            longitud - enviados,
            0
        );

        if (n <= 0) {
            return 0;
        }

        enviados += n;
    }

    return 1;
}

// Enviar una respuesta HTTP
void responder(int cliente, int codigo,
               const char *estado, const char *contenido) {

    char cabecera[512];
    size_t longitud = strlen(contenido);

    snprintf(
        cabecera,
        sizeof(cabecera),
        "HTTP/1.1 %d %s\r\n"
        "Content-Type: text/html; charset=utf-8\r\n"
        "Content-Length: %zu\r\n"
        "Connection: close\r\n"
        "\r\n",
        codigo,
        estado,
        longitud
    );

    enviar_todo(cliente, cabecera, strlen(cabecera));
    enviar_todo(cliente, contenido, longitud);
}

int main() {

    int servidor = socket(AF_INET, SOCK_STREAM, 0);

    if (servidor < 0) {
        perror("socket");
        return 1;
    }

    int opcion = 1;

    setsockopt(
        servidor,
        SOL_SOCKET,
        SO_REUSEADDR,
        &opcion,
        sizeof(opcion)
    );

    struct sockaddr_in direccion = {0};

    direccion.sin_family = AF_INET;
    direccion.sin_port = htons(PUERTO);
    direccion.sin_addr.s_addr = INADDR_ANY;

    if (bind(
        servidor,
        (struct sockaddr *)&direccion,
        sizeof(direccion)
    ) < 0) {
        perror("bind");
        close(servidor);
        return 1;
    }

    if (listen(servidor, 10) < 0) {
        perror("listen");
        close(servidor);
        return 1;
    }

    printf("Servidor en http://localhost:%d\n", PUERTO);

    while (1) {

        int cliente = accept(servidor, NULL, NULL);

        if (cliente < 0) {
            continue;
        }

        char peticion[BUFFER] = {0};

        ssize_t recibidos = recv(
            cliente,
            peticion,
            sizeof(peticion) - 1,
            0
        );

        if (recibidos <= 0) {
            close(cliente);
            continue;
        }

        char metodo[16] = {0};
        char ruta[1024] = {0};

        if (sscanf(peticion, "%15s %1023s", metodo, ruta) != 2) {
            responder(cliente, 400, "Bad Request", "<h1>400</h1>");
            close(cliente);
            continue;
        }

        printf("%s %s\n", metodo, ruta);

        if (strcmp(metodo, "GET") != 0) {
            responder(cliente, 405, "Method Not Allowed", "<h1>405</h1>");
            close(cliente);
            continue;
        }

        // Eliminar parámetros de consulta
        char *consulta = strchr(ruta, '?');

        if (consulta != NULL) {
            *consulta = '\0';
        }

        if (strcmp(ruta, "/") == 0) {
            strcpy(ruta, "/index.html");
        }

        // Solo archivos HTML en esta versión
        size_t longitud_ruta = strlen(ruta);

        if (longitud_ruta < 5 ||
            strcmp(ruta + longitud_ruta - 5, ".html") != 0) {
            responder(cliente, 403, "Forbidden", "<h1>403</h1>");
            close(cliente);
            continue;
        }

        char archivo[PATH_MAX];

        int n = snprintf(
            archivo,
            sizeof(archivo),
            "%s%s",
            CARPETA,
            ruta
        );

        if (n < 0 || (size_t)n >= sizeof(archivo)) {
            responder(cliente, 414, "URI Too Long", "<h1>414</h1>");
            close(cliente);
            continue;
        }

        char *contenido = procesar_html(archivo, 0);

        if (contenido == NULL) {
            responder(
                cliente,
                404,
                "Not Found",
                "<h1>404 - Archivo no encontrado</h1>"
            );
        } else {
            responder(cliente, 200, "OK", contenido);
            free(contenido);
        }

        close(cliente);
    }

    close(servidor);
    return 0;
}
