#include <json-c/json.h>
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
int jocarsa_coleccion_crear(void* h,const char* n){try{error.clear();return static_cast<Handle*>(h)->db.crearColeccion(n)?1:0;}catch(const std::exception&e){error=e.what();return -1;}}
int jocarsa_coleccion_eliminar(void* h,const char* n){try{error.clear();return static_cast<Handle*>(h)->db.eliminarColeccion(n)?1:0;}catch(const std::exception&e){error=e.what();return -1;}}
int jocarsa_coleccion_agregar(void* h,const char* n,const char* t){try{error.clear();return static_cast<Handle*>(h)->db.agregarTablaColeccion(t,n)?1:0;}catch(const std::exception&e){error=e.what();return -1;}}
int jocarsa_coleccion_quitar(void* h,const char* n,const char* t){try{error.clear();return static_cast<Handle*>(h)->db.quitarTablaColeccion(t,n)?1:0;}catch(const std::exception&e){error=e.what();return -1;}}
static char* listaJson(const std::vector<std::string>& v){json_object* a=json_object_new_array();for(auto& s:v)json_object_array_add(a,json_object_new_string(s.c_str()));char* p=duplicar(json_object_to_json_string(a));json_object_put(a);return p;}
char* jocarsa_coleccion_listar(void* h){try{error.clear();return listaJson(static_cast<Handle*>(h)->db.listarColecciones());}catch(const std::exception&e){error=e.what();return nullptr;}}
char* jocarsa_coleccion_tablas(void* h,const char* n){try{error.clear();return listaJson(static_cast<Handle*>(h)->db.tablasDeColeccion(n));}catch(const std::exception&e){error=e.what();return nullptr;}}
char* jocarsa_tabla_colecciones(void* h,const char* t){try{error.clear();return listaJson(static_cast<Handle*>(h)->db.coleccionesDeTabla(t));}catch(const std::exception&e){error=e.what();return nullptr;}}

int jocarsa_coleccion_subcrear(void* h,const char* parent,const char* name){try{error.clear();return static_cast<Handle*>(h)->db.crearSubcoleccion(parent,name)?1:0;}catch(const std::exception&e){error=e.what();return -1;}}
int jocarsa_coleccion_mover(void* h,const char* name,const char* parent){try{error.clear();return static_cast<Handle*>(h)->db.moverColeccion(name,parent)?1:0;}catch(const std::exception&e){error=e.what();return -1;}}
char* jocarsa_coleccion_hijas(void* h,const char* name){try{error.clear();return listaJson(static_cast<Handle*>(h)->db.subcoleccionesDe(name));}catch(const std::exception&e){error=e.what();return nullptr;}}
char* jocarsa_coleccion_recursivo(void* h,const char* name){try{error.clear();return listaJson(static_cast<Handle*>(h)->db.tablasDeColeccionRecursiva(name));}catch(const std::exception&e){error=e.what();return nullptr;}}
char* jocarsa_coleccion_arbol(void* h){try{error.clear();return duplicar(static_cast<Handle*>(h)->db.arbolColecciones());}catch(const std::exception&e){error=e.what();return nullptr;}}

}
