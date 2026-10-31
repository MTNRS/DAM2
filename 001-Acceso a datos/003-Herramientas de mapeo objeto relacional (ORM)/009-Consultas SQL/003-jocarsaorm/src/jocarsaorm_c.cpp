#include "jocarsaorm_c.h"
#include "JocarsaORM.h"
#include <cstdlib>
#include <cstring>
#include <exception>
#include <string>
static thread_local std::string ultimo_error;
static char* copiar(const std::string& texto){char* p=(char*)std::malloc(texto.size()+1);if(!p)throw std::bad_alloc();std::memcpy(p,texto.c_str(),texto.size()+1);return p;}
template<typename F,typename T>static T seguro(F f,T fallo){try{ultimo_error.clear();return f();}catch(const std::exception& e){ultimo_error=e.what();return fallo;}catch(...){ultimo_error="Error desconocido";return fallo;}}
extern "C" {
void* jocarsa_open(const char* ruta){return seguro([&]()->void*{if(!ruta)throw std::runtime_error("Ruta nula");return new JocarsaORM(ruta);},(void*)nullptr);}
void jocarsa_close(void* orm){delete static_cast<JocarsaORM*>(orm);}
int64_t jocarsa_guardar(void* p,const char* c,const char* j){return seguro([&]()->int64_t{return static_cast<JocarsaORM*>(p)->guardar(c,nlohmann::json::parse(j));},(int64_t)-1);}
char* jocarsa_obtener(void* p,const char* c,int64_t id){return seguro([&]()->char*{return copiar(static_cast<JocarsaORM*>(p)->obtener(c,id).dump());},(char*)nullptr);}
char* jocarsa_todos(void* p,const char* c){return seguro([&]()->char*{return copiar(static_cast<JocarsaORM*>(p)->todos(c).dump());},(char*)nullptr);}
char* jocarsa_buscar(void* p,const char* c,const char* campo,const char* v){return seguro([&]()->char*{return copiar(static_cast<JocarsaORM*>(p)->buscar(c,campo,nlohmann::json::parse(v)).dump());},(char*)nullptr);}
int jocarsa_actualizar(void* p,const char* c,int64_t id,const char* v){return seguro([&]()->int{return static_cast<JocarsaORM*>(p)->actualizar(c,id,nlohmann::json::parse(v))?1:0;},-1);}
int jocarsa_eliminar(void* p,const char* c,int64_t id){return seguro([&]()->int{return static_cast<JocarsaORM*>(p)->eliminar(c,id)?1:0;},-1);}
int64_t jocarsa_contar(void* p,const char* c){return seguro([&]()->int64_t{return static_cast<JocarsaORM*>(p)->contar(c);},(int64_t)-1);}
int64_t jocarsa_importar(void* p,const char* c,const char* ruta){return seguro([&]()->int64_t{return static_cast<JocarsaORM*>(p)->importar(c,ruta);},(int64_t)-1);}
int64_t jocarsa_exportar(void* p,const char* c,const char* ruta){return seguro([&]()->int64_t{return static_cast<JocarsaORM*>(p)->exportar(c,ruta);},(int64_t)-1);}
const char* jocarsa_error(void){return ultimo_error.c_str();}
void jocarsa_free(char* p){std::free(p);}
}
