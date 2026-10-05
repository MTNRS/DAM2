#include "JocarsaBBDD.hpp"
#include <iostream>
#include <sstream>

static std::vector<std::string> separar(const std::string&s){return JocarsaSerializador().desserializar(s);}
static void imprimir(const std::map<std::string,std::string>& r){for(auto const&[k,v]:r)std::cout<<k<<"="<<v<<"  ";std::cout<<"\n";}
static void ayuda(){std::cout<<R"(
Comandos:
  CREAR BASE nombre
  USAR nombre
  CREAR TABLA nombre campo1,campo2,campo3
  ALTERAR TABLA nombre ANADIR columna
  ALTERAR TABLA nombre ELIMINAR COLUMNA columna
  ALTERAR TABLA nombre RENOMBRAR COLUMNA antigua nueva
  ALTERAR TABLA nombre MODIFICAR COLUMNA antigua nueva
  TRUNCAR TABLA nombre
  ELIMINAR TABLA nombre
  ELIMINAR BASE nombre
  INSERTAR tabla valor1,valor2,valor3
  SELECCIONAR tabla id
  LISTAR tabla
  BUSCAR tabla columna valor
  ACTUALIZAR tabla id valor1,valor2,valor3
  ELIMINAR tabla id
  ESQUEMA tabla
  AYUDA
  SALIR
)";}
int main(int argc,char**argv){
 try{
  std::string cfg=argc>1?argv[1]:"config.json"; JocarsaBBDD db(cfg);
  std::cout<<"JocarsaBBDD C++ - terminal\n"; ayuda(); std::string linea;
  while(std::cout<<(db.basededatos.empty()?"jocarsa> ":("jocarsa["+db.basededatos+"]> "))&&std::getline(std::cin,linea)){
   std::istringstream ss(linea);std::string a,b,c;ss>>a;if(a.empty())continue;
   if(a=="SALIR"||a=="salir")break;if(a=="AYUDA"||a=="ayuda"){ayuda();continue;}
   if(a=="CREAR"){ss>>b;if(b=="BASE"){ss>>c;std::cout<<(db.creaBaseDatos(c)?"OK\n":"ERROR\n");}else if(b=="TABLA"){ss>>c;std::string e;std::getline(ss,e);if(!e.empty()&&e[0]==' ')e.erase(0,1);std::cout<<(db.creaTabla(c,e)?"OK\n":"ERROR\n");}continue;}
   if(a=="USAR"){ss>>b;std::cout<<(db.usaBaseDatos(b)?"OK\n":"ERROR\n");continue;}
   if(a=="ALTERAR"||a=="alterar"){
    ss>>b; if(b!="TABLA"&&b!="tabla"){std::cout<<"Sintaxis: ALTERAR TABLA ...\n";continue;}
    std::string tabla,accion; ss>>tabla>>accion; bool ok=false;
    if(accion=="ANADIR"||accion=="anadir"||accion=="AÑADIR"||accion=="añadir"){std::string col;ss>>col;ok=db.alteraTablaAnadir(tabla,col);}
    else if(accion=="ELIMINAR"||accion=="eliminar"){std::string palabra,col;ss>>palabra;if(palabra=="COLUMNA"||palabra=="columna")ss>>col;else col=palabra;ok=db.alteraTablaEliminarColumna(tabla,col);}
    else if(accion=="RENOMBRAR"||accion=="renombrar"){std::string palabra,antigua,nueva;ss>>palabra;if(palabra=="COLUMNA"||palabra=="columna")ss>>antigua>>nueva;else{antigua=palabra;ss>>nueva;}ok=db.alteraTablaRenombrarColumna(tabla,antigua,nueva);}
    else if(accion=="MODIFICAR"||accion=="modificar"){std::string palabra,antigua,nueva;ss>>palabra;if(palabra=="COLUMNA"||palabra=="columna")ss>>antigua>>nueva;else{antigua=palabra;ss>>nueva;}ok=db.alteraTablaModificarColumna(tabla,antigua,nueva);}
    else {std::cout<<"Accion ALTERAR desconocida\n";continue;}
    std::cout<<(ok?"OK\n":"ERROR\n");continue;
   }
   if(a=="TRUNCAR"||a=="truncar"){ss>>b>>c;if(b!="TABLA"&&b!="tabla"){std::cout<<"Sintaxis: TRUNCAR TABLA nombre\n";continue;}std::cout<<(db.truncarTabla(c)?"OK\n":"ERROR\n");continue;}
   if(a=="INSERTAR"){ss>>b;std::string d;std::getline(ss,d);if(!d.empty()&&d[0]==' ')d.erase(0,1);auto id=db.insertarDatos(b,separar(d));std::cout<<(id<0?"ERROR":"ID="+std::to_string(id))<<"\n";continue;}
   if(a=="SELECCIONAR"){long long id;ss>>b>>id;auto r=db.seleccionar(b,id);if(r.empty())std::cout<<"No encontrado\n";else imprimir(r);continue;}
   if(a=="LISTAR"){ss>>b;for(auto&r:db.listarTodo(b))imprimir(r);continue;}
   if(a=="BUSCAR"){ss>>b>>c;std::string v;std::getline(ss,v);if(!v.empty()&&v[0]==' ')v.erase(0,1);for(auto&r:db.buscarColumna(b,c,v))imprimir(r);continue;}
   if(a=="ACTUALIZAR"){long long id;ss>>b>>id;std::string d;std::getline(ss,d);if(!d.empty()&&d[0]==' ')d.erase(0,1);std::cout<<(db.actualizar(b,id,separar(d))?"OK\n":"ERROR\n");continue;}
   if(a=="ELIMINAR"||a=="eliminar"){
    ss>>b;
    if(b=="TABLA"||b=="tabla"){ss>>c;std::cout<<(db.eliminarTabla(c)?"OK\n":"ERROR\n");continue;}
    if(b=="BASE"||b=="base"){ss>>c;std::cout<<(db.eliminarBaseDatos(c)?"OK\n":"ERROR\n");continue;}
    long long id;ss>>id;std::cout<<(db.eliminar(b,id)?"OK\n":"ERROR\n");continue;
   }
   if(a=="ESQUEMA"){ss>>b;auto e=db.obtenerEsquema(b);for(size_t i=0;i<e.size();++i)std::cout<<(i?",":"")<<e[i];std::cout<<"\n";continue;}
   std::cout<<"Comando desconocido. Escribe AYUDA.\n";
  }
 }catch(const std::exception&e){std::cerr<<"Error: "<<e.what()<<"\n";return 1;}
}
