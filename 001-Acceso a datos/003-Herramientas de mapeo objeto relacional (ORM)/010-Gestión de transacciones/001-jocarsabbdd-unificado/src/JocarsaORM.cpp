#include "JocarsaORM.hpp"
#include <json-c/json.h>
#include <stdexcept>
#include <algorithm>
#include <cctype>
#include <memory>
using J=std::unique_ptr<json_object,decltype(&json_object_put)>;
static J parse(const std::string& s){auto* p=json_tokener_parse(s.c_str());if(!p)throw std::invalid_argument("JSON invalido");return J(p,json_object_put);}
static std::string dump(json_object* o){return o?json_object_to_json_string_ext(o,JSON_C_TO_STRING_PLAIN):"null";}
static std::string hex(const std::string& s){static const char* d="0123456789abcdef";std::string r;r.reserve(s.size()*2);for(unsigned char c:s){r+=d[c>>4];r+=d[c&15];}return r;}
static std::string unhex(const std::string& s){if(s.size()%2)throw std::runtime_error("Documento corrupto");std::string r;for(size_t i=0;i<s.size();i+=2){auto n=[](char c)->int{if(c>='0'&&c<='9')return c-'0';if(c>='a'&&c<='f')return c-'a'+10;throw std::runtime_error("Hex invalido");};r+=char((n(s[i])<<4)|n(s[i+1]));}return r;}
std::string JocarsaORM::tabla(const std::string& c) const {if(c.empty())throw std::invalid_argument("Coleccion vacia");std::string t="orm_";for(unsigned char x:c){if((x<128&&std::isalnum(x))||x=='_')t+=char(x);else throw std::invalid_argument("Coleccion invalida");}return t;}
void JocarsaORM::asegurar(const std::string& c){auto t=tabla(c);auto v=db.listarTablas();if(std::find(v.begin(),v.end(),t)==v.end()&&!db.creaTablaTipada(t,{{"documento",TipoDato::TEXTO}}))throw std::runtime_error("Error creando tabla ORM");}
long long JocarsaORM::guardar(const std::string& c,const std::string& s){auto j=parse(s);asegurar(c);auto id=db.insertarConColumnas(tabla(c),{"documento"},{hex(dump(j.get()))});if(id<0)throw std::runtime_error("Error insertando");return id;}
std::string JocarsaORM::obtener(const std::string& c,long long id) const {auto t=tabla(c);auto v=db.listarTablas();if(std::find(v.begin(),v.end(),t)==v.end())return "null";auto r=db.seleccionar(t,id);auto it=r.find("documento");return it==r.end()?"null":unhex(it->second);}
std::string JocarsaORM::todos(const std::string& c) const {auto t=tabla(c);auto v=db.listarTablas();if(std::find(v.begin(),v.end(),t)==v.end())return "[]";J a(json_object_new_array(),json_object_put);auto rows=db.consultar("SELECCIONAR documento DE "+t);for(auto& r:rows.filas)if(!r.empty()){auto j=parse(unhex(r[0]));json_object_array_add(a.get(),j.release());}return dump(a.get());}
std::string JocarsaORM::buscar(const std::string& c,const std::string& campo,const std::string& valor) const {auto expected=parse(valor),all=parse(todos(c));J out(json_object_new_array(),json_object_put);for(size_t i=0;i<json_object_array_length(all.get());++i){auto* item=json_object_array_get_idx(all.get(),i);json_object* field=nullptr;if(json_object_is_type(item,json_type_object)&&json_object_object_get_ex(item,campo.c_str(),&field)&&json_object_equal(field,expected.get()))json_object_array_add(out.get(),json_object_get(item));}return dump(out.get());}
bool JocarsaORM::actualizar(const std::string& c,long long id,const std::string& cambios){auto before=obtener(c,id);if(before=="null")return false;auto old=parse(before),patch=parse(cambios);if(json_object_is_type(old.get(),json_type_object)&&json_object_is_type(patch.get(),json_type_object)){json_object_object_foreach(patch.get(),key,value){json_object_object_add(old.get(),key,json_object_get(value));}}else old=std::move(patch);auto n=db.actualizarDonde(tabla(c),{{"documento",hex(dump(old.get()))}},"id",std::to_string(id));if(n<0)throw std::runtime_error("Error actualizando");return n>0;}
bool JocarsaORM::eliminar(const std::string& c,long long id){return db.eliminar(tabla(c),id);}
long long JocarsaORM::contar(const std::string& c) const {auto all=parse(todos(c));return json_object_array_length(all.get());}
