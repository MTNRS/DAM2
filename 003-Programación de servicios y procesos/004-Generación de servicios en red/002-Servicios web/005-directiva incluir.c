
#define _DEFAULT_SOURCE

#include <stdio.h>
#include <stdlib.h>
#include <stdint.h>
#include <string.h>
#include <unistd.h>
#include <errno.h>
#include <limits.h>
#include <signal.h>
#include <sys/socket.h>
#include <sys/stat.h>
#include <netinet/in.h>

#define PUERTO 8000
#define CARPETA "public"
#define BUFFER 4096
#define MAX_PROFUNDIDAD 10
#define MAX_HTML (16 * 1024 * 1024)

// ============================================================
// TIPOS MIME
// ============================================================

const char *tipo_mime(const char *ruta) {

    const char *extension = strrchr(ruta, '.');

    if (extension == NULL) {
        return "application/octet-stream";
    }

    if (strcmp(extension, ".html") == 0)
        return "text/html; charset=utf-8";

    if (strcmp(extension, ".htm") == 0)
        return "text/html; charset=utf-8";

    if (strcmp(extension, ".css") == 0)
        return "text/css; charset=utf-8";

    if (strcmp(extension, ".js") == 0)
        return "text/javascript; charset=utf-8";

    if (strcmp(extension, ".mjs") == 0)
        return "text/javascript; charset=utf-8";

    if (strcmp(extension, ".json") == 0)
        return "application/json";

    if (strcmp(extension, ".txt") == 0)
        return "text/plain; charset=utf-8";

    if (strcmp(extension, ".xml") == 0)
        return "application/xml";

    if (strcmp(extension, ".png") == 0)
        return "image/png";

    if (strcmp(extension, ".jpg") == 0)
        return "image/jpeg";

    if (strcmp(extension, ".jpeg") == 0)
        return "image/jpeg";

    if (strcmp(extension, ".gif") == 0)
        return "image/gif";

    if (strcmp(extension, ".svg") == 0)
        return "image/svg+xml";

    if (strcmp(extension, ".webp") == 0)
        return "image/webp";

    if (strcmp(extension, ".ico") == 0)
        return "image/x-icon";

    if (strcmp(extension, ".woff") == 0)
        return "font/woff";

    if (strcmp(extension, ".woff2") == 0)
        return "font/woff2";

    if (strcmp(extension, ".ttf") == 0)
        return "font/ttf";

    if (strcmp(extension, ".otf") == 0)
        return "font/otf";

    if (strcmp(extension, ".pdf") == 0)
        return "application/pdf";

    if (strcmp(extension, ".mp4") == 0)
        return "video/mp4";

    if (strcmp(extension, ".webm") == 0)
        return "video/webm";

    if (strcmp(extension, ".mp3") == 0)
        return "audio/mpeg";

    return "application/octet-stream";
}

// ============================================================
// COMPROBAR SI ES HTML
// ============================================================

int es_html(const char *ruta) {

    const char *extension = strrchr(ruta, '.');

    if (extension == NULL) {
        return 0;
    }

    return strcmp(extension, ".html") == 0 ||
           strcmp(extension, ".htm") == 0;
}

// ============================================================
// BUFFER DINAMICO
// ============================================================

int agregar(
    char **destino,
    size_t *longitud,
    const char *texto,
    size_t cantidad
) {

    if (cantidad > MAX_HTML ||
        *longitud > MAX_HTML - cantidad) {
        return 0;
    }

    char *nuevo = realloc(
        *destino,
        *longitud + cantidad + 1
    );

    if (nuevo == NULL) {
        return 0;
    }

    *destino = nuevo;

    memcpy(
        *destino + *longitud,
        texto,
        cantidad
    );

    *longitud += cantidad;

    (*destino)[*longitud] = '\0';

    return 1;
}

// ============================================================
// LEER ARCHIVO DE TEXTO
// ============================================================

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

    while (
        (leidos = fread(
            buffer,
            1,
            sizeof(buffer),
            archivo
        )) > 0
    ) {

        if (!agregar(
            &contenido,
            &longitud,
            buffer,
            leidos
        )) {

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

// ============================================================
// SEGURIDAD DE RUTAS
// ============================================================

int ruta_permitida(const char *ruta) {

    char raiz[PATH_MAX];
    char real[PATH_MAX];

    if (realpath(CARPETA, raiz) == NULL) {
        return 0;
    }

    if (realpath(ruta, real) == NULL) {
        return 0;
    }

    size_t longitud = strlen(raiz);

    if (strncmp(raiz, real, longitud) != 0) {
        return 0;
    }

    if (
        real[longitud] != '/' &&
        real[longitud] != '\0'
    ) {
        return 0;
    }

    struct stat datos;

    if (stat(real, &datos) != 0) {
        return 0;
    }

    return S_ISREG(datos.st_mode);
}

// ============================================================
// MOTOR DE INCLUSIONES HTML
// ============================================================

char *procesar_html(
    const char *ruta,
    int profundidad
) {

    if (profundidad > MAX_PROFUNDIDAD) {
        return strdup(
            "<!-- Profundidad maxima de inclusion -->"
        );
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

        const char *inicio = strstr(
            cursor,
            inicio_directiva
        );

        if (inicio == NULL) {

            if (!agregar(
                &resultado,
                &longitud,
                cursor,
                strlen(cursor)
            )) {
                goto error;
            }

            break;
        }

        if (!agregar(
            &resultado,
            &longitud,
            cursor,
            inicio - cursor
        )) {
            goto error;
        }

        const char *nombre_inicio =
            inicio + strlen(inicio_directiva);

        const char *nombre_fin = strstr(
            nombre_inicio,
            fin_directiva
        );

        if (nombre_fin == NULL) {

            if (!agregar(
                &resultado,
                &longitud,
                inicio,
                strlen(inicio)
            )) {
                goto error;
            }

            break;
        }

        char nombre[PATH_MAX];

        size_t tamano =
            nombre_fin - nombre_inicio;

        if (tamano >= sizeof(nombre)) {

            cursor =
                nombre_fin + strlen(fin_directiva);

            continue;
        }

        memcpy(
            nombre,
            nombre_inicio,
            tamano
        );

        nombre[tamano] = '\0';

        // Directorio del archivo actual

        char directorio[PATH_MAX];

        snprintf(
            directorio,
            sizeof(directorio),
            "%s",
            ruta
        );

        char *ultima_barra = strrchr(
            directorio,
            '/'
        );

        if (ultima_barra != NULL) {
            *ultima_barra = '\0';
        } else {
            strcpy(directorio, CARPETA);
        }

        // Ruta del archivo incluido

        char ruta_incluida[PATH_MAX];

        int n = snprintf(
            ruta_incluida,
            sizeof(ruta_incluida),
            "%s/%s",
            directorio,
            nombre
        );

        if (
            n > 0 &&
            (size_t)n < sizeof(ruta_incluida)
        ) {

            char *incluido = procesar_html(
                ruta_incluida,
                profundidad + 1
            );

            if (incluido != NULL) {

                int correcto = agregar(
                    &resultado,
                    &longitud,
                    incluido,
                    strlen(incluido)
                );

                free(incluido);

                if (!correcto) {
                    goto error;
                }

            } else {

                const char *mensaje =
                    "<!-- Archivo incluido no encontrado -->";

                if (!agregar(
                    &resultado,
                    &longitud,
                    mensaje,
                    strlen(mensaje)
                )) {
                    goto error;
                }
            }
        }

        cursor =
            nombre_fin + strlen(fin_directiva);
    }

    free(original);

    return resultado;

error:

    free(original);
    free(resultado);

    return NULL;
}

// ============================================================
// ENVIAR DATOS COMPLETOS
// ============================================================

int enviar_todo(
    int cliente,
    const void *datos,
    size_t longitud
) {

    const char *buffer = datos;

    size_t enviados = 0;

    while (enviados < longitud) {

        ssize_t n = send(
            cliente,
            buffer + enviados,
            longitud - enviados,
            MSG_NOSIGNAL
        );

        if (n < 0 && errno == EINTR) {
            continue;
        }

        if (n <= 0) {
            return 0;
        }

        enviados += n;
    }

    return 1;
}

// ============================================================
// ENVIAR CABECERAS HTTP
// ============================================================

int enviar_cabecera(
    int cliente,
    int codigo,
    const char *estado,
    const char *mime,
    size_t longitud
) {

    char cabecera[1024];

    int n = snprintf(
        cabecera,
        sizeof(cabecera),
        "HTTP/1.1 %d %s\r\n"
        "Content-Type: %s\r\n"
        "Content-Length: %zu\r\n"
        "Connection: close\r\n"
        "X-Content-Type-Options: nosniff\r\n"
        "\r\n",
        codigo,
        estado,
        mime,
        longitud
    );

    if (n < 0 || (size_t)n >= sizeof(cabecera)) {
        return 0;
    }

    return enviar_todo(
        cliente,
        cabecera,
        (size_t)n
    );
}

// ============================================================
// RESPUESTA HTTP DE TEXTO
// ============================================================

void responder(
    int cliente,
    int codigo,
    const char *estado,
    const char *contenido
) {

    size_t longitud = strlen(contenido);

    if (!enviar_cabecera(
        cliente,
        codigo,
        estado,
        "text/html; charset=utf-8",
        longitud
    )) {
        return;
    }

    enviar_todo(
        cliente,
        contenido,
        longitud
    );
}

// ============================================================
// SERVIR ARCHIVO ESTATICO
// ============================================================

void servir_estatico(
    int cliente,
    const char *ruta
) {

    if (!ruta_permitida(ruta)) {

        responder(
            cliente,
            404,
            "Not Found",
            "<h1>404 - Archivo no encontrado</h1>"
        );

        return;
    }

    FILE *archivo = fopen(ruta, "rb");

    if (archivo == NULL) {

        responder(
            cliente,
            404,
            "Not Found",
            "<h1>404 - Archivo no encontrado</h1>"
        );

        return;
    }

    struct stat datos;

    if (fstat(fileno(archivo), &datos) != 0 ||
        !S_ISREG(datos.st_mode) ||
        datos.st_size < 0) {

        fclose(archivo);

        responder(
            cliente,
            500,
            "Internal Server Error",
            "<h1>500 - Error de archivo</h1>"
        );

        return;
    }

    size_t tamano = (size_t)datos.st_size;

    const char *mime = tipo_mime(ruta);

    if (!enviar_cabecera(
        cliente,
        200,
        "OK",
        mime,
        tamano
    )) {

        fclose(archivo);
        return;
    }

    char buffer[BUFFER];

    size_t leidos;

    while (
        (leidos = fread(
            buffer,
            1,
            sizeof(buffer),
            archivo
        )) > 0
    ) {

        if (!enviar_todo(
            cliente,
            buffer,
            leidos
        )) {
            break;
        }
    }

    fclose(archivo);
}

// ============================================================
// SERVIR ARCHIVO HTML
// ============================================================

void servir_html(
    int cliente,
    const char *ruta
) {

    if (!ruta_permitida(ruta)) {

        responder(
            cliente,
            404,
            "Not Found",
            "<h1>404 - Archivo no encontrado</h1>"
        );

        return;
    }

    char *contenido = procesar_html(ruta, 0);

    if (contenido == NULL) {

        responder(
            cliente,
            500,
            "Internal Server Error",
            "<h1>500 - Error procesando HTML</h1>"
        );

        return;
    }

    size_t longitud = strlen(contenido);

    if (enviar_cabecera(
        cliente,
        200,
        "OK",
        "text/html; charset=utf-8",
        longitud
    )) {

        enviar_todo(
            cliente,
            contenido,
            longitud
        );
    }

    free(contenido);
}

// ============================================================
// PROCESAR PETICION HTTP
// ============================================================

void procesar_peticion(int cliente) {

    char peticion[BUFFER] = {0};

    ssize_t recibidos = recv(
        cliente,
        peticion,
        sizeof(peticion) - 1,
        0
    );

    if (recibidos <= 0) {
        return;
    }

    char metodo[16] = {0};
    char ruta[1024] = {0};

    if (sscanf(
        peticion,
        "%15s %1023s",
        metodo,
        ruta
    ) != 2) {

        responder(
            cliente,
            400,
            "Bad Request",
            "<h1>400 - Peticion incorrecta</h1>"
        );

        return;
    }

    printf("%s %s\n", metodo, ruta);
    fflush(stdout);

    if (strcmp(metodo, "GET") != 0) {

        responder(
            cliente,
            405,
            "Method Not Allowed",
            "<h1>405 - Metodo no permitido</h1>"
        );

        return;
    }

    // Eliminar parámetros GET y fragmentos

    char *consulta = strpbrk(ruta, "?#");

    if (consulta != NULL) {
        *consulta = '\0';
    }

    // Comprobar ruta inicial

    if (ruta[0] != '/') {

        responder(
            cliente,
            400,
            "Bad Request",
            "<h1>400 - Ruta incorrecta</h1>"
        );

        return;
    }

    // Evitar rutas con directorios superiores

    if (strstr(ruta, "..") != NULL ||
        strchr(ruta, '\\') != NULL) {

        responder(
            cliente,
            403,
            "Forbidden",
            "<h1>403 - Acceso prohibido</h1>"
        );

        return;
    }

    // Página por defecto

    if (strcmp(ruta, "/") == 0) {
        strcpy(ruta, "/index.html");
    }

    // Construir ruta física

    char archivo[PATH_MAX];

    int n = snprintf(
        archivo,
        sizeof(archivo),
        "%s%s",
        CARPETA,
        ruta
    );

    if (
        n < 0 ||
        (size_t)n >= sizeof(archivo)
    ) {

        responder(
            cliente,
            414,
            "URI Too Long",
            "<h1>414 - Ruta demasiado larga</h1>"
        );

        return;
    }

    // Comprobar existencia y ubicación

    if (!ruta_permitida(archivo)) {

        responder(
            cliente,
            404,
            "Not Found",
            "<h1>404 - Archivo no encontrado</h1>"
        );

        return;
    }

    // HTML: procesar inclusiones

    if (es_html(archivo)) {

        servir_html(
            cliente,
            archivo
        );

    } else {

        // CSS, JS, imágenes, fuentes, etc.

        servir_estatico(
            cliente,
            archivo
        );
    }
}

// ============================================================
// SERVIDOR PRINCIPAL
// ============================================================

int main() {

    signal(SIGPIPE, SIG_IGN);

    int servidor = socket(
        AF_INET,
        SOCK_STREAM,
        0
    );

    if (servidor < 0) {
        perror("socket");
        return 1;
    }

    int opcion = 1;

    if (setsockopt(
        servidor,
        SOL_SOCKET,
        SO_REUSEADDR,
        &opcion,
        sizeof(opcion)
    ) < 0) {
        perror("setsockopt");
    }

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

    printf("\n");
    printf("====================================\n");
    printf(" SERVIDOR WEB EN C\n");
    printf("====================================\n");
    printf(" Puerto: %d\n", PUERTO);
    printf(" Carpeta: %s\n", CARPETA);
    printf(" URL: http://localhost:%d\n", PUERTO);
    printf(" Inclusiones: <#incluir \"archivo.html\" #>\n");
    printf("====================================\n\n");

    fflush(stdout);

    while (1) {

        int cliente = accept(
            servidor,
            NULL,
            NULL
        );

        if (cliente < 0) {

            if (errno == EINTR) {
                continue;
            }

            perror("accept");
            continue;
        }

        procesar_peticion(cliente);

        close(cliente);
    }

    close(servidor);

    return 0;
}
