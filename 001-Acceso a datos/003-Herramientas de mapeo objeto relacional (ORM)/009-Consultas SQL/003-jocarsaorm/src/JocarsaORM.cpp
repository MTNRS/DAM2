#include "JocarsaORM.h"
#include <filesystem>
#include <fstream>
#include <algorithm>
#include <stdexcept>
#include <memory>
namespace fs = std::filesystem;
namespace {
struct Stmt {
    sqlite3_stmt* p = nullptr;
    sqlite3* db;
    Stmt(sqlite3* d, const char* sql): db(d) {
        if (sqlite3_prepare_v2(db, sql, -1, &p, nullptr) != SQLITE_OK)
            throw std::runtime_error(sqlite3_errmsg(db));
    }
    ~Stmt(){ sqlite3_finalize(p); }
    void txt(int n, const std::string& v) {
        if(sqlite3_bind_text(p,n,v.c_str(),(int)v.size(),SQLITE_TRANSIENT)!=SQLITE_OK) throw std::runtime_error(sqlite3_errmsg(db));
    }
    void num(int n,int64_t v){if(sqlite3_bind_int64(p,n,v)!=SQLITE_OK) throw std::runtime_error(sqlite3_errmsg(db));}
    void nul(int n){if(sqlite3_bind_null(p,n)!=SQLITE_OK) throw std::runtime_error(sqlite3_errmsg(db));}
    int step(){int rc=sqlite3_step(p); if(rc!=SQLITE_ROW && rc!=SQLITE_DONE) throw std::runtime_error(sqlite3_errmsg(db));return rc;}
};
std::string col(sqlite3_stmt* p,int i){auto t=sqlite3_column_text(p,i);return t?(const char*)t:"";}
}
void JocarsaORM::ejecutar(const std::string& sql){char* err=nullptr;if(sqlite3_exec(db_,sql.c_str(),nullptr,nullptr,&err)!=SQLITE_OK){std::string msg=err?err:"Error SQLite";sqlite3_free(err);throw std::runtime_error(msg);}}
JocarsaORM::JocarsaORM(const std::string& ruta){
    if(sqlite3_open(ruta.c_str(),&db_)!=SQLITE_OK){std::string e=db_?sqlite3_errmsg(db_):"SQLite no disponible";if(db_)sqlite3_close(db_);db_=nullptr;throw std::runtime_error(e);}
    try {
        sqlite3_busy_timeout(db_,5000);
        ejecutar("PRAGMA foreign_keys=ON");
        ejecutar("CREATE TABLE IF NOT EXISTS jocarsa_registros(id INTEGER PRIMARY KEY AUTOINCREMENT,coleccion TEXT NOT NULL)");
        ejecutar("CREATE INDEX IF NOT EXISTS idx_reg_coleccion ON jocarsa_registros(coleccion)");
        ejecutar("CREATE TABLE IF NOT EXISTS jocarsa_nodos(id INTEGER PRIMARY KEY AUTOINCREMENT,registro_id INTEGER NOT NULL REFERENCES jocarsa_registros(id) ON DELETE CASCADE,padre_id INTEGER REFERENCES jocarsa_nodos(id) ON DELETE CASCADE,clave TEXT,posicion INTEGER,tipo TEXT NOT NULL,valor TEXT)");
        ejecutar("CREATE INDEX IF NOT EXISTS idx_nodo_registro ON jocarsa_nodos(registro_id)");
        ejecutar("CREATE INDEX IF NOT EXISTS idx_nodo_padre ON jocarsa_nodos(padre_id)");
    } catch(...) {sqlite3_close(db_);db_=nullptr;throw;}
}
JocarsaORM::~JocarsaORM(){if(db_)sqlite3_close(db_);}
void JocarsaORM::transaccion(const std::function<void()>& fn){ejecutar("BEGIN IMMEDIATE");try{fn();ejecutar("COMMIT");}catch(...){try{ejecutar("ROLLBACK");}catch(...){}throw;}}
void JocarsaORM::insertarNodo(int64_t registro,const json& dato,int64_t padre,const std::string& clave,int64_t posicion){
    std::string tipo=dato.is_object()?"objeto":dato.is_array()?"array":"valor";
    Stmt s(db_,"INSERT INTO jocarsa_nodos(registro_id,padre_id,clave,posicion,tipo,valor) VALUES(?,?,?,?,?,?)");
    s.num(1,registro);if(padre)s.num(2,padre);else s.nul(2);
    if(padre && posicion<0)s.txt(3,clave);else s.nul(3);
    if(posicion>=0)s.num(4,posicion);else s.nul(4);
    s.txt(5,tipo);if(tipo=="valor")s.txt(6,dato.dump());else s.nul(6);
    s.step();int64_t nodo=sqlite3_last_insert_rowid(db_);
    if(dato.is_object())for(auto it=dato.begin();it!=dato.end();++it)insertarNodo(registro,it.value(),nodo,it.key());
    if(dato.is_array())for(size_t i=0;i<dato.size();++i)insertarNodo(registro,dato[i],nodo,"",(int64_t)i);
}
JocarsaORM::json JocarsaORM::reconstruirNodo(int64_t nodo){
    std::string tipo,valor;
    {Stmt s(db_,"SELECT tipo,valor FROM jocarsa_nodos WHERE id=?");s.num(1,nodo);if(s.step()!=SQLITE_ROW)throw std::runtime_error("Nodo inexistente");tipo=col(s.p,0);valor=col(s.p,1);}
    if(tipo=="valor")return json::parse(valor);
    if(tipo!="objeto" && tipo!="array")throw std::runtime_error("Tipo desconocido");
    json out=tipo=="objeto"?json::object():json::array();
    std::vector<std::pair<int64_t,std::string>> hijos;
    {Stmt s(db_,"SELECT id,clave FROM jocarsa_nodos WHERE padre_id=? ORDER BY posicion,id");s.num(1,nodo);while(s.step()==SQLITE_ROW)hijos.emplace_back(sqlite3_column_int64(s.p,0),col(s.p,1));}
    for(auto& h:hijos){json v=reconstruirNodo(h.first);if(tipo=="array")out.push_back(v);else out[h.second]=v;}
    return out;
}
bool JocarsaORM::existe(const std::string& c,int64_t id){Stmt s(db_,"SELECT 1 FROM jocarsa_registros WHERE coleccion=? AND id=?");s.txt(1,c);s.num(2,id);return s.step()==SQLITE_ROW;}
std::vector<int64_t> JocarsaORM::ids(const std::string& c){Stmt s(db_,"SELECT id FROM jocarsa_registros WHERE coleccion=? ORDER BY id");s.txt(1,c);std::vector<int64_t> out;while(s.step()==SQLITE_ROW)out.push_back(sqlite3_column_int64(s.p,0));return out;}
int64_t JocarsaORM::guardarInterno(const std::string& c,const json& dato){Stmt s(db_,"INSERT INTO jocarsa_registros(coleccion) VALUES(?)");s.txt(1,c);s.step();int64_t id=sqlite3_last_insert_rowid(db_);insertarNodo(id,dato);return id;}
int64_t JocarsaORM::guardar(const std::string& c,const json& dato){int64_t id=0;transaccion([&]{id=guardarInterno(c,dato);});return id;}
JocarsaORM::json JocarsaORM::obtener(const std::string& c,int64_t id){if(!existe(c,id))return nullptr;Stmt s(db_,"SELECT id FROM jocarsa_nodos WHERE registro_id=? AND padre_id IS NULL");s.num(1,id);if(s.step()!=SQLITE_ROW)throw std::runtime_error("Registro sin nodo raíz");return reconstruirNodo(sqlite3_column_int64(s.p,0));}
JocarsaORM::json JocarsaORM::todos(const std::string& c){json out=json::array();for(auto id:ids(c))out.push_back(obtener(c,id));return out;}
JocarsaORM::json JocarsaORM::buscar(const std::string& c,const std::string& campo,const json& valor){json out=json::array();for(auto id:ids(c)){json v=obtener(c,id);if(v.is_object()&&v.contains(campo)&&v[campo]==valor)out.push_back(v);}return out;}
bool JocarsaORM::actualizar(const std::string& c,int64_t id,const json& cambios){if(!existe(c,id))return false;json dato=obtener(c,id);if(dato.is_object()&&cambios.is_object())for(auto it=cambios.begin();it!=cambios.end();++it)dato[it.key()]=it.value();else dato=cambios;
    transaccion([&]{Stmt s(db_,"DELETE FROM jocarsa_nodos WHERE registro_id=?");s.num(1,id);s.step();insertarNodo(id,dato);});return true;
}
bool JocarsaORM::eliminar(const std::string& c,int64_t id){if(!existe(c,id))return false;transaccion([&]{Stmt s(db_,"DELETE FROM jocarsa_registros WHERE coleccion=? AND id=?");s.txt(1,c);s.num(2,id);s.step();});return true;}
int64_t JocarsaORM::contar(const std::string& c){Stmt s(db_,"SELECT COUNT(*) FROM jocarsa_registros WHERE coleccion=?");s.txt(1,c);s.step();return sqlite3_column_int64(s.p,0);}
size_t JocarsaORM::importar(const std::string& c,const std::string& carpeta){if(!fs::is_directory(carpeta))throw std::runtime_error("Carpeta inexistente: "+carpeta);std::vector<fs::path> archivos;for(auto& e:fs::directory_iterator(carpeta))if(e.is_regular_file()&&e.path().extension()==".json")archivos.push_back(e.path());std::sort(archivos.begin(),archivos.end());size_t n=0;
    for(auto& p:archivos){std::ifstream f(p);if(!f)throw std::runtime_error("No se puede abrir: "+p.string());json dato;f>>dato;size_t local=0;transaccion([&]{if(dato.is_array()){for(auto& x:dato){guardarInterno(c,x);++local;}}else{guardarInterno(c,dato);++local;}});n+=local;}return n;
}
size_t JocarsaORM::exportar(const std::string& c,const std::string& carpeta){fs::create_directories(carpeta);auto registros=ids(c);for(auto id:registros){auto ruta=fs::path(carpeta)/(c+"_"+std::to_string(id)+".json");std::ofstream f(ruta);if(!f)throw std::runtime_error("No se puede escribir: "+ruta.string());f<<obtener(c,id).dump(4)<<'\n';if(!f)throw std::runtime_error("Error al escribir: "+ruta.string());}return registros.size();}
