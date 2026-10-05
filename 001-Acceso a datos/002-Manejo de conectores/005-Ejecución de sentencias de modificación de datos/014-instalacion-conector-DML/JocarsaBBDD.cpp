#include "JocarsaBBDD.hpp"
#include <algorithm>
#include <cstdlib>
#include <filesystem>
#include <fstream>
#include <iostream>
#include <regex>
#include <sstream>
#include <stdexcept>

namespace fs = std::filesystem;

std::string JocarsaSerializador::serializar(const std::vector<std::string>& lista, char delimitador) const {
    std::ostringstream out;
    for (std::size_t i=0; i<lista.size(); ++i) { if (i) out << delimitador; out << lista[i]; }
    return out.str();
}
std::vector<std::string> JocarsaSerializador::desserializar(const std::string& cadena, char delimitador) const {
    std::vector<std::string> r; std::stringstream ss(cadena); std::string x;
    while (std::getline(ss,x,delimitador)) r.push_back(x);
    if (!cadena.empty() && cadena.back()==delimitador) r.push_back("");
    return r;
}

JocarsaBBDD::JocarsaBBDD(const std::string& config) {
    // Prioridad: ruta explicita/local -> variable de entorno -> configuracion global.
    std::string rutaConfig = config;
    if (!fs::exists(rutaConfig)) {
        if (const char* env = std::getenv("JOCARSABBDD_CONFIG"); env && fs::exists(env)) rutaConfig = env;
        else if (fs::exists("/etc/jocarsabbdd/config.json")) rutaConfig = "/etc/jocarsabbdd/config.json";
        else throw std::runtime_error("No se ha encontrado config.json ni /etc/jocarsabbdd/config.json");
    }
    std::ifstream f(rutaConfig); if (!f) throw std::runtime_error("No se puede abrir la configuracion: " + rutaConfig);
    std::string s((std::istreambuf_iterator<char>(f)), {});
    std::smatch m;
    if (!std::regex_search(s,m,std::regex("\\\"instalacion\\\"\\s*:\\s*\\\"([^\\\"]+)\\\""))) throw std::runtime_error("Falta 'instalacion' en config.json");
    instalacion=m[1].str();
    if (!std::regex_search(s,m,std::regex("\\\"tamanoRegistro\\\"\\s*:\\s*([0-9]+)"))) throw std::runtime_error("Falta 'tamanoRegistro' en config.json");
    tamanoRegistro=std::stoull(m[1].str());
    fs::create_directories(instalacion);
}
std::string JocarsaBBDD::rutaTabla(const std::string& t,const std::string& e) const { return (fs::path(instalacion)/basededatos/(t+e)).string(); }
std::string JocarsaBBDD::trimDerecha(std::string s){ while(!s.empty() && (s.back()==' '||s.back()=='\n'||s.back()=='\r'||s.back()=='\t')) s.pop_back(); return s; }
bool JocarsaBBDD::creaBaseDatos(const std::string& n){ fs::path p=fs::path(instalacion)/n; if(fs::exists(p)){std::cerr<<"La base de datos ya existe\n";return false;} return fs::create_directory(p); }
bool JocarsaBBDD::usaBaseDatos(const std::string& n){ fs::path p=fs::path(instalacion)/n; if(!fs::exists(p)){std::cerr<<"La base de datos no existe\n";return false;} basededatos=n; return true; }
bool JocarsaBBDD::creaTabla(const std::string& n,const std::string& e){ if(basededatos.empty()){std::cerr<<"No hay base seleccionada\n";return false;} auto csv=rutaTabla(n,".csv"); if(fs::exists(csv)){std::cerr<<"La tabla ya existe\n";return false;} std::ofstream(csv,std::ios::binary); std::ofstream(rutaTabla(n,".esquema"))<<"id,activo,"<<e; std::ofstream(rutaTabla(n,".idx")); return true; }

bool JocarsaBBDD::alteraTablaAnadir(const std::string& t,const std::string& col){
    auto es=obtenerEsquema(t); if(es.empty()||col.empty()) return false;
    if(std::find(es.begin(),es.end(),col)!=es.end()){std::cerr<<"La columna ya existe\n";return false;}
    auto filas=listarTodo(t); es.push_back(col);
    std::ofstream(rutaTabla(t,".esquema"),std::ios::trunc)<<JocarsaSerializador().serializar(es);
    std::ofstream csv(rutaTabla(t,".csv"),std::ios::binary|std::ios::trunc); std::ofstream idx(rutaTabla(t,".idx"),std::ios::trunc);
    for(const auto&r:filas){ std::vector<std::string> e; for(size_t i=0;i<es.size()-1;++i)e.push_back(r.at(es[i])); e.push_back(""); auto pos=csv.tellp(); auto reg=prepararRegistro(e); csv.write(reg.data(),reg.size()); idx<<e[0]<<","<<pos<<"\n"; }
    return bool(csv)&&bool(idx);
}

bool JocarsaBBDD::alteraTablaEliminarColumna(const std::string& t,const std::string& col){
    if(col=="id"||col=="activo"){std::cerr<<"No se puede eliminar una columna interna\n";return false;}
    auto es=obtenerEsquema(t); auto it=std::find(es.begin(),es.end(),col); if(it==es.end()) return false;
    auto filas=listarTodo(t); es.erase(it);
    std::ofstream(rutaTabla(t,".esquema"),std::ios::trunc)<<JocarsaSerializador().serializar(es);
    std::ofstream csv(rutaTabla(t,".csv"),std::ios::binary|std::ios::trunc); std::ofstream idx(rutaTabla(t,".idx"),std::ios::trunc);
    for(const auto&r:filas){ std::vector<std::string> e; for(const auto&c:es)e.push_back(r.at(c)); auto pos=csv.tellp(); auto reg=prepararRegistro(e); csv.write(reg.data(),reg.size()); idx<<e[0]<<","<<pos<<"\n"; }
    return bool(csv)&&bool(idx);
}

bool JocarsaBBDD::alteraTablaRenombrarColumna(const std::string& t,const std::string& antigua,const std::string& nueva){
    if(antigua=="id"||antigua=="activo"||nueva.empty()) return false;
    auto es=obtenerEsquema(t); auto it=std::find(es.begin(),es.end(),antigua); if(it==es.end()) return false;
    if(std::find(es.begin(),es.end(),nueva)!=es.end()){std::cerr<<"La columna destino ya existe\n";return false;}
    *it=nueva; std::ofstream f(rutaTabla(t,".esquema"),std::ios::trunc); f<<JocarsaSerializador().serializar(es); return bool(f);
}

bool JocarsaBBDD::alteraTablaModificarColumna(const std::string& t,const std::string& antigua,const std::string& nueva){
    return alteraTablaRenombrarColumna(t,antigua,nueva);
}

bool JocarsaBBDD::truncarTabla(const std::string& t){
    if(basededatos.empty()||!fs::exists(rutaTabla(t,".csv"))) return false;
    std::ofstream csv(rutaTabla(t,".csv"),std::ios::binary|std::ios::trunc); std::ofstream idx(rutaTabla(t,".idx"),std::ios::trunc); return bool(csv)&&bool(idx);
}

bool JocarsaBBDD::eliminarTabla(const std::string& t){
    if(basededatos.empty()) return false; bool habia=false;
    for(const auto&e:{".csv",".esquema",".idx"}){auto p=rutaTabla(t,e);if(fs::exists(p)){fs::remove(p);habia=true;}}
    return habia;
}

bool JocarsaBBDD::eliminarBaseDatos(const std::string& n){
    if(n.empty()) return false; fs::path p=fs::path(instalacion)/n; if(!fs::exists(p)||!fs::is_directory(p)) return false;
    fs::remove_all(p); if(basededatos==n) basededatos.clear(); return true;
}
std::vector<std::string> JocarsaBBDD::obtenerEsquema(const std::string& t) const { std::ifstream f(rutaTabla(t,".esquema")); if(!f) return {}; std::string s((std::istreambuf_iterator<char>(f)),{}); return JocarsaSerializador().desserializar(s); }
long long JocarsaBBDD::siguienteId(const std::string& t) const { std::ifstream f(rutaTabla(t,".idx")); long long ultimo=0,id,pos; char c; while(f>>id>>c>>pos) ultimo=id; return ultimo+1; }
std::string JocarsaBBDD::prepararRegistro(const std::vector<std::string>& e) const { auto s=JocarsaSerializador().serializar(e); if(s.size()>tamanoRegistro-1) throw std::runtime_error("Registro demasiado grande"); s.append(tamanoRegistro-1-s.size(),' '); s.push_back('\n'); return s; }
long long JocarsaBBDD::insertarDatos(const std::string& t,const std::vector<std::string>& datos){ if(basededatos.empty()||!fs::exists(rutaTabla(t,".csv"))) return -1; auto id=siguienteId(t); std::vector<std::string> e={std::to_string(id),"1"}; e.insert(e.end(),datos.begin(),datos.end()); auto reg=prepararRegistro(e); std::ofstream f(rutaTabla(t,".csv"),std::ios::binary|std::ios::app); auto pos=fs::file_size(rutaTabla(t,".csv")); f.write(reg.data(),reg.size()); std::ofstream(rutaTabla(t,".idx"),std::ios::app)<<id<<","<<pos<<"\n"; return id; }
std::int64_t JocarsaBBDD::buscarPosicion(const std::string& t,long long buscado) const { std::ifstream f(rutaTabla(t,".idx")); long long id,pos; char c; while(f>>id>>c>>pos) if(id==buscado) return pos; return -1; }
std::vector<std::string> JocarsaBBDD::leerRegistro(const std::string& t,long long id) const { auto pos=buscarPosicion(t,id); if(pos<0)return{}; std::ifstream f(rutaTabla(t,".csv"),std::ios::binary); f.seekg(pos); std::string b(tamanoRegistro,'\0'); f.read(b.data(),tamanoRegistro); b.resize(f.gcount()); auto e=JocarsaSerializador().desserializar(trimDerecha(b)); if(e.size()<2||e[1]=="0")return{}; return e; }
std::map<std::string,std::string> JocarsaBBDD::seleccionar(const std::string& t,long long id) const { auto e=leerRegistro(t,id), es=obtenerEsquema(t); std::map<std::string,std::string> r; if(e.size()!=es.size())return r; for(size_t i=0;i<es.size();++i)r[es[i]]=e[i]; return r; }
std::vector<std::map<std::string,std::string>> JocarsaBBDD::listarTodo(const std::string& t) const { std::vector<std::map<std::string,std::string>> out; auto es=obtenerEsquema(t); std::ifstream f(rutaTabla(t,".csv"),std::ios::binary); while(f){std::string b(tamanoRegistro,'\0');f.read(b.data(),tamanoRegistro);if(f.gcount()==0)break;b.resize(f.gcount());auto e=JocarsaSerializador().desserializar(trimDerecha(b));if(e.size()==es.size()&&e.size()>1&&e[1]=="1"){std::map<std::string,std::string> r;for(size_t i=0;i<es.size();++i)r[es[i]]=e[i];out.push_back(r);}}return out; }
std::vector<std::map<std::string,std::string>> JocarsaBBDD::buscarColumna(const std::string& t,const std::string& col,const std::string& val) const { auto es=obtenerEsquema(t); auto it=std::find(es.begin(),es.end(),col); if(it==es.end())return{}; size_t p=it-es.begin(); std::vector<std::map<std::string,std::string>> out; for(auto&r:listarTodo(t)) if(r.at(es[p])==val) out.push_back(r); return out; }
bool JocarsaBBDD::actualizar(const std::string& t,long long id,const std::vector<std::string>& datos){auto pos=buscarPosicion(t,id);auto es=obtenerEsquema(t);if(pos<0||leerRegistro(t,id).empty()||datos.size()!=es.size()-2)return false;std::vector<std::string>e={std::to_string(id),"1"};e.insert(e.end(),datos.begin(),datos.end());auto reg=prepararRegistro(e);std::fstream f(rutaTabla(t,".csv"),std::ios::binary|std::ios::in|std::ios::out);f.seekp(pos);f.write(reg.data(),reg.size());return bool(f);}
bool JocarsaBBDD::eliminar(const std::string& t,long long id){auto pos=buscarPosicion(t,id);auto e=leerRegistro(t,id);if(pos<0||e.empty())return false;e[1]="0";auto reg=prepararRegistro(e);std::fstream f(rutaTabla(t,".csv"),std::ios::binary|std::ios::in|std::ios::out);f.seekp(pos);f.write(reg.data(),reg.size());return bool(f);}

long long JocarsaBBDD::insertarConColumnas(const std::string& t,const std::vector<std::string>& columnas,const std::vector<std::string>& valores){
    auto es=obtenerEsquema(t); if(es.size()<2||columnas.size()!=valores.size()) return -1;
    std::vector<std::string> datos(es.size()-2,"");
    for(size_t i=0;i<columnas.size();++i){
        auto it=std::find(es.begin()+2,es.end(),columnas[i]);
        if(it==es.end()){std::cerr<<"Columna no encontrada: "<<columnas[i]<<"\n";return -1;}
        datos[static_cast<size_t>(it-(es.begin()+2))]=valores[i];
    }
    return insertarDatos(t,datos);
}

long long JocarsaBBDD::insertarDesdeSeleccion(const std::string& destino,const std::string& origen){
    auto ed=obtenerEsquema(destino), eo=obtenerEsquema(origen);
    if(ed.size()<2||eo.size()<2||ed.size()!=eo.size()){std::cerr<<"Los esquemas no son compatibles\n";return -1;}
    long long n=0;
    for(const auto&r:listarTodo(origen)){
        std::vector<std::string> datos;
        for(size_t i=2;i<eo.size();++i) datos.push_back(r.at(eo[i]));
        if(insertarDatos(destino,datos)<0) return -1;
        ++n;
    }
    return n;
}

long long JocarsaBBDD::actualizarDonde(const std::string& t,const std::map<std::string,std::string>& cambios,const std::string& colDonde,const std::string& valDonde){
    auto es=obtenerEsquema(t); if(es.size()<2||cambios.empty()) return -1;
    for(const auto&[c,v]:cambios){(void)v;if(c=="id"||c=="activo"||std::find(es.begin(),es.end(),c)==es.end()){std::cerr<<"Columna no modificable: "<<c<<"\n";return -1;}}
    if(!colDonde.empty()&&std::find(es.begin(),es.end(),colDonde)==es.end()){std::cerr<<"Columna WHERE/DONDE no encontrada\n";return -1;}
    long long n=0;
    auto filas=listarTodo(t);
    for(auto&r:filas){
        if(!colDonde.empty() && r.at(colDonde)!=valDonde) continue;
        std::vector<std::string> datos;
        for(size_t i=2;i<es.size();++i){auto it=cambios.find(es[i]);datos.push_back(it==cambios.end()?r.at(es[i]):it->second);}
        if(actualizar(t,std::stoll(r.at("id")),datos)) ++n;
    }
    return n;
}

long long JocarsaBBDD::eliminarDonde(const std::string& t,const std::string& colDonde,const std::string& valDonde){
    auto es=obtenerEsquema(t); if(es.size()<2) return -1;
    if(!colDonde.empty()&&std::find(es.begin(),es.end(),colDonde)==es.end()){std::cerr<<"Columna WHERE/DONDE no encontrada\n";return -1;}
    long long n=0;
    auto filas=listarTodo(t);
    for(const auto&r:filas){
        if(!colDonde.empty() && r.at(colDonde)!=valDonde) continue;
        if(eliminar(t,std::stoll(r.at("id")))) ++n;
    }
    return n;
}
