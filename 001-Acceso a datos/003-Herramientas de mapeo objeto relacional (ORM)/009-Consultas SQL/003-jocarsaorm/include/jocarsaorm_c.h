#ifndef JOCARSAORM_C_H
#define JOCARSAORM_C_H
#include <stdint.h>
#ifdef __cplusplus
extern "C" {
#endif
void* jocarsa_open(const char* ruta);
void jocarsa_close(void* orm);
int64_t jocarsa_guardar(void* orm,const char* coleccion,const char* json_texto);
char* jocarsa_obtener(void* orm,const char* coleccion,int64_t id);
char* jocarsa_todos(void* orm,const char* coleccion);
char* jocarsa_buscar(void* orm,const char* coleccion,const char* campo,const char* json_valor);
int jocarsa_actualizar(void* orm,const char* coleccion,int64_t id,const char* json_cambios);
int jocarsa_eliminar(void* orm,const char* coleccion,int64_t id);
int64_t jocarsa_contar(void* orm,const char* coleccion);
int64_t jocarsa_importar(void* orm,const char* coleccion,const char* carpeta);
int64_t jocarsa_exportar(void* orm,const char* coleccion,const char* carpeta);
const char* jocarsa_error(void);
void jocarsa_free(char* texto);
#ifdef __cplusplus
}
#endif
#endif
