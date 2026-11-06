#include <sqlite3.h>
#include <json-c/json.h>
#include <filesystem>
#include <fstream>
#include <iostream>
#include <sstream>
#include <vector>
#include <map>
#include <algorithm>
#include <chrono>
#include <iomanip>
#include <stdexcept>
#include <cctype>
#include <optional>
#include <memory>
using namespace std;
namespace fs=std::filesystem;

string leer(const string& pregunta){ cout<<pregunta; string s; getline(cin,s); return s; }
string minusculas(string s){ transform(s.begin(),s.end(),s.begin(),[](unsigned char c){return (char)tolower(c);}); return s; }
string multilinea(){ cout<<"Escribe el contenido. Termina con una linea que contenga solo FIN:\n"; string s,l; bool primero=true; while(getline(cin,l) && l!="FIN"){if(!primero)s+='\n';s+=l;primero=false;}return s; }
string ahora(){ auto t=chrono::system_clock::to_time_t(chrono::system_clock::now()); tm utc{}; gmtime_r(&t,&utc); ostringstream o; o<<put_time(&utc,"%Y-%m-%dT%H:%M:%S+00:00");return o.str(); }
string texto_archivo(const fs::path& p){ifstream f(p,ios::binary);if(!f)throw runtime_error("No se puede leer: "+p.string());ostringstream s;s<<f.rdbuf();if(!f.good()&&!f.eof())throw runtime_error("Error leyendo archivo");return s.str();}
void escribir_archivo(const fs::path& p,const string& s){ofstream f(p,ios::binary|ios::trunc);if(!f)throw runtime_error("No se puede escribir: "+p.string());f.write(s.data(),s.size());if(!f)throw runtime_error("Error al escribir");}
string normalizar_json(const string& s){ json_tokener* t=json_tokener_new();json_object* obj=json_tokener_parse_ex(t,s.c_str(),(int)s.size());auto err=json_tokener_get_error(t);int fin=json_tokener_get_parse_end(t);bool resto=false;for(size_t i=fin;i<s.size();++i)if(!isspace((unsigned char)s[i]))resto=true;json_tokener_free(t);if(err!=json_tokener_success||resto||!obj){if(obj)json_object_put(obj);throw runtime_error("JSON no valido");}string out=json_object_to_json_string_ext(obj,JSON_C_TO_STRING_PRETTY);json_object_put(obj);return out+"\n"; }
struct Documento{int id;string ruta,tipo,descripcion,creado,actualizado;};
class MotorDocumental{
 fs::path raiz_;sqlite3* db_=nullptr;
 void sql(const string& s){char* err=nullptr;if(sqlite3_exec(db_,s.c_str(),nullptr,nullptr,&err)!=SQLITE_OK){string msg=err?err:"Error SQLite";sqlite3_free(err);throw runtime_error(msg);}}
 struct Sentencia{sqlite3_stmt* s=nullptr;Sentencia(sqlite3* db,const string& q){if(sqlite3_prepare_v2(db,q.c_str(),-1,&s,nullptr)!=SQLITE_OK)throw runtime_error(sqlite3_errmsg(db));}~Sentencia(){sqlite3_finalize(s);}void texto(int i,const string& v){sqlite3_bind_text(s,i,v.c_str(),-1,SQLITE_TRANSIENT);}void numero(int i,int v){sqlite3_bind_int(s,i,v);}int paso(){return sqlite3_step(s);} };
 void comprobar(int rc){if(rc!=SQLITE_DONE)throw runtime_error(sqlite3_errmsg(db_));}
 static bool dentro(const fs::path& p,const fs::path& raiz){auto a=p.begin(),b=raiz.begin();for(;b!=raiz.end();++b,++a)if(a==p.end()||*a!=*b)return false;return true;}
 bool interno(const fs::path& p)const{auto n=p.filename().string();return p.parent_path()==raiz_&&(n=="documental.db"||n=="documental.db-wal"||n=="documental.db-shm"||n=="documental.db-journal");}
 fs::path ruta_segura(const string& r)const{fs::path p=fs::weakly_canonical(raiz_/fs::path(r));if(!dentro(p,raiz_))throw runtime_error("Ruta fuera de la base documental");if(interno(p))throw runtime_error("Archivo interno protegido");return p;}
 string relativa(const fs::path& p)const{return p.lexically_relative(raiz_).generic_string();}
 static string tipo(const fs::path& p){string e=minusculas(p.extension().string());if(e==".json")return "json";if(e==".md")return "markdown";if(e==".html"||e==".htm")return "html";return "externo";}
 static string escapar_html(const string& s){string out;for(char c:s){switch(c){case '&':out+="&amp;";break;case '<':out+="&lt;";break;case '>':out+="&gt;";break;case '"':out+="&quot;";break;default:out+=c;}}return out;}
 void registrar(const fs::path& p,const string& descripcion="",bool actualizar=false){Sentencia st(db_,"INSERT INTO documentos(ruta,tipo,descripcion,creado,actualizado) VALUES(?,?,?,?,?) ON CONFLICT(ruta) DO UPDATE SET tipo=excluded.tipo, actualizado=CASE WHEN ? THEN excluded.actualizado ELSE documentos.actualizado END");auto t=ahora();st.texto(1,relativa(p));st.texto(2,tipo(p));st.texto(3,descripcion);st.texto(4,t);st.texto(5,t);st.numero(6,actualizar);comprobar(st.paso());}
 Documento fila(int id){Sentencia st(db_,"SELECT id,ruta,tipo,descripcion,creado,actualizado FROM documentos WHERE id=?");st.numero(1,id);if(st.paso()!=SQLITE_ROW)throw runtime_error("No existe ese identificador");auto val=[&](int i){auto x=sqlite3_column_text(st.s,i);return string(x?(const char*)x:"");};return {sqlite3_column_int(st.s,0),val(1),val(2),val(3),val(4),val(5)};}
 vector<Documento> consulta(const string& q,const vector<string>& args={}){Sentencia st(db_,q);for(size_t i=0;i<args.size();++i)st.texto((int)i+1,args[i]);vector<Documento> v;int rc;while((rc=st.paso())==SQLITE_ROW){auto val=[&](int i){auto x=sqlite3_column_text(st.s,i);return string(x?(const char*)x:"");};v.push_back({sqlite3_column_int(st.s,0),val(1),val(2),val(3),val(4),val(5)});}if(rc!=SQLITE_DONE)throw runtime_error(sqlite3_errmsg(db_));return v;}
 void carpeta_destino(const fs::path& p){if(!fs::is_directory(p.parent_path()))throw runtime_error("La carpeta de destino no existe");if(fs::exists(p))throw runtime_error("El destino ya existe");}
public:
 explicit MotorDocumental(const string& raiz="jocarsa-documental"){raiz_=fs::absolute(fs::path(raiz)).lexically_normal();fs::create_directories(raiz_);raiz_=fs::canonical(raiz_);if(sqlite3_open((raiz_/"documental.db").string().c_str(),&db_)!=SQLITE_OK){string e=sqlite3_errmsg(db_);sqlite3_close(db_);db_=nullptr;throw runtime_error(e);}sql("PRAGMA foreign_keys=ON;");sql("CREATE TABLE IF NOT EXISTS documentos(id INTEGER PRIMARY KEY AUTOINCREMENT,ruta TEXT NOT NULL UNIQUE,tipo TEXT NOT NULL,descripcion TEXT NOT NULL DEFAULT '',creado TEXT NOT NULL,actualizado TEXT NOT NULL);");sql("CREATE INDEX IF NOT EXISTS idx_documentos_tipo ON documentos(tipo);");sincronizar();}
 ~MotorDocumental(){if(db_)sqlite3_close(db_);}MotorDocumental(const MotorDocumental&)=delete;MotorDocumental& operator=(const MotorDocumental&)=delete;
 void sincronizar(){vector<fs::path> reales;for(fs::recursive_directory_iterator it(raiz_,fs::directory_options::skip_permission_denied),fin;it!=fin;++it){auto p=it->path();if(it->is_symlink()){if(it->is_directory())it.disable_recursion_pending();continue;}if(it->is_regular_file()&&!interno(p))reales.push_back(p);}sql("BEGIN IMMEDIATE;");try{for(auto& p:reales)registrar(p);auto filas=consulta("SELECT id,ruta,tipo,descripcion,creado,actualizado FROM documentos");for(auto& d:filas){auto p=ruta_segura(d.ruta);if(!fs::is_regular_file(p)){Sentencia st(db_,"DELETE FROM documentos WHERE id=?");st.numero(1,d.id);comprobar(st.paso());}}sql("COMMIT;");}catch(...){sql("ROLLBACK;");throw;}}
 string crear_carpeta(const string& r){auto p=ruta_segura(r);if(p==raiz_)throw runtime_error("No se puede crear la raiz");if(fs::exists(p))throw runtime_error("La carpeta ya existe");fs::create_directories(p);return relativa(p);}
 string crear(const string& r,const string& contenido,const string& descripcion=""){auto p=ruta_segura(r);auto t=tipo(p);if(t=="externo")throw runtime_error("Usa .json, .md o .html; para otros formatos utiliza importar");carpeta_destino(p);string c=t=="json"?normalizar_json(contenido):contenido;escribir_archivo(p,c);try{registrar(p,descripcion,true);}catch(...){fs::remove(p);throw;}return relativa(p);}
 string importar(const string& origen,const string& carpeta="",const string& nombre=""){auto src=fs::canonical(fs::path(origen));if(!fs::is_regular_file(src))throw runtime_error("El origen no es un archivo");auto p=ruta_segura((fs::path(carpeta)/(nombre.empty()?src.filename():fs::path(nombre))).generic_string());carpeta_destino(p);fs::copy_file(src,p);try{registrar(p,"",true);}catch(...){fs::remove(p);throw;}return relativa(p);}
 void listar(const string& carpeta=""){auto p=ruta_segura(carpeta);if(!fs::is_directory(p))throw runtime_error("Carpeta inexistente");vector<fs::path> v;for(auto& e:fs::directory_iterator(p))if(!interno(e.path())&&!e.is_symlink())v.push_back(e.path());sort(v.begin(),v.end());for(auto& x:v)cout<<(fs::is_directory(x)?"[CARPETA] ":"["+tipo(x)+"] ")<<relativa(x)<<"\n";}

 vector<string> carpetas() const {
  vector<string> v={""};
  for(fs::recursive_directory_iterator it(raiz_,fs::directory_options::skip_permission_denied),fin;it!=fin;++it){
   if(it->is_symlink()){it.disable_recursion_pending();continue;}
   if(it->is_directory())v.push_back(relativa(it->path()));
  }
  sort(v.begin()+1,v.end());return v;
 }
 string seleccionar_carpeta(){
  auto v=carpetas();cout<<"\n=== SELECCIONAR CARPETA ===\n";
  for(size_t i=0;i<v.size();++i){
   size_t nivel=v[i].empty()?0:count(v[i].begin(),v[i].end(),'/')+1;
   cout<<i<<". "<<string(nivel*4,' ')<<"[CARPETA] "<<(v[i].empty()?raiz_.filename().string():fs::path(v[i]).filename().string())<<"/\n";
  }
  string entrada=leer("Numero de carpeta (vacio = cancelar): ");
  if(entrada.empty())throw runtime_error("Operacion cancelada");
  size_t pos=0;unsigned long n=stoul(entrada,&pos);
  if(pos!=entrada.size()||n>=v.size())throw runtime_error("Seleccion no valida");
  return v[n];
 }
 void arbol() const {
  cout<<"\n=== ARBOL DOCUMENTAL ===\n[CARPETA] "<<raiz_.filename().string()<<"/\n";
  vector<fs::path> v;
  for(fs::recursive_directory_iterator it(raiz_,fs::directory_options::skip_permission_denied),fin;it!=fin;++it){
   if(it->is_symlink()){it.disable_recursion_pending();continue;}
   if(!interno(it->path()))v.push_back(it->path());
  }
  sort(v.begin(),v.end(),[&](const fs::path&a,const fs::path&b){return relativa(a)<relativa(b);});
  for(const auto& p:v){auto r=relativa(p);size_t nivel=count(r.begin(),r.end(),'/')+1;
   cout<<string(nivel*4,' ')<<(fs::is_directory(p)?"[CARPETA] ":"[ARCHIVO] ")<<p.filename().string()<<(fs::is_directory(p)?"/":"")<<"\n";
  }
 }
 void leer_documento(int id){auto d=fila(id);auto p=ruta_segura(d.ruta);cout<<"ID: "<<d.id<<"\nRuta: "<<d.ruta<<"\nTipo: "<<d.tipo<<"\nDescripcion: "<<d.descripcion<<"\nCreado: "<<d.creado<<"\nActualizado: "<<d.actualizado<<"\nTamano: "<<fs::file_size(p)<<" bytes\n";if(d.tipo=="externo")cout<<"Contenido binario: utiliza exportar.\n";else cout<<"--- CONTENIDO ---\n"<<texto_archivo(p)<<"\n";}
 vector<Documento> buscar(const string& texto,const string& filtro="",bool contenido=false){auto todos=consulta("SELECT id,ruta,tipo,descripcion,creado,actualizado FROM documentos WHERE (?='' OR tipo=?) ORDER BY ruta",{filtro,filtro});vector<Documento> r;auto q=minusculas(texto);for(auto& d:todos){if(q.empty()||minusculas(d.ruta+" "+d.descripcion).find(q)!=string::npos){r.push_back(d);continue;}if(contenido&&d.tipo!="externo"){try{if(minusculas(texto_archivo(ruta_segura(d.ruta))).find(q)!=string::npos)r.push_back(d);}catch(const exception&){}}}return r;}
 void actualizar(int id,const optional<string>& contenido,const optional<string>& descripcion){auto d=fila(id);auto p=ruta_segura(d.ruta);if(contenido){if(d.tipo=="externo")throw runtime_error("No se puede editar un archivo binario");string c=d.tipo=="json"?normalizar_json(*contenido):*contenido;escribir_archivo(p,c);}Sentencia st(db_,"UPDATE documentos SET descripcion=?,actualizado=? WHERE id=?");st.texto(1,descripcion?*descripcion:d.descripcion);st.texto(2,ahora());st.numero(3,id);comprobar(st.paso());}
 void eliminar(int id){auto d=fila(id);fs::remove(ruta_segura(d.ruta));Sentencia st(db_,"DELETE FROM documentos WHERE id=?");st.numero(1,id);comprobar(st.paso());}
 void eliminar_carpeta(const string& r){auto p=ruta_segura(r);if(p==raiz_)throw runtime_error("No se puede eliminar la raiz");if(!fs::is_directory(p))throw runtime_error("Carpeta inexistente");if(!fs::remove(p))throw runtime_error("No se ha eliminado la carpeta (debe estar vacia)");}
 void mover(int id,const string& r){auto d=fila(id);auto src=ruta_segura(d.ruta),dst=ruta_segura(r);carpeta_destino(dst);fs::rename(src,dst);try{Sentencia st(db_,"UPDATE documentos SET ruta=?,tipo=?,actualizado=? WHERE id=?");st.texto(1,relativa(dst));st.texto(2,tipo(dst));st.texto(3,ahora());st.numero(4,id);comprobar(st.paso());}catch(...){fs::rename(dst,src);throw;}}
 void copiar(int id,const string& r){auto d=fila(id);auto src=ruta_segura(d.ruta),dst=ruta_segura(r);carpeta_destino(dst);fs::copy_file(src,dst);try{registrar(dst,d.descripcion,true);}catch(...){fs::remove(dst);throw;}}
 string exportar(int id,const string& r){auto d=fila(id);auto dst=fs::absolute(fs::path(r)).lexically_normal();if(fs::exists(dst))throw runtime_error("El destino ya existe");if(!fs::is_directory(dst.parent_path()))throw runtime_error("Directorio de exportacion inexistente");fs::copy_file(ruta_segura(d.ruta),dst);return dst.string();}
 void estadisticas(){sincronizar();auto docs=consulta("SELECT id,ruta,tipo,descripcion,creado,actualizado FROM documentos");map<string,int> conteo;uintmax_t bytes=0;for(auto& d:docs){++conteo[d.tipo];bytes+=fs::file_size(ruta_segura(d.ruta));}cout<<"Documentos: "<<docs.size()<<"\nAlmacenamiento: "<<bytes<<" bytes\n";for(auto& [t,n]:conteo)cout<<"  "<<t<<": "<<n<<"\n";}
};
int pedir_id(){auto s=leer("ID: ");size_t pos=0;int n=stoi(s,&pos);if(pos!=s.size()||n<=0)throw runtime_error("ID no valido");return n;}
int main(int argc,char** argv){try{string raiz=argc>1?argv[1]:"jocarsa-documental";MotorDocumental motor(raiz);const string menu="\n=== JOCARSA | DOCUMENTAL (C++) ===\n1. Crear carpeta\n2. Crear documento JSON, Markdown o HTML\n3. Importar archivo externo\n4. Listar carpeta\n5. Leer documento\n6. Buscar documentos\n7. Actualizar documento\n8. Eliminar documento\n9. Mover documento\n10. Copiar documento\n11. Exportar documento\n12. Estadisticas\n13. Sincronizar archivos del disco\n14. Eliminar carpeta vacia\n15. Ver arbol completo\n0. Salir\n";while(true){cout<<menu;string op=leer("Opcion: ");if(!cin&&op.empty())break;if(op=="0")break;try{if(op=="1"){
 auto padre=motor.seleccionar_carpeta();auto nombre=leer("Nombre de la nueva carpeta: ");
 if(nombre.empty()||nombre=="."||nombre==".."||nombre.find('/')!=string::npos||nombre.find('\\')!=string::npos)throw runtime_error("Nombre de carpeta no valido");
 auto ruta=(fs::path(padre)/nombre).generic_string();
 auto creada=motor.crear_carpeta(ruta);cout<<"Carpeta creada: "<<creada<<"\n";
}
else if(op=="2"){auto padre=motor.seleccionar_carpeta();auto nombre=leer("Nombre con extension (.json/.md/.html): ");auto ruta=(fs::path(padre)/nombre).generic_string();string contenido=multilinea();string desc=leer("Descripcion (opcional): ");cout<<"Creado: "<<motor.crear(ruta,contenido,desc)<<"\n";}
else if(op=="3"){
 auto padre=motor.seleccionar_carpeta();auto origen=leer("Ruta de origen: ");
 auto importado=motor.importar(origen,padre);cout<<"Importado: "<<importado<<"\n";
}
else if(op=="4")motor.listar(motor.seleccionar_carpeta());
else if(op=="5")motor.leer_documento(pedir_id());
else if(op=="6"){auto q=leer("Buscar texto: "),t=leer("Tipo (json/markdown/html/externo o vacio): ");bool c=minusculas(leer("Buscar tambien dentro del contenido? (s/n): "))=="s";for(auto& d:motor.buscar(q,t,c))cout<<d.id<<" | "<<d.tipo<<" | "<<d.ruta<<"\n";}
else if(op=="7"){int id=pedir_id();optional<string> c,d;if(minusculas(leer("Editar contenido? (s/n): "))=="s")c=multilinea();auto desc=leer("Nueva descripcion (vacia = conservar): ");if(!desc.empty())d=desc;motor.actualizar(id,c,d);cout<<"Actualizado\n";}
else if(op=="8"){int id=pedir_id();motor.leer_documento(id);if(leer("Escribe ELIMINAR para confirmar: ")=="ELIMINAR"){motor.eliminar(id);cout<<"Eliminado\n";}}
else if(op=="9"){int id=pedir_id();motor.mover(id,leer("Nueva ruta completa: "));cout<<"Movido\n";}
else if(op=="10"){int id=pedir_id();motor.copiar(id,leer("Ruta de la copia: "));cout<<"Copiado\n";}
else if(op=="11"){int id=pedir_id();cout<<"Exportado: "<<motor.exportar(id,leer("Ruta de exportacion: "))<<"\n";}
else if(op=="12")motor.estadisticas();
else if(op=="13"){motor.sincronizar();cout<<"Sincronizacion terminada\n";}
else if(op=="14"){auto carpeta=motor.seleccionar_carpeta();if(carpeta.empty())throw runtime_error("No se puede eliminar la raiz");motor.eliminar_carpeta(carpeta);cout<<"Carpeta eliminada\n";}
else if(op=="15")motor.arbol();
else cout<<"Opcion desconocida\n";}catch(const exception& e){cerr<<"Error: "<<e.what()<<"\n";}}}catch(const exception& e){cerr<<"Error al iniciar: "<<e.what()<<"\n";return 1;}return 0;}
