
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
#include <arpa/inet.h>
#include <ctype.h>
#include <pwd.h>
#include <grp.h>
#include <openssl/ssl.h>
#include <openssl/err.h>
#include <dirent.h>
#include <sys/select.h>
#include <sys/time.h>

#define PUERTO 8000
#define CARPETA "public"
static char document_root[PATH_MAX]="public";
static SSL *tls_conn=NULL;
static int tls_fd=-1;
static int https_request=0;
static int tls_enabled=0, http_port=8000, https_port=8443, redirect_https=0;
static char vhosts_dir[PATH_MAX]="config/virtualhosts";
static char default_host[256]="localhost";
static volatile sig_atomic_t reload_requested=0;
static void reload_signal(int sig){(void)sig;reload_requested=1;}
#define MAX_VHOSTS 64
typedef struct {char domain[256],alias[256],root[PATH_MAX],cert[PATH_MAX],key[PATH_MAX];int redirect;SSL_CTX *ctx;} VHost;
static VHost hosts[MAX_VHOSTS];static int host_count=0;static VHost *current_host=NULL;
#define BUFFER 4096
#define MAX_PROFUNDIDAD 10
#define MAX_HTML (16 * 1024 * 1024)

typedef struct {
    int puerto, solo_local, timeout_ms, cpu_segundos, memoria_mb;
    int max_script_kb, max_salida_kb, max_post_bytes, max_conexiones;
    int max_archivos, max_procesos;
    char usuario[64];
} Config;
static Config cfg = {8000,1,3000,2,128,256,1024,8191,16,32,16,""};
static int leer_config(const char *ruta) {
    FILE *f=fopen(ruta,"r");
    if(!f) {perror(ruta);return 0;}
    char linea[512];int num=0;
    while(fgets(linea,sizeof linea,f)) {
        num++; char *p=linea;while(isspace((unsigned char)*p))p++;
        if(!*p||*p=='#'||*p==';'||*p=='[')continue;
        char *eq=strchr(p,'=');if(!eq){fprintf(stderr,"Configuracion linea %d invalida\n",num);fclose(f);return 0;}
        *eq++=0;char *end=p+strlen(p);while(end>p&&isspace((unsigned char)end[-1]))*--end=0;
        while(isspace((unsigned char)*eq))eq++;
        end=eq+strlen(eq);while(end>eq&&isspace((unsigned char)end[-1]))*--end=0;
        if(!strcmp(p,"escuchar_http")){http_port=atoi(eq);continue;}
        if(!strcmp(p,"escuchar_https")){https_port=atoi(eq);continue;}
        if(!strcmp(p,"ssl_habilitado")){tls_enabled=atoi(eq);continue;}
        if(!strcmp(p,"redirigir_https")){redirect_https=atoi(eq);continue;}
        if(!strcmp(p,"directorio_virtualhosts")){snprintf(vhosts_dir,sizeof vhosts_dir,"%s",eq);continue;}
        if(!strcmp(p,"host_por_defecto")){snprintf(default_host,sizeof default_host,"%s",eq);continue;}
        if(!strcmp(p,"usuario")){if(strlen(eq)>=sizeof cfg.usuario){fclose(f);return 0;}strcpy(cfg.usuario,eq);continue;}
        char *rest;long v=strtol(eq,&rest,10);
        if(*eq==0||*rest||v<0||v>10000000){fprintf(stderr,"Valor invalido: %s\n",p);fclose(f);return 0;}
        int *dst=NULL;
        #define OPT(k,field) if(!strcmp(p,k))dst=&cfg.field
        OPT("puerto",puerto);else OPT("solo_local",solo_local);else OPT("timeout_ms",timeout_ms);
        else OPT("cpu_segundos",cpu_segundos);else OPT("memoria_mb",memoria_mb);
        else OPT("max_script_kb",max_script_kb);else OPT("max_salida_kb",max_salida_kb);
        else OPT("max_post_bytes",max_post_bytes);else OPT("max_conexiones",max_conexiones);
        else OPT("max_archivos",max_archivos);else OPT("max_procesos",max_procesos);
        #undef OPT
        if(!dst){fprintf(stderr,"Opcion desconocida: %s\n",p);fclose(f);return 0;}
        *dst=(int)v;
    }
    fclose(f);
    if(http_port<0||http_port>65535||https_port<1||https_port>65535||tls_enabled>1||redirect_https>1){fprintf(stderr,"Puertos/TLS invalidos\n");return 0;}
    if(cfg.puerto<1||cfg.puerto>65535||cfg.solo_local>1||cfg.timeout_ms<100||cfg.timeout_ms>60000||cfg.cpu_segundos<1||cfg.cpu_segundos>60||cfg.memoria_mb<32||cfg.memoria_mb>4096||cfg.max_script_kb<1||cfg.max_script_kb>1024||cfg.max_salida_kb<1||cfg.max_salida_kb>16384||cfg.max_post_bytes<1||cfg.max_post_bytes>8191||cfg.max_conexiones<1||cfg.max_conexiones>128||cfg.max_archivos<4||cfg.max_archivos>1024||cfg.max_procesos<1||cfg.max_procesos>256) {fprintf(stderr,"Configuracion fuera de rango\n");return 0;}
    return 1;
}


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

    if (realpath(document_root, raiz) == NULL) {
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
            strcpy(directorio, document_root);
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


// Ejecuta bloques de lenguaje en proceso independiente.
// Fase inicial de desarrollo: no exponer a Internet sin aislamiento del proceso.
#include <sys/wait.h>
#include <sys/resource.h>
#include <time.h>
#include <fcntl.h>

#define INTERPRETE "./bin/jocarsa-lenguaje"
#define MAX_SCRIPT (256 * 1024)
static char request_method[8]="GET", request_query[4096]="", request_body[8192]="";

static char *ejecutar_codigo(const char *codigo, size_t tam, int *correcto) {
    *correcto = 0;
    if (tam > (size_t)cfg.max_script_kb*1024) return NULL;
    char script[] = "/tmp/jocarsa-script-XXXXXX";
    char salida[] = "/tmp/jocarsa-output-XXXXXX";
    int fd = mkstemp(script);
    if (fd < 0) return NULL;
    int out = mkstemp(salida);
    if (out < 0) { close(fd); unlink(script); return NULL; }
    unlink(salida);
    size_t pos = 0;
    while (pos < tam) {
        ssize_t n = write(fd, codigo + pos, tam - pos);
        if (n <= 0) { close(fd); close(out); unlink(script); return NULL; }
        pos += (size_t)n;
    }
    if(cfg.usuario[0]) {
        struct passwd *pw=getpwnam(cfg.usuario);
        if(!pw || geteuid()!=0 || fchown(fd,pw->pw_uid,pw->pw_gid)!=0) {close(fd);close(out);unlink(script);return NULL;}
    }
    close(fd);
    pid_t pid = fork();
    if (pid < 0) { close(out); unlink(script); return NULL; }
    if (pid == 0) {
        struct rlimit cpu = {(rlim_t)cfg.cpu_segundos, (rlim_t)cfg.cpu_segundos}, memoria = {(rlim_t)cfg.memoria_mb*1024*1024,(rlim_t)cfg.memoria_mb*1024*1024};
        struct rlimit archivos={(rlim_t)cfg.max_archivos,(rlim_t)cfg.max_archivos};
        struct rlimit procesos={(rlim_t)cfg.max_procesos,(rlim_t)cfg.max_procesos};
        setrlimit(RLIMIT_NOFILE,&archivos);
        setrlimit(RLIMIT_NPROC,&procesos);
        struct rlimit core={0,0};setrlimit(RLIMIT_CORE,&core);
        setrlimit(RLIMIT_CPU, &cpu);
        setrlimit(RLIMIT_AS, &memoria);
        if(cfg.usuario[0]) {
            struct passwd *pw=getpwnam(cfg.usuario);
            if(!pw || geteuid()!=0 || setgroups(0,NULL)!=0 || setgid(pw->pw_gid)!=0 || setuid(pw->pw_uid)!=0) _exit(126);
        }
        dup2(out, STDOUT_FILENO);
        close(out);
        int devnull = open("/dev/null", O_RDONLY);
        if (devnull >= 0) { dup2(devnull, STDIN_FILENO); close(devnull); }
        setenv("JOCARSA_HTTP_METHOD",request_method,1);
        setenv("JOCARSA_HTTP_QUERY",request_query,1);
        setenv("JOCARSA_HTTP_BODY",request_body,1);
        execl(INTERPRETE, "jocarsa-lenguaje", script, (char*)NULL);
        _exit(127);
    }
    int estado = 0, terminado = 0;
    for (int i=0; i<cfg.timeout_ms/10; i++) {
        pid_t w = waitpid(pid, &estado, WNOHANG);
        if (w == pid) { terminado=1; break; }
        if (w < 0) break;
        usleep(10000);
    }
    if (!terminado) { kill(pid,SIGKILL); waitpid(pid,&estado,0); }
    unlink(script);
    if (!terminado || !WIFEXITED(estado) || WEXITSTATUS(estado)!=0) { close(out); return NULL; }
    off_t n = lseek(out,0,SEEK_END);
    if (n < 0 || n > (off_t)cfg.max_salida_kb*1024 || lseek(out,0,SEEK_SET)<0) { close(out); return NULL; }
    char *resultado = malloc((size_t)n+1);
    if (!resultado) { close(out); return NULL; }
    size_t leido=0;
    while (leido<(size_t)n) {
        ssize_t r=read(out,resultado+leido,(size_t)n-leido);
        if (r<=0) {free(resultado);close(out);return NULL;}
        leido+=(size_t)r;
    }
    close(out);
    resultado[leido]='\0';
    *correcto=1;
    return resultado;
}

static char *procesar_bloques_jocarsa(const char *html) {
    const char *cursor=html;
    char *resultado=malloc(1);
    if (!resultado) return NULL;
    resultado[0]='\0';
    size_t longitud=0;
    while (*cursor) {
        const char *ini=strstr(cursor,"<#jocarsa");
        if (!ini) {
            if (!agregar(&resultado,&longitud,cursor,strlen(cursor))) goto error;
            break;
        }
        if (!agregar(&resultado,&longitud,cursor,(size_t)(ini-cursor))) goto error;
        const char *codigo=ini+strlen("<#jocarsa");
        const char *fin=strstr(codigo,"#>");
        if (!fin) goto error;
        if (*codigo=='\r') codigo++;
        if (*codigo=='\n') codigo++;
        int ok=0;
        char *salida=ejecutar_codigo(codigo,(size_t)(fin-codigo),&ok);
        if (!ok || !salida) {free(salida);goto error;}
        int agregado=agregar(&resultado,&longitud,salida,strlen(salida));
        free(salida);
        if (!agregado) goto error;
        cursor=fin+2;
    }
    return resultado;
error:
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

        ssize_t n = tls_conn && cliente==tls_fd ? SSL_write(tls_conn,buffer+enviados,(int)((longitud-enviados)>16384?16384:(longitud-enviados))) : send(cliente,buffer+enviados,longitud-enviados,MSG_NOSIGNAL);

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

    char *dinamico = procesar_bloques_jocarsa(contenido);
    free(contenido);
    if (!dinamico) {
        responder(cliente, 500, "Internal Server Error", "<h1>500 - Error ejecutando jocarsa</h1>");
        return;
    }
    contenido = dinamico;
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

    ssize_t recibidos = tls_conn && cliente==tls_fd ? SSL_read(tls_conn,peticion,sizeof(peticion)-1) : recv(cliente,peticion,sizeof(peticion)-1,0);

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

    /* Seleccionar VirtualHost por Host, sin permitir rutas de otros sitios. */
    char host_name[256]="";char *header_end_for_host=strstr(peticion,"\r\n\r\n");
    char *hp=strcasestr(peticion,"\r\nHost:");
    if(hp && (!header_end_for_host || hp<header_end_for_host)){hp+=7;while(*hp==' ')hp++;size_t i=0;while(hp[i]&&hp[i]!='\r'&&hp[i]!=':'&&i<sizeof(host_name)-1){host_name[i]=hp[i];i++;}host_name[i]=0;}
    current_host=NULL;for(int i=0;i<host_count;i++)if(!strcasecmp(host_name,hosts[i].domain)|| (hosts[i].alias[0]&&!strcasecmp(host_name,hosts[i].alias))){current_host=&hosts[i];break;}
    if(!current_host){responder(cliente,421,"Misdirected Request","<h1>421 - Host desconocido</h1>");return;}
    if(https_request && tls_conn){const char *sni=SSL_get_servername(tls_conn,TLSEXT_NAMETYPE_host_name);if(sni && strcasecmp(sni,host_name)){responder(cliente,421,"Misdirected Request","<h1>421 - SNI/Host no coinciden</h1>");return;}}
    snprintf(document_root,sizeof document_root,"%s",current_host->root);
    if(!https_request && (current_host->redirect||redirect_https) && tls_enabled){char location[2048];snprintf(location,sizeof location,"HTTP/1.1 301 Moved Permanently\r\nLocation: https://%s%s%s\r\nContent-Length: 0\r\nConnection: close\r\n\r\n",host_name,https_port==443?"":":8443",ruta);enviar_todo(cliente,location,strlen(location));return;}
    printf("%s %s\n", metodo, ruta);
    fflush(stdout);

    if (strcmp(metodo, "GET") != 0 && strcmp(metodo,"POST") != 0) {

        responder(
            cliente,
            405,
            "Method Not Allowed",
            "<h1>405 - Metodo no permitido</h1>"
        );

        return;
    }

    snprintf(request_method,sizeof(request_method),"%s",metodo);
    request_query[0]=0; request_body[0]=0;
    char *qmark=strchr(ruta,'?');
    if(qmark) snprintf(request_query,sizeof(request_query),"%s",qmark+1);
    if(!strcmp(metodo,"POST")) {
        char *header_end=strstr(peticion,"\r\n\r\n");
        char *cl=strcasestr(peticion,"Content-Length:");
        long length=0;
        if(cl && (!header_end || cl<header_end)) length=strtol(cl+15,NULL,10);
        if(length<0 || length>8191) {responder(cliente,413,"Payload Too Large","<h1>413</h1>");return;}
        size_t have=0;
        if(header_end) {char *body=header_end+4;have=(size_t)(recibidos-(body-peticion));if(have>(size_t)length)have=(size_t)length;memcpy(request_body,body,have);}
        while(have<(size_t)length) {ssize_t n=tls_conn && cliente==tls_fd ? SSL_read(tls_conn,request_body+have,(int)((size_t)length-have)) : recv(cliente,request_body+have,(size_t)length-have,0);if(n<=0)return;have+=(size_t)n;}
        request_body[have]=0;
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

    if(strstr(ruta,"/.")){responder(cliente,403,"Forbidden","<h1>403</h1>");return;}
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

    // Endpoints ejecutables, nunca devolver el código fuente.
    const char *endpoint_ext=strrchr(archivo,'.');
    if(endpoint_ext && !strcmp(endpoint_ext,".jocarsa")) {
        char *codigo=leer_archivo(archivo);
        if(!codigo){responder(cliente,500,"Internal Server Error","<h1>500</h1>");return;}
        int ok=0; char *salida=ejecutar_codigo(codigo,strlen(codigo),&ok);free(codigo);
        if(!ok || !salida){free(salida);responder(cliente,500,"Internal Server Error","<h1>Error de script</h1>");return;}
        size_t n=strlen(salida);
        if(enviar_cabecera(cliente,200,"OK","text/html; charset=utf-8",n))enviar_todo(cliente,salida,n);
        free(salida);return;
    }
    // Nunca entregar código fuente del intérprete al navegador.
    const char *ext = strrchr(archivo, '.');
    if (ext && strcmp(ext, ".jocarsa") == 0) {
        responder(cliente, 403, "Forbidden", "<h1>403 - Código fuente protegido</h1>");
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


static void clear_hosts(void){for(int i=0;i<host_count;i++){if(hosts[i].ctx)SSL_CTX_free(hosts[i].ctx);}memset(hosts,0,sizeof hosts);host_count=0;}
static int load_hosts(void){
    clear_hosts();DIR *d=opendir(vhosts_dir);if(!d){perror(vhosts_dir);return 0;}
    struct dirent *ent;while((ent=readdir(d))){size_t n=strlen(ent->d_name);if(n<6||strcmp(ent->d_name+n-5,".conf"))continue;
        if(host_count==MAX_VHOSTS){closedir(d);return 0;}
        char path[PATH_MAX];snprintf(path,sizeof path,"%s/%s",vhosts_dir,ent->d_name);FILE *f=fopen(path,"r");if(!f)continue;
        VHost *h=&hosts[host_count];h->redirect=0;char line[PATH_MAX+128];
        while(fgets(line,sizeof line,f)){char *p=line;while(isspace((unsigned char)*p))p++;if(!*p||*p=='#'||*p==';'||*p=='[')continue;char *eq=strchr(p,'=');if(!eq)continue;*eq++=0;char *end=p+strlen(p);while(end>p&&isspace((unsigned char)end[-1]))*--end=0;while(isspace((unsigned char)*eq))eq++;end=eq+strlen(eq);while(end>eq&&isspace((unsigned char)end[-1]))*--end=0;
#define FIELD(k,field) if(!strcmp(p,k)){if(strlen(eq)>=sizeof h->field){fclose(f);closedir(d);return 0;}strcpy(h->field,eq);}
            FIELD("dominio",domain)else FIELD("alias",alias)else FIELD("document_root",root)else FIELD("certificado",cert)else FIELD("clave",key)else if(!strcmp(p,"redirigir_https"))h->redirect=atoi(eq);
#undef FIELD
        }fclose(f);
        if(!h->domain[0]||!h->root[0]||!realpath(h->root,path)){fprintf(stderr,"VirtualHost invalido: %s\n",ent->d_name);closedir(d);return 0;}
        if(tls_enabled){if(!h->cert[0]||!h->key[0]){fprintf(stderr,"Faltan certificado/clave: %s\n",h->domain);closedir(d);return 0;}
            h->ctx=SSL_CTX_new(TLS_server_method());if(!h->ctx){closedir(d);return 0;}SSL_CTX_set_min_proto_version(h->ctx,TLS1_2_VERSION);SSL_CTX_set_options(h->ctx,SSL_OP_NO_COMPRESSION);
            if(SSL_CTX_use_certificate_chain_file(h->ctx,h->cert)!=1||SSL_CTX_use_PrivateKey_file(h->ctx,h->key,SSL_FILETYPE_PEM)!=1||SSL_CTX_check_private_key(h->ctx)!=1){ERR_print_errors_fp(stderr);closedir(d);return 0;}
        }host_count++;
    }closedir(d);if(!host_count){fprintf(stderr,"No hay VirtualHosts\n");return 0;}return 1;
}
static int sni_callback(SSL *ssl,int *ad,void *arg){(void)ad;(void)arg;const char *name=SSL_get_servername(ssl,TLSEXT_NAMETYPE_host_name);if(!name)return SSL_TLSEXT_ERR_OK;for(int i=0;i<host_count;i++)if(!strcasecmp(name,hosts[i].domain)|| (hosts[i].alias[0]&&!strcasecmp(name,hosts[i].alias))){SSL_set_SSL_CTX(ssl,hosts[i].ctx);return SSL_TLSEXT_ERR_OK;}return SSL_TLSEXT_ERR_ALERT_FATAL;}
static int listen_port(int port){int fd=socket(AF_INET,SOCK_STREAM,0);if(fd<0)return -1;int yes=1;setsockopt(fd,SOL_SOCKET,SO_REUSEADDR,&yes,sizeof yes);struct sockaddr_in addr={0};addr.sin_family=AF_INET;addr.sin_port=htons((uint16_t)port);addr.sin_addr.s_addr=htonl(cfg.solo_local?INADDR_LOOPBACK:INADDR_ANY);if(bind(fd,(void*)&addr,sizeof addr)<0||listen(fd,cfg.max_conexiones)<0){perror("listen/bind");close(fd);return -1;}return fd;}
int main(int argc,char **argv){
    const char *config="config/servidor.conf";int test=0,list=0;
    for(int i=1;i<argc;i++){if(!strcmp(argv[i],"--test-config"))test=1;else if(!strcmp(argv[i],"--list-vhosts"))list=1;else if(!strcmp(argv[i],"--version")){puts("jocarsa-web 0.4 OpenSSL");return 0;}else config=argv[i];}
    if(!leer_config(config))return 1;signal(SIGPIPE,SIG_IGN);signal(SIGHUP,reload_signal);
    SSL_library_init();SSL_load_error_strings();
    if(!load_hosts())return 1;
    if(tls_enabled)for(int i=0;i<host_count;i++)SSL_CTX_set_tlsext_servername_callback(hosts[i].ctx,sni_callback);
    if(test){puts("Configuracion y certificados OK");clear_hosts();return 0;}
    if(list){for(int i=0;i<host_count;i++)printf("%s (%s) -> %s\n",hosts[i].domain,hosts[i].alias,hosts[i].root);clear_hosts();return 0;}
    int httpfd=http_port?listen_port(http_port):-1;
    int httpsfd=tls_enabled?listen_port(https_port):-1;
    if((http_port&&httpfd<0)||(tls_enabled&&httpsfd<0)){clear_hosts();return 1;}
    fprintf(stderr,"jocarsa web: HTTP=%d HTTPS=%s%d vhosts=%d\n",http_port,tls_enabled?"":"desactivado ",tls_enabled?https_port:0,host_count);
    for(;;){
        if(reload_requested){reload_requested=0;if(!load_hosts()){fprintf(stderr,"Error recargando configuracion: detener servicio para corregir\n");break;}if(tls_enabled)for(int i=0;i<host_count;i++)SSL_CTX_set_tlsext_servername_callback(hosts[i].ctx,sni_callback);}
        fd_set readfds;FD_ZERO(&readfds);int maxfd=-1;if(httpfd>=0){FD_SET(httpfd,&readfds);maxfd=httpfd;}if(httpsfd>=0){FD_SET(httpsfd,&readfds);if(httpsfd>maxfd)maxfd=httpsfd;}
        if(select(maxfd+1,&readfds,NULL,NULL,NULL)<0){if(errno==EINTR)continue;perror("select");break;}
        int secure=httpsfd>=0&&FD_ISSET(httpsfd,&readfds);int fd=accept(secure?httpsfd:httpfd,NULL,NULL);if(fd<0)continue;
        struct timeval tv={.tv_sec=5};setsockopt(fd,SOL_SOCKET,SO_RCVTIMEO,&tv,sizeof tv);setsockopt(fd,SOL_SOCKET,SO_SNDTIMEO,&tv,sizeof tv);
        tls_conn=NULL;tls_fd=-1;https_request=secure;
        if(secure){tls_conn=SSL_new(hosts[0].ctx);if(tls_conn){SSL_set_fd(tls_conn,fd);tls_fd=fd;if(SSL_accept(tls_conn)!=1){SSL_free(tls_conn);tls_conn=NULL;close(fd);continue;}}else{close(fd);continue;}}
        procesar_peticion(fd);
        if(tls_conn){SSL_shutdown(tls_conn);SSL_free(tls_conn);tls_conn=NULL;tls_fd=-1;}
        close(fd);
    }
    if(httpfd>=0)close(httpfd);if(httpsfd>=0)close(httpsfd);clear_hosts();return 0;
}
