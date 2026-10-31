#include "JocarsaORM.h"
#include <iostream>
#include <filesystem>
#include <cstdlib>
using json=nlohmann::json;
int main(int argc,char** argv){
 try{
  if(argc<3){std::cerr<<"Uso: jocarsaorm <archivo.db> <comando> [coleccion] [argumentos]\nComandos: crear, guardar, obtener, todos, buscar, actualizar, eliminar, contar, importar, exportar\n";return 2;}
  JocarsaORM orm(argv[1]);std::string cmd=argv[2];
  if(cmd=="crear"){std::cout<<"Base preparada: "<<argv[1]<<"\n";return 0;}
  if(argc<4)throw std::runtime_error("Falta colección");std::string c=argv[3];
  if(cmd=="guardar"&&argc>=5)std::cout<<orm.guardar(c,json::parse(argv[4]))<<"\n";
  else if(cmd=="obtener"&&argc>=5)std::cout<<orm.obtener(c,std::stoll(argv[4])).dump(4)<<"\n";
  else if(cmd=="todos")std::cout<<orm.todos(c).dump(4)<<"\n";
  else if(cmd=="buscar"&&argc>=6)std::cout<<orm.buscar(c,argv[4],json::parse(argv[5])).dump(4)<<"\n";
  else if(cmd=="actualizar"&&argc>=6)std::cout<<(orm.actualizar(c,std::stoll(argv[4]),json::parse(argv[5]))?"OK":"NO EXISTE")<<"\n";
  else if(cmd=="eliminar"&&argc>=5)std::cout<<(orm.eliminar(c,std::stoll(argv[4]))?"OK":"NO EXISTE")<<"\n";
  else if(cmd=="contar")std::cout<<orm.contar(c)<<"\n";
  else if(cmd=="importar"&&argc>=5)std::cout<<orm.importar(c,argv[4])<<" registros importados\n";
  else if(cmd=="exportar"&&argc>=5)std::cout<<orm.exportar(c,argv[4])<<" registros exportados\n";
  else throw std::runtime_error("Comando o argumentos incorrectos");
  return 0;
 }catch(const std::exception& e){std::cerr<<"ERROR: "<<e.what()<<"\n";return 1;}
}
