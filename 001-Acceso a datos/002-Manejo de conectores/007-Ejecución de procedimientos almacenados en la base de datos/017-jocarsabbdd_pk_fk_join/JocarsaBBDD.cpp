#include "JocarsaBBDD.hpp"
#include <algorithm>
#include <cstdlib>
#include <filesystem>
#include <fstream>
#include <iostream>
#include <iomanip>
#include <regex>
#include <sstream>
#include <set>
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
bool JocarsaBBDD::creaTabla(const std::string& n,const std::string& e){ if(basededatos.empty()){std::cerr<<"No hay base seleccionada\n";return false;} auto csv=rutaTabla(n,".csv"); if(fs::exists(csv)){std::cerr<<"La tabla ya existe\n";return false;} std::ofstream(csv,std::ios::binary); std::ofstream(rutaTabla(n,".esquema"))<<"id,activo,"<<e; std::ofstream(rutaTabla(n,".idx")); std::ofstream(rutaTabla(n,".rel"))<<"PK,id\n"; return true; }

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
    for(const auto&e:{".csv",".esquema",".idx",".rel"}){auto p=rutaTabla(t,e);if(fs::exists(p)){fs::remove(p);habia=true;}}
    return habia;
}

bool JocarsaBBDD::eliminarBaseDatos(const std::string& n){
    if(n.empty()) return false; fs::path p=fs::path(instalacion)/n; if(!fs::exists(p)||!fs::is_directory(p)) return false;
    fs::remove_all(p); if(basededatos==n) basededatos.clear(); return true;
}
std::string JocarsaBBDD::obtenerClavePrimaria(const std::string& t) const {
    // Compatibilidad: toda tabla JocarsaBBDD tiene id como PK implicita.
    auto es=obtenerEsquema(t);
    return es.empty() ? "" : "id";
}

std::vector<ClaveForanea> JocarsaBBDD::obtenerClavesForaneas(const std::string& t) const {
    std::vector<ClaveForanea> out;
    std::ifstream f(rutaTabla(t,".rel"));
    std::string linea;
    while(std::getline(f,linea)){
        auto v=JocarsaSerializador().desserializar(linea);
        if(v.size()==4 && v[0]=="FK") out.push_back({v[1],v[2],v[3]});
    }
    return out;
}

bool JocarsaBBDD::anadirClaveForanea(const std::string& t,const std::string& col,const std::string& tr,const std::string& cr){
    if(basededatos.empty()) return false;
    auto es=obtenerEsquema(t), er=obtenerEsquema(tr);
    if(es.empty()||er.empty()){std::cerr<<"Tabla local o referenciada no encontrada\n";return false;}
    if(std::find(es.begin(),es.end(),col)==es.end()){std::cerr<<"Columna local no encontrada: "<<col<<"\n";return false;}
    if(std::find(er.begin(),er.end(),cr)==er.end()){std::cerr<<"Columna referenciada no encontrada: "<<cr<<"\n";return false;}
    if(cr!="id"){std::cerr<<"En esta version la FK debe referenciar la PK id\n";return false;}
    for(const auto& fk:obtenerClavesForaneas(t)) if(fk.columna==col){std::cerr<<"La columna ya tiene clave foranea\n";return false;}
    // Los datos existentes tambien deben cumplir la nueva restriccion.
    for(const auto& r:listarTodo(t)){
        auto it=r.find(col); if(it!=r.end()&&!it->second.empty()&&!registroReferenciado(tr,cr,it->second)){
            std::cerr<<"No se puede crear FK: valor existente sin referencia: "<<it->second<<"\n";return false;
        }
    }
    std::ofstream f(rutaTabla(t,".rel"),std::ios::app);
    f<<"FK,"<<col<<","<<tr<<","<<cr<<"\n";
    return bool(f);
}

bool JocarsaBBDD::registroReferenciado(const std::string& t,const std::string& col,const std::string& valor) const {
    if(valor.empty()) return true; // cadena vacia equivale a FK no informada en esta version
    if(col=="id"){
        try { return !seleccionar(t,std::stoll(valor)).empty(); } catch(...) { return false; }
    }
    return !buscarColumna(t,col,valor).empty();
}

bool JocarsaBBDD::validarClavesForaneas(const std::string& t,const std::vector<std::string>& datos) const {
    auto es=obtenerEsquema(t);
    if(es.size()<2||datos.size()!=es.size()-2) return false;
    for(const auto& fk:obtenerClavesForaneas(t)){
        auto it=std::find(es.begin()+2,es.end(),fk.columna);
        if(it==es.end()) return false;
        size_t pos=static_cast<size_t>(it-(es.begin()+2));
        const auto& valor=datos[pos];
        if(!valor.empty()&&!registroReferenciado(fk.tablaReferenciada,fk.columnaReferenciada,valor)){
            std::cerr<<"Violacion de clave foranea: "<<t<<"."<<fk.columna<<"="<<valor
                     <<" no existe en "<<fk.tablaReferenciada<<"."<<fk.columnaReferenciada<<"\n";
            return false;
        }
    }
    return true;
}

bool JocarsaBBDD::puedeEliminarRegistro(const std::string& t,const std::map<std::string,std::string>& registro) const {
    if(basededatos.empty()) return false;
    fs::path dir=fs::path(instalacion)/basededatos;
    if(!fs::exists(dir)) return false;
    for(const auto& entry:fs::directory_iterator(dir)){
        if(entry.path().extension()!=".rel") continue;
        std::string hija=entry.path().stem().string();
        for(const auto& fk:obtenerClavesForaneas(hija)){
            if(fk.tablaReferenciada!=t) continue;
            auto it=registro.find(fk.columnaReferenciada);
            if(it==registro.end()) continue;
            if(!buscarColumna(hija,fk.columna,it->second).empty()){
                std::cerr<<"No se puede eliminar: registro referenciado desde "<<hija<<"."<<fk.columna<<"\n";
                return false;
            }
        }
    }
    return true;
}

std::vector<std::string> JocarsaBBDD::obtenerEsquema(const std::string& t) const { std::ifstream f(rutaTabla(t,".esquema")); if(!f) return {}; std::string s((std::istreambuf_iterator<char>(f)),{}); return JocarsaSerializador().desserializar(s); }
long long JocarsaBBDD::siguienteId(const std::string& t) const { std::ifstream f(rutaTabla(t,".idx")); long long ultimo=0,id,pos; char c; while(f>>id>>c>>pos) ultimo=id; return ultimo+1; }
std::string JocarsaBBDD::prepararRegistro(const std::vector<std::string>& e) const { auto s=JocarsaSerializador().serializar(e); if(s.size()>tamanoRegistro-1) throw std::runtime_error("Registro demasiado grande"); s.append(tamanoRegistro-1-s.size(),' '); s.push_back('\n'); return s; }
long long JocarsaBBDD::insertarDatos(const std::string& t,const std::vector<std::string>& datos){
    if(basededatos.empty()||!fs::exists(rutaTabla(t,".csv"))) return -1;
    auto es=obtenerEsquema(t);
    if(es.size()<2||datos.size()!=es.size()-2){std::cerr<<"Numero de valores incorrecto\n";return -1;}
    if(!validarClavesForaneas(t,datos)) return -1;
    auto id=siguienteId(t); std::vector<std::string> e={std::to_string(id),"1"}; e.insert(e.end(),datos.begin(),datos.end());
    auto reg=prepararRegistro(e); std::ofstream f(rutaTabla(t,".csv"),std::ios::binary|std::ios::app); auto pos=fs::file_size(rutaTabla(t,".csv"));
    f.write(reg.data(),reg.size()); if(!f) return -1; std::ofstream(rutaTabla(t,".idx"),std::ios::app)<<id<<","<<pos<<"\n"; return id;
}
std::int64_t JocarsaBBDD::buscarPosicion(const std::string& t,long long buscado) const { std::ifstream f(rutaTabla(t,".idx")); long long id,pos; char c; while(f>>id>>c>>pos) if(id==buscado) return pos; return -1; }
std::vector<std::string> JocarsaBBDD::leerRegistro(const std::string& t,long long id) const { auto pos=buscarPosicion(t,id); if(pos<0)return{}; std::ifstream f(rutaTabla(t,".csv"),std::ios::binary); f.seekg(pos); std::string b(tamanoRegistro,'\0'); f.read(b.data(),tamanoRegistro); b.resize(f.gcount()); auto e=JocarsaSerializador().desserializar(trimDerecha(b)); if(e.size()<2||e[1]=="0")return{}; return e; }
std::map<std::string,std::string> JocarsaBBDD::seleccionar(const std::string& t,long long id) const { auto e=leerRegistro(t,id), es=obtenerEsquema(t); std::map<std::string,std::string> r; if(e.size()!=es.size())return r; for(size_t i=0;i<es.size();++i)r[es[i]]=e[i]; return r; }
std::vector<std::map<std::string,std::string>> JocarsaBBDD::listarTodo(const std::string& t) const { std::vector<std::map<std::string,std::string>> out; auto es=obtenerEsquema(t); std::ifstream f(rutaTabla(t,".csv"),std::ios::binary); while(f){std::string b(tamanoRegistro,'\0');f.read(b.data(),tamanoRegistro);if(f.gcount()==0)break;b.resize(f.gcount());auto e=JocarsaSerializador().desserializar(trimDerecha(b));if(e.size()==es.size()&&e.size()>1&&e[1]=="1"){std::map<std::string,std::string> r;for(size_t i=0;i<es.size();++i)r[es[i]]=e[i];out.push_back(r);}}return out; }
std::vector<std::map<std::string,std::string>> JocarsaBBDD::buscarColumna(const std::string& t,const std::string& col,const std::string& val) const { auto es=obtenerEsquema(t); auto it=std::find(es.begin(),es.end(),col); if(it==es.end())return{}; size_t p=it-es.begin(); std::vector<std::map<std::string,std::string>> out; for(auto&r:listarTodo(t)) if(r.at(es[p])==val) out.push_back(r); return out; }
bool JocarsaBBDD::actualizar(const std::string& t,long long id,const std::vector<std::string>& datos){
    auto pos=buscarPosicion(t,id); auto es=obtenerEsquema(t);
    if(pos<0||leerRegistro(t,id).empty()||datos.size()!=es.size()-2) return false;
    if(!validarClavesForaneas(t,datos)) return false;
    std::vector<std::string> e={std::to_string(id),"1"}; e.insert(e.end(),datos.begin(),datos.end()); auto reg=prepararRegistro(e);
    std::fstream f(rutaTabla(t,".csv"),std::ios::binary|std::ios::in|std::ios::out); f.seekp(pos); f.write(reg.data(),reg.size()); return bool(f);
}
bool JocarsaBBDD::eliminar(const std::string& t,long long id){
    auto pos=buscarPosicion(t,id); auto e=leerRegistro(t,id); if(pos<0||e.empty()) return false;
    auto registro=seleccionar(t,id); if(!puedeEliminarRegistro(t,registro)) return false;
    e[1]="0"; auto reg=prepararRegistro(e); std::fstream f(rutaTabla(t,".csv"),std::ios::binary|std::ios::in|std::ios::out); f.seekp(pos); f.write(reg.data(),reg.size()); return bool(f);
}

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

// -----------------------------------------------------------------------------
// DQL: SELECCIONAR ... DE ...
// Subconjunto implementado: DISTINTO, COMO (alias y LIKE), DONDE, = <> != > <
// >= <=, Y/O/NO, ENTRE, EN, ORDENAR POR, ASCENDENTE/DESCENDENTE y LIMITE.
// -----------------------------------------------------------------------------
namespace {
struct TokenDQL { std::string texto; bool literal=false; };
static std::string dqlMayus(std::string s){std::transform(s.begin(),s.end(),s.begin(),[](unsigned char c){return std::toupper(c);});return s;}
static bool dqlNumero(const std::string&s,double&v){char*e=nullptr;v=std::strtod(s.c_str(),&e);return e&&*e=='\0'&&e!=s.c_str();}
static int dqlComparar(const std::string&a,const std::string&b){double x,y;if(dqlNumero(a,x)&&dqlNumero(b,y))return x<y?-1:(x>y?1:0);return a<b?-1:(a>b?1:0);}
static std::vector<TokenDQL> dqlTokens(const std::string&s){
 std::vector<TokenDQL> r; size_t i=0;
 while(i<s.size()){
  if(std::isspace((unsigned char)s[i])||s[i]==';'){++i;continue;}
  if(s[i]=='\''||s[i]=='"'){char q=s[i++];std::string v;while(i<s.size()){if(s[i]==q){if(i+1<s.size()&&s[i+1]==q){v+=q;i+=2;continue;}++i;break;}v+=s[i++];}r.push_back({v,true});continue;}
  if(std::string("(),*").find(s[i])!=std::string::npos){r.push_back({std::string(1,s[i++]),false});continue;}
  if(std::string("=<>!").find(s[i])!=std::string::npos){std::string v(1,s[i++]);if(i<s.size()&&(s[i]=='='||(v=="<"&&s[i]=='>')))v+=s[i++];r.push_back({v,false});continue;}
  std::string v;while(i<s.size()&&!std::isspace((unsigned char)s[i])&&std::string("(),;*=<>!\"'").find(s[i])==std::string::npos)v+=s[i++];
  if(!v.empty())r.push_back({v,false});
 }
 return r;
}
class ExprDQL {
 const std::vector<TokenDQL>&t; size_t p=0; const std::map<std::string,std::string>&f;
 bool kw(const std::string&x)const{return p<t.size()&&!t[p].literal&&dqlMayus(t[p].texto)==x;}
 bool tomar(const std::string&x){if(kw(x)){++p;return true;}return false;}
 std::string valor(){if(p>=t.size())throw std::runtime_error("Falta un valor en DONDE");auto x=t[p++];if(!x.literal){auto it=f.find(x.texto);if(it!=f.end())return it->second;}return x.texto;}
 bool prim(){
  if(p<t.size()&&t[p].texto=="("){++p;bool v=o();if(p>=t.size()||t[p].texto!=")")throw std::runtime_error("Falta ')' en DONDE");++p;return v;}
  std::string izq=valor(); bool neg=tomar("NO");
  if(tomar("ENTRE")){std::string a=valor();if(!tomar("Y"))throw std::runtime_error("ENTRE necesita Y");std::string b=valor();bool v=dqlComparar(izq,a)>=0&&dqlComparar(izq,b)<=0;return neg?!v:v;}
  if(tomar("EN")){if(p>=t.size()||t[p++].texto!="(")throw std::runtime_error("EN necesita '('");bool v=false;while(p<t.size()&&t[p].texto!=")"){if(izq==valor())v=true;if(p<t.size()&&t[p].texto==",")++p;else break;}if(p>=t.size()||t[p++].texto!=")")throw std::runtime_error("EN necesita ')'");return neg?!v:v;}
  if(tomar("COMO")||tomar("LIKE")){std::string patron=valor(), rx="^";for(char c:patron){if(c=='%')rx+=".*";else if(c=='_')rx+='.';else{if(std::string(".^$|()[]{}*+?\\").find(c)!=std::string::npos)rx+='\\';rx+=c;}}rx+="$";bool v=std::regex_match(izq,std::regex(rx));return neg?!v:v;}
  if(neg) throw std::runtime_error("NO debe preceder una condicion, ENTRE, EN o COMO");
  if(p>=t.size())throw std::runtime_error("Falta operador de comparacion");std::string op=t[p++].texto;std::string der=valor();int c=dqlComparar(izq,der);
  if(op=="=")return c==0;if(op=="<>"||op=="!=")return c!=0;if(op==">")return c>0;if(op=="<")return c<0;if(op==">=")return c>=0;if(op=="<=")return c<=0;throw std::runtime_error("Operador no soportado: "+op);
 }
 bool no(){if(tomar("NO"))return !no();return prim();}
 bool y(){bool v=no();while(tomar("Y")){bool d=no();v=v&&d;}return v;}
 bool o(){bool v=y();while(tomar("O")){bool d=y();v=v||d;}return v;}
public: ExprDQL(const std::vector<TokenDQL>&tt,const std::map<std::string,std::string>&ff):t(tt),f(ff){} bool evaluar(){if(t.empty())return true;bool v=o();if(p!=t.size())throw std::runtime_error("Expresion DONDE no reconocida cerca de: "+t[p].texto);return v;}
};
struct CampoDQL{
 std::string nombre,alias,funcion;
 bool agregado() const { return !funcion.empty(); }
};
static std::string dqlNumeroTexto(double v){
 std::ostringstream o; o<<std::setprecision(15)<<v; std::string s=o.str();
 if(s.find('.')!=std::string::npos){while(!s.empty()&&s.back()=='0')s.pop_back();if(!s.empty()&&s.back()=='.')s.pop_back();}
 return s;
}
}

ResultadoConsulta JocarsaBBDD::consultar(const std::string& consulta) const {
    auto t=dqlTokens(consulta); size_t p=0;
    auto kw=[&](const std::string&x){return p<t.size()&&!t[p].literal&&dqlMayus(t[p].texto)==x;};
    auto toma=[&](const std::string&x){if(kw(x)){++p;return true;}return false;};
    if(!toma("SELECCIONAR")) throw std::runtime_error("La consulta debe comenzar por SELECCIONAR");
    bool distinto=toma("DISTINTO");
    std::vector<CampoDQL> campos; bool estrella=false;
    if(p<t.size()&&t[p].texto=="*"){estrella=true;++p;}
    else while(p<t.size()&&!kw("DE")){
        CampoDQL c;
        std::string posible=dqlMayus(t[p].texto);
        if((posible=="CONTAR"||posible=="SUMA"||posible=="MEDIA"||posible=="MINIMO"||posible=="MAXIMO") && p+1<t.size() && t[p+1].texto=="("){
            c.funcion=posible; p+=2;
            if(p>=t.size()) throw std::runtime_error("Falta argumento de funcion agregada");
            c.nombre=t[p++].texto;
            if(p>=t.size()||t[p++].texto!=")") throw std::runtime_error("Falta ')' en funcion agregada");
            c.alias=c.funcion+"("+c.nombre+")";
        } else { c.nombre=t[p++].texto; c.alias=c.nombre; }
        if(toma("COMO")){if(p>=t.size())throw std::runtime_error("Falta alias tras COMO");c.alias=t[p++].texto;}
        campos.push_back(c);
        if(p<t.size()&&t[p].texto==","){++p;continue;}
        if(!kw("DE")) throw std::runtime_error("Se esperaba ',' o DE");
    }
    if(!toma("DE")||p>=t.size()) throw std::runtime_error("Falta DE tabla");
    std::string tablaBase=t[p++].texto; auto esquemaBase=obtenerEsquema(tablaBase);
    if(esquemaBase.empty()) throw std::runtime_error("Tabla no encontrada: "+tablaBase);

    struct JoinInfo{std::string tabla,izq,der;}; std::vector<JoinInfo> joins;
    while(kw("JOIN")||kw("UNIR")){
        ++p; if(p>=t.size())throw std::runtime_error("Falta tabla tras JOIN"); std::string tj=t[p++].texto;
        if(obtenerEsquema(tj).empty())throw std::runtime_error("Tabla JOIN no encontrada: "+tj);
        if(!(toma("EN")||toma("ON")))throw std::runtime_error("JOIN necesita EN");
        if(p>=t.size())throw std::runtime_error("Falta columna izquierda en JOIN");std::string izq=t[p++].texto;
        if(p>=t.size()||t[p++].texto!="=")throw std::runtime_error("JOIN solo admite igualdad en esta version");
        if(p>=t.size())throw std::runtime_error("Falta columna derecha en JOIN");std::string der=t[p++].texto;
        joins.push_back({tj,izq,der});
    }

    auto cualificar=[&](const std::string& tabla,const std::map<std::string,std::string>& fila){
        std::map<std::string,std::string> r; for(const auto&[k,v]:fila){r[tabla+"."+k]=v;if(!r.count(k))r[k]=v;} return r;
    };
    std::vector<std::map<std::string,std::string>> filas;
    for(const auto&f:listarTodo(tablaBase)) filas.push_back(cualificar(tablaBase,f));
    std::vector<std::string> esquemaSalida; for(const auto&c:esquemaBase) esquemaSalida.push_back(joins.empty()?c:tablaBase+"."+c);
    for(const auto&j:joins){
        auto ej=obtenerEsquema(j.tabla); for(const auto&c:ej) esquemaSalida.push_back(j.tabla+"."+c);
        std::vector<std::map<std::string,std::string>> nuevas;
        for(const auto&acum:filas) for(const auto&fr:listarTodo(j.tabla)){
            auto q=cualificar(j.tabla,fr); auto comb=acum; for(const auto&[k,v]:q) if(k.find('.')!=std::string::npos||!comb.count(k)) comb[k]=v;
            auto valor=[&](const std::string&n){auto it=comb.find(n);if(it==comb.end())throw std::runtime_error("Columna JOIN no encontrada: "+n);return it->second;};
            if(valor(j.izq)==valor(j.der)) nuevas.push_back(std::move(comb));
        } filas=std::move(nuevas);
    }
    if(estrella){for(const auto&c:esquemaSalida)campos.push_back({c,c,""});}
    auto existeCampo=[&](const std::string& c){
        if(c=="*")return true;
        if(!filas.empty())return filas.front().count(c)>0;
        if(c.find('.')!=std::string::npos){auto q=c.find('.');auto e=obtenerEsquema(c.substr(0,q));return std::find(e.begin(),e.end(),c.substr(q+1))!=e.end();}
        return std::find(esquemaBase.begin(),esquemaBase.end(),c)!=esquemaBase.end();
    };
    for(const auto&c:campos)if(!existeCampo(c.nombre))throw std::runtime_error("Columna no encontrada: "+c.nombre);

    std::vector<TokenDQL> donde,having; std::vector<std::string> grupos; std::string orden; bool desc=false; long long limite=-1;
    if(toma("DONDE")){int nivel=0;while(p<t.size()){if(t[p].texto=="(")++nivel;if(t[p].texto==")")--nivel;if(nivel==0&&(kw("AGRUPAR")||kw("TENIENDO")||kw("ORDENAR")||kw("LIMITE")))break;donde.push_back(t[p++]);}}
    if(toma("AGRUPAR")){if(!toma("POR"))throw std::runtime_error("AGRUPAR necesita POR");while(p<t.size()&&!kw("TENIENDO")&&!kw("ORDENAR")&&!kw("LIMITE")){grupos.push_back(t[p++].texto);if(p<t.size()&&t[p].texto==",")++p;else break;}}
    if(toma("TENIENDO")){int nivel=0;while(p<t.size()){if(t[p].texto=="(")++nivel;if(t[p].texto==")")--nivel;if(nivel==0&&(kw("ORDENAR")||kw("LIMITE")))break;having.push_back(t[p++]);}}
    if(toma("ORDENAR")){if(!toma("POR")||p>=t.size())throw std::runtime_error("ORDENAR necesita POR columna");orden=t[p++].texto;if(toma("DESCENDENTE"))desc=true;else toma("ASCENDENTE");}
    if(toma("LIMITE")){if(p>=t.size())throw std::runtime_error("Falta numero tras LIMITE");limite=std::stoll(t[p++].texto);if(limite<0)throw std::runtime_error("LIMITE no puede ser negativo");}
    if(p!=t.size())throw std::runtime_error("Clausula no reconocida: "+t[p].texto);
    for(const auto&g:grupos)if(!existeCampo(g))throw std::runtime_error("Columna AGRUPAR no encontrada: "+g);

    std::vector<std::map<std::string,std::string>> filtradas;
    for(const auto&f:filas)if(donde.empty()||ExprDQL(donde,f).evaluar())filtradas.push_back(f);
    bool hayAgregados=std::any_of(campos.begin(),campos.end(),[](const CampoDQL&c){return c.agregado();});
    if(!hayAgregados&&!grupos.empty()) throw std::runtime_error("AGRUPAR POR requiere al menos una funcion agregada");
    if(!hayAgregados&&!having.empty()) throw std::runtime_error("TENIENDO requiere una consulta agregada");
    if(hayAgregados){for(const auto&c:campos)if(!c.agregado()&&std::find(grupos.begin(),grupos.end(),c.nombre)==grupos.end())throw std::runtime_error("Columna no agregada debe aparecer en AGRUPAR POR: "+c.nombre);}

    ResultadoConsulta r; for(const auto&c:campos)r.columnas.push_back(c.alias);
    std::vector<std::map<std::string,std::string>> salidaMap;
    if(hayAgregados){
        std::map<std::string,std::vector<std::map<std::string,std::string>>> bolsas;
        if(grupos.empty()) bolsas[""]=filtradas;
        else for(const auto&f:filtradas){std::string k;for(const auto&g:grupos)k+=std::to_string(f.at(g).size())+":"+f.at(g)+"|";bolsas[k].push_back(f);}
        if(filtradas.empty()&&grupos.empty()) bolsas[""]={};
        for(const auto&[clave,bolsa]:bolsas){(void)clave;std::map<std::string,std::string> out;
            for(const auto&c:campos){std::string v;
                if(!c.agregado()) v=bolsa.empty()?"":bolsa.front().at(c.nombre);
                else if(c.funcion=="CONTAR"){
                    if(c.nombre=="*")v=std::to_string(bolsa.size());else{long long n=0;for(const auto&f:bolsa)if(f.count(c.nombre)&&!f.at(c.nombre).empty())++n;v=std::to_string(n);}
                } else if(c.funcion=="SUMA"||c.funcion=="MEDIA"){
                    double suma=0;long long n=0;for(const auto&f:bolsa){double x;if(!dqlNumero(f.at(c.nombre),x))throw std::runtime_error(c.funcion+" requiere valores numericos en "+c.nombre);suma+=x;++n;}v=dqlNumeroTexto(c.funcion=="MEDIA"?(n?suma/n:0):suma);
                } else if(c.funcion=="MINIMO"||c.funcion=="MAXIMO"){
                    if(bolsa.empty())v="";else{v=bolsa.front().at(c.nombre);for(const auto&f:bolsa){int cmp=dqlComparar(f.at(c.nombre),v);if((c.funcion=="MINIMO"&&cmp<0)||(c.funcion=="MAXIMO"&&cmp>0))v=f.at(c.nombre);}}
                }
                out[c.alias]=v;out[c.funcion+"("+c.nombre+")"]=v;if(!c.agregado())out[c.nombre]=v;
            }
            if(having.empty()||ExprDQL(having,out).evaluar())salidaMap.push_back(std::move(out));
        }
    } else {
        for(const auto&f:filtradas){std::map<std::string,std::string> out=f;for(const auto&c:campos)out[c.alias]=f.at(c.nombre);salidaMap.push_back(std::move(out));}
    }
    if(!orden.empty()){
        auto tieneOrden=[&](const auto&f){return f.count(orden)>0;}; if(!salidaMap.empty()&&!tieneOrden(salidaMap.front()))throw std::runtime_error("Columna de ordenacion no encontrada: "+orden);
        std::stable_sort(salidaMap.begin(),salidaMap.end(),[&](const auto&a,const auto&b){int c=dqlComparar(a.at(orden),b.at(orden));return desc?c>0:c<0;});
    }
    std::set<std::string> vistos;
    for(const auto&f:salidaMap){std::vector<std::string> fila;std::string clave;for(const auto&c:campos){auto v=f.at(c.alias);fila.push_back(v);clave+=std::to_string(v.size())+":"+v+"|";}if(distinto&&!vistos.insert(clave).second)continue;r.filas.push_back(std::move(fila));if(limite>=0&&(long long)r.filas.size()>=limite)break;}
    return r;
}

