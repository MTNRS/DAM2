#include "JocarsaORM.hpp"
#include <cstring>
#include <cstdlib>
#include <memory>
#include <string>
struct Handle {JocarsaBBDD db; JocarsaORM orm; Handle(const char* config,const char* base):db(config),orm(db){if(!db.usaBaseDatos(base)){db.creaBaseDatos(base);if(!db.usaBaseDatos(base))throw std::runtime_error("No se pudo abrir la base");}}};
static thread_local std::string error;
extern "C" {
void* jocarsa_orm_abrir(const char* config,const char* base){try{error.clear();return new Handle(config,base);}catch(const std::exception& e){error=e.what();return nullptr;}}
void jocarsa_orm_cerrar(void* h){delete static_cast<Handle*>(h);}
const char* jocarsa_orm_error(){return error.c_str();}
void jocarsa_orm_liberar(char* s){std::free(s);}
static char* duplicar(const std::string& s){auto* p=(char*)std::malloc(s.size()+1);if(!p)throw std::bad_alloc();std::memcpy(p,s.c_str(),s.size()+1);return p;}
long long jocarsa_orm_guardar(void* h,const char* c,const char* json){try{error.clear();return static_cast<Handle*>(h)->orm.guardar(c,std::string(json));}catch(const std::exception&e){error=e.what();return -1;}}
char* jocarsa_orm_obtener(void* h,const char* c,long long id){try{error.clear();return duplicar(static_cast<Handle*>(h)->orm.obtener(c,id));}catch(const std::exception&e){error=e.what();return nullptr;}}
char* jocarsa_orm_todos(void* h,const char* c){try{error.clear();return duplicar(static_cast<Handle*>(h)->orm.todos(c));}catch(const std::exception&e){error=e.what();return nullptr;}}
char* jocarsa_orm_buscar(void* h,const char* c,const char* campo,const char* v){try{error.clear();return duplicar(static_cast<Handle*>(h)->orm.buscar(c,campo,std::string(v)));}catch(const std::exception&e){error=e.what();return nullptr;}}
int jocarsa_orm_actualizar(void* h,const char* c,long long id,const char* cambios){try{error.clear();return static_cast<Handle*>(h)->orm.actualizar(c,id,std::string(cambios));}catch(const std::exception&e){error=e.what();return -1;}}
int jocarsa_orm_eliminar(void* h,const char* c,long long id){try{error.clear();return static_cast<Handle*>(h)->orm.eliminar(c,id);}catch(const std::exception&e){error=e.what();return -1;}}
long long jocarsa_orm_contar(void* h,const char* c){try{error.clear();return static_cast<Handle*>(h)->orm.contar(c);}catch(const std::exception&e){error=e.what();return -1;}}
}
