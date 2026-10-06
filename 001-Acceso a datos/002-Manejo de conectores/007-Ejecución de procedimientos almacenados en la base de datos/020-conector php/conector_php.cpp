extern "C" {
#include <php.h>
#include <ext/standard/info.h>
#include <Zend/zend_exceptions.h>
}
#include "JocarsaBBDD.hpp"
#include <algorithm>
#include <cctype>
#include <map>
#include <memory>
#include <string>
#include <vector>

static std::unique_ptr<JocarsaBBDD> g_motor;
static std::string g_config = "config.json";

static JocarsaBBDD& motor(){
    if(!g_motor) g_motor = std::make_unique<JocarsaBBDD>(g_config);
    return *g_motor;
}
static void lanzar(const std::exception& e){ zend_throw_exception(nullptr,e.what(),0); }
static bool php_array_strings(zval* arr,std::vector<std::string>& out){
    if(Z_TYPE_P(arr)!=IS_ARRAY) return false;
    zval* v; ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(arr),v){
        zend_string* zs=zval_get_string(v); out.emplace_back(ZSTR_VAL(zs),ZSTR_LEN(zs)); zend_string_release(zs);
    } ZEND_HASH_FOREACH_END(); return true;
}
static bool php_assoc_strings(zval* arr,std::map<std::string,std::string>& out){
    if(Z_TYPE_P(arr)!=IS_ARRAY) return false;
    zend_string* key; zend_ulong idx; zval* v;
    ZEND_HASH_FOREACH_KEY_VAL(Z_ARRVAL_P(arr),idx,key,v){
        if(!key) continue; zend_string* zs=zval_get_string(v); out[ZSTR_VAL(key)]=std::string(ZSTR_VAL(zs),ZSTR_LEN(zs)); zend_string_release(zs);
    } ZEND_HASH_FOREACH_END(); return true;
}
static TipoDato tipo_desde_php(const std::string& n){
    std::string x=n; std::transform(x.begin(),x.end(),x.begin(),[](unsigned char c){return std::toupper(c);});
    if(x=="ENTERO"||x=="INT")return TipoDato::ENTERO;
    if(x=="REAL"||x=="DECIMAL"||x=="FLOAT")return TipoDato::REAL;
    if(x=="BOOLEANO"||x=="BOOL")return TipoDato::BOOLEANO;
    return TipoDato::TEXTO;
}
static const char* nombre_tipo(TipoDato t){return t==TipoDato::ENTERO?"ENTERO":t==TipoDato::REAL?"REAL":t==TipoDato::BOOLEANO?"BOOLEANO":"TEXTO";}
static void mapa_a_array(const std::map<std::string,std::string>& m,zval* out){array_init(out);for(const auto&[k,v]:m)add_assoc_stringl(out,k.c_str(),v.c_str(),v.size());}

PHP_FUNCTION(jocarsabbdd_configurar){char* c;size_t n;ZEND_PARSE_PARAMETERS_START(1,1) Z_PARAM_STRING(c,n) ZEND_PARSE_PARAMETERS_END();try{g_config.assign(c,n);g_motor=std::make_unique<JocarsaBBDD>(g_config);RETURN_TRUE;}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_crear_base){char*n;size_t l;ZEND_PARSE_PARAMETERS_START(1,1) Z_PARAM_STRING(n,l) ZEND_PARSE_PARAMETERS_END();try{RETURN_BOOL(motor().creaBaseDatos(std::string(n,l)));}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_usar_base){char*n;size_t l;ZEND_PARSE_PARAMETERS_START(1,1) Z_PARAM_STRING(n,l) ZEND_PARSE_PARAMETERS_END();try{RETURN_BOOL(motor().usaBaseDatos(std::string(n,l)));}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_crear_tabla){char*t,*e;size_t tl,el;ZEND_PARSE_PARAMETERS_START(2,2) Z_PARAM_STRING(t,tl) Z_PARAM_STRING(e,el) ZEND_PARSE_PARAMETERS_END();try{RETURN_BOOL(motor().creaTabla(std::string(t,tl),std::string(e,el)));}catch(const std::exception&e2){lanzar(e2);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_crear_tabla_tipada){char*t;size_t tl;zval* a;ZEND_PARSE_PARAMETERS_START(2,2) Z_PARAM_STRING(t,tl) Z_PARAM_ARRAY(a) ZEND_PARSE_PARAMETERS_END();try{std::vector<DefinicionColumna>d;zend_string*k;zend_ulong i;zval*v;ZEND_HASH_FOREACH_KEY_VAL(Z_ARRVAL_P(a),i,k,v){if(!k)continue;zend_string*zs=zval_get_string(v);d.push_back({ZSTR_VAL(k),tipo_desde_php(ZSTR_VAL(zs))});zend_string_release(zs);}ZEND_HASH_FOREACH_END();RETURN_BOOL(motor().creaTablaTipada(std::string(t,tl),d));}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_tipos){char*t;size_t tl;ZEND_PARSE_PARAMETERS_START(1,1) Z_PARAM_STRING(t,tl) ZEND_PARSE_PARAMETERS_END();try{auto m=motor().obtenerTipos(std::string(t,tl));array_init(return_value);for(const auto&[k,v]:m)add_assoc_string(return_value,k.c_str(),const_cast<char*>(nombre_tipo(v)));}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_insertar){char*t;size_t tl;zval*a;ZEND_PARSE_PARAMETERS_START(2,2) Z_PARAM_STRING(t,tl) Z_PARAM_ARRAY(a) ZEND_PARSE_PARAMETERS_END();try{std::vector<std::string>v;php_array_strings(a,v);RETURN_LONG(motor().insertarDatos(std::string(t,tl),v));}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_insertar_columnas){char*t;size_t tl;zval*c,*v;ZEND_PARSE_PARAMETERS_START(3,3) Z_PARAM_STRING(t,tl) Z_PARAM_ARRAY(c) Z_PARAM_ARRAY(v) ZEND_PARSE_PARAMETERS_END();try{std::vector<std::string>cc,vv;php_array_strings(c,cc);php_array_strings(v,vv);RETURN_LONG(motor().insertarConColumnas(std::string(t,tl),cc,vv));}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_seleccionar){char*t;size_t tl;zend_long id;ZEND_PARSE_PARAMETERS_START(2,2) Z_PARAM_STRING(t,tl) Z_PARAM_LONG(id) ZEND_PARSE_PARAMETERS_END();try{mapa_a_array(motor().seleccionar(std::string(t,tl),id),return_value);}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_listar){char*t;size_t tl;ZEND_PARSE_PARAMETERS_START(1,1) Z_PARAM_STRING(t,tl) ZEND_PARSE_PARAMETERS_END();try{array_init(return_value);for(const auto&m:motor().listarTodo(std::string(t,tl))){zval row;mapa_a_array(m,&row);add_next_index_zval(return_value,&row);}}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_consultar){char*q;size_t ql;ZEND_PARSE_PARAMETERS_START(1,1) Z_PARAM_STRING(q,ql) ZEND_PARSE_PARAMETERS_END();try{auto r=motor().consultar(std::string(q,ql));array_init(return_value);for(const auto&f:r.filas){zval row;array_init(&row);for(size_t i=0;i<r.columnas.size();++i)add_assoc_stringl(&row,r.columnas[i].c_str(),f[i].c_str(),f[i].size());add_next_index_zval(return_value,&row);}}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_actualizar_donde){char*t,*c="",*v="";size_t tl,cl=0,vl=0;zval*d;ZEND_PARSE_PARAMETERS_START(2,4) Z_PARAM_STRING(t,tl) Z_PARAM_ARRAY(d) Z_PARAM_OPTIONAL Z_PARAM_STRING(c,cl) Z_PARAM_STRING(v,vl) ZEND_PARSE_PARAMETERS_END();try{std::map<std::string,std::string>m;php_assoc_strings(d,m);RETURN_LONG(motor().actualizarDonde(std::string(t,tl),m,std::string(c,cl),std::string(v,vl)));}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_eliminar_donde){char*t,*c="",*v="";size_t tl,cl=0,vl=0;ZEND_PARSE_PARAMETERS_START(1,3) Z_PARAM_STRING(t,tl) Z_PARAM_OPTIONAL Z_PARAM_STRING(c,cl) Z_PARAM_STRING(v,vl) ZEND_PARSE_PARAMETERS_END();try{RETURN_LONG(motor().eliminarDonde(std::string(t,tl),std::string(c,cl),std::string(v,vl)));}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_anadir_clave_foranea){char*t,*c,*tr,*cr=(char*)"id";size_t tl,cl,trl,crl=2;ZEND_PARSE_PARAMETERS_START(3,4) Z_PARAM_STRING(t,tl) Z_PARAM_STRING(c,cl) Z_PARAM_STRING(tr,trl) Z_PARAM_OPTIONAL Z_PARAM_STRING(cr,crl) ZEND_PARSE_PARAMETERS_END();try{RETURN_BOOL(motor().anadirClaveForanea(std::string(t,tl),std::string(c,cl),std::string(tr,trl),std::string(cr,crl)));}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_establecer_no_nulo){char*t,*c;size_t tl,cl;ZEND_PARSE_PARAMETERS_START(2,2) Z_PARAM_STRING(t,tl) Z_PARAM_STRING(c,cl) ZEND_PARSE_PARAMETERS_END();try{RETURN_BOOL(motor().establecerNoNulo(std::string(t,tl),std::string(c,cl)));}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_establecer_unico){char*t,*c;size_t tl,cl;ZEND_PARSE_PARAMETERS_START(2,2) Z_PARAM_STRING(t,tl) Z_PARAM_STRING(c,cl) ZEND_PARSE_PARAMETERS_END();try{RETURN_BOOL(motor().establecerUnico(std::string(t,tl),std::string(c,cl)));}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_establecer_por_defecto){char*t,*c,*v;size_t tl,cl,vl;ZEND_PARSE_PARAMETERS_START(3,3) Z_PARAM_STRING(t,tl) Z_PARAM_STRING(c,cl) Z_PARAM_STRING(v,vl) ZEND_PARSE_PARAMETERS_END();try{RETURN_BOOL(motor().establecerPorDefecto(std::string(t,tl),std::string(c,cl),std::string(v,vl)));}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_crear_vista){char*n,*q;size_t nl,ql;ZEND_PARSE_PARAMETERS_START(2,2) Z_PARAM_STRING(n,nl) Z_PARAM_STRING(q,ql) ZEND_PARSE_PARAMETERS_END();try{RETURN_BOOL(motor().crearVista(std::string(n,nl),std::string(q,ql)));}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_eliminar_vista){char*n;size_t nl;ZEND_PARSE_PARAMETERS_START(1,1) Z_PARAM_STRING(n,nl) ZEND_PARSE_PARAMETERS_END();try{RETURN_BOOL(motor().eliminarVista(std::string(n,nl)));}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_listar_vistas){ZEND_PARSE_PARAMETERS_NONE();try{array_init(return_value);for(const auto&v:motor().listarVistas())add_next_index_stringl(return_value,v.c_str(),v.size());}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_iniciar_transaccion){ZEND_PARSE_PARAMETERS_NONE();try{RETURN_BOOL(motor().iniciarTransaccion());}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_confirmar){ZEND_PARSE_PARAMETERS_NONE();try{RETURN_BOOL(motor().confirmarTransaccion());}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_deshacer){ZEND_PARSE_PARAMETERS_NONE();try{RETURN_BOOL(motor().deshacerTransaccion());}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}
PHP_FUNCTION(jocarsabbdd_configuracion){ZEND_PARSE_PARAMETERS_NONE();try{array_init(return_value);add_assoc_string(return_value,"instalacion",const_cast<char*>(motor().instalacion.c_str()));add_assoc_string(return_value,"base",const_cast<char*>(motor().basededatos.c_str()));add_assoc_long(return_value,"tamanoRegistro",motor().tamanoRegistro);}catch(const std::exception&e){lanzar(e);RETURN_THROWS();}}

ZEND_BEGIN_ARG_INFO_EX(arginfo_void,0,0,0) ZEND_END_ARG_INFO()
ZEND_BEGIN_ARG_INFO_EX(arginfo_s,0,0,1) ZEND_ARG_TYPE_INFO(0,valor,IS_STRING,0) ZEND_END_ARG_INFO()
ZEND_BEGIN_ARG_INFO_EX(arginfo_ss,0,0,2) ZEND_ARG_TYPE_INFO(0,a,IS_STRING,0) ZEND_ARG_TYPE_INFO(0,b,IS_STRING,0) ZEND_END_ARG_INFO()
ZEND_BEGIN_ARG_INFO_EX(arginfo_sa,0,0,2) ZEND_ARG_TYPE_INFO(0,a,IS_STRING,0) ZEND_ARG_ARRAY_INFO(0,b,0) ZEND_END_ARG_INFO()
ZEND_BEGIN_ARG_INFO_EX(arginfo_saa,0,0,3) ZEND_ARG_TYPE_INFO(0,a,IS_STRING,0) ZEND_ARG_ARRAY_INFO(0,b,0) ZEND_ARG_ARRAY_INFO(0,c,0) ZEND_END_ARG_INFO()
ZEND_BEGIN_ARG_INFO_EX(arginfo_sl,0,0,2) ZEND_ARG_TYPE_INFO(0,a,IS_STRING,0) ZEND_ARG_TYPE_INFO(0,id,IS_LONG,0) ZEND_END_ARG_INFO()
ZEND_BEGIN_ARG_INFO_EX(arginfo_sass,0,0,2) ZEND_ARG_TYPE_INFO(0,a,IS_STRING,0) ZEND_ARG_ARRAY_INFO(0,b,0) ZEND_ARG_TYPE_INFO(0,c,IS_STRING,0) ZEND_ARG_TYPE_INFO(0,d,IS_STRING,0) ZEND_END_ARG_INFO()
ZEND_BEGIN_ARG_INFO_EX(arginfo_sss,0,0,3) ZEND_ARG_TYPE_INFO(0,a,IS_STRING,0) ZEND_ARG_TYPE_INFO(0,b,IS_STRING,0) ZEND_ARG_TYPE_INFO(0,c,IS_STRING,0) ZEND_END_ARG_INFO()
ZEND_BEGIN_ARG_INFO_EX(arginfo_ssss,0,0,3) ZEND_ARG_TYPE_INFO(0,a,IS_STRING,0) ZEND_ARG_TYPE_INFO(0,b,IS_STRING,0) ZEND_ARG_TYPE_INFO(0,c,IS_STRING,0) ZEND_ARG_TYPE_INFO(0,d,IS_STRING,0) ZEND_END_ARG_INFO()

static const zend_function_entry funcs[]={
 PHP_FE(jocarsabbdd_configurar,arginfo_s) PHP_FE(jocarsabbdd_crear_base,arginfo_s) PHP_FE(jocarsabbdd_usar_base,arginfo_s)
 PHP_FE(jocarsabbdd_crear_tabla,arginfo_ss) PHP_FE(jocarsabbdd_crear_tabla_tipada,arginfo_sa) PHP_FE(jocarsabbdd_tipos,arginfo_s)
 PHP_FE(jocarsabbdd_insertar,arginfo_sa) PHP_FE(jocarsabbdd_insertar_columnas,arginfo_saa) PHP_FE(jocarsabbdd_seleccionar,arginfo_sl)
 PHP_FE(jocarsabbdd_listar,arginfo_s) PHP_FE(jocarsabbdd_consultar,arginfo_s) PHP_FE(jocarsabbdd_actualizar_donde,arginfo_sass)
 PHP_FE(jocarsabbdd_eliminar_donde,arginfo_sss) PHP_FE(jocarsabbdd_anadir_clave_foranea,arginfo_ssss)
 PHP_FE(jocarsabbdd_establecer_no_nulo,arginfo_ss) PHP_FE(jocarsabbdd_establecer_unico,arginfo_ss) PHP_FE(jocarsabbdd_establecer_por_defecto,arginfo_sss)
 PHP_FE(jocarsabbdd_crear_vista,arginfo_ss) PHP_FE(jocarsabbdd_eliminar_vista,arginfo_s) PHP_FE(jocarsabbdd_listar_vistas,arginfo_void)
 PHP_FE(jocarsabbdd_iniciar_transaccion,arginfo_void) PHP_FE(jocarsabbdd_confirmar,arginfo_void) PHP_FE(jocarsabbdd_deshacer,arginfo_void)
 PHP_FE(jocarsabbdd_configuracion,arginfo_void) PHP_FE_END
};

PHP_MINFO_FUNCTION(jocarsabbdd){php_info_print_table_start();php_info_print_table_header(2,"JocarsaBBDD support","enabled");php_info_print_table_row(2,"Version","1.0");php_info_print_table_end();}
zend_module_entry jocarsabbdd_module_entry={STANDARD_MODULE_HEADER,"jocarsabbdd",funcs,nullptr,nullptr,nullptr,nullptr,PHP_MINFO(jocarsabbdd),"1.0",STANDARD_MODULE_PROPERTIES};
#ifdef COMPILE_DL_JOCARSABBDD
extern "C" { ZEND_GET_MODULE(jocarsabbdd) }
#endif
