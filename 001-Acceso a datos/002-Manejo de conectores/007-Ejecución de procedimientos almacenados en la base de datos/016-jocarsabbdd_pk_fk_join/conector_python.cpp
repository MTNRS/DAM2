#define PY_SSIZE_T_CLEAN
#include <Python.h>
#include "JocarsaBBDD.hpp"
#include <memory>
#include <stdexcept>

static std::unique_ptr<JocarsaBBDD> db;

static JocarsaBBDD& motor() {
    if (!db) db = std::make_unique<JocarsaBBDD>();
    return *db;
}

static PyObject* error_cpp(const std::exception& e) {
    PyErr_SetString(PyExc_RuntimeError, e.what());
    return nullptr;
}

static bool lista_a_vector(PyObject* lista, std::vector<std::string>& datos) {
    if (!PyList_Check(lista) && !PyTuple_Check(lista)) {
        PyErr_SetString(PyExc_TypeError, "Los datos deben ser una lista o tupla de strings");
        return false;
    }
    Py_ssize_t n = PySequence_Size(lista);
    for (Py_ssize_t i=0; i<n; ++i) {
        PyObject* item = PySequence_GetItem(lista, i);
        if (!item) return false;
        if (!PyUnicode_Check(item)) {
            Py_DECREF(item);
            PyErr_SetString(PyExc_TypeError, "Todos los valores deben ser strings");
            return false;
        }
        const char* s = PyUnicode_AsUTF8(item);
        if (!s) { Py_DECREF(item); return false; }
        datos.emplace_back(s);
        Py_DECREF(item);
    }
    return true;
}

static PyObject* mapa_a_dict(const std::map<std::string,std::string>& m) {
    PyObject* d = PyDict_New();
    for (const auto& [k,v] : m) {
        PyObject* pv = PyUnicode_FromString(v.c_str());
        PyDict_SetItemString(d, k.c_str(), pv);
        Py_DECREF(pv);
    }
    return d;
}

static PyObject* crear_base(PyObject*, PyObject* args) {
    const char* n; if (!PyArg_ParseTuple(args,"s",&n)) return nullptr;
    try { return PyBool_FromLong(motor().creaBaseDatos(n)); } catch(const std::exception& e){ return error_cpp(e); }
}
static PyObject* usar_base(PyObject*, PyObject* args) {
    const char* n; if (!PyArg_ParseTuple(args,"s",&n)) return nullptr;
    try { return PyBool_FromLong(motor().usaBaseDatos(n)); } catch(const std::exception& e){ return error_cpp(e); }
}
static PyObject* crear_tabla(PyObject*, PyObject* args) {
    const char *t,*e; if (!PyArg_ParseTuple(args,"ss",&t,&e)) return nullptr;
    try { return PyBool_FromLong(motor().creaTabla(t,e)); } catch(const std::exception& ex){ return error_cpp(ex); }
}
static PyObject* insertar(PyObject*, PyObject* args) {
    const char* t; PyObject* l; if (!PyArg_ParseTuple(args,"sO",&t,&l)) return nullptr;
    std::vector<std::string> d; if(!lista_a_vector(l,d)) return nullptr;
    try { return PyLong_FromLongLong(motor().insertarDatos(t,d)); } catch(const std::exception& e){ return error_cpp(e); }
}
static PyObject* seleccionar(PyObject*, PyObject* args) {
    const char* t; long long id; if(!PyArg_ParseTuple(args,"sL",&t,&id)) return nullptr;
    try { return mapa_a_dict(motor().seleccionar(t,id)); } catch(const std::exception& e){ return error_cpp(e); }
}
static PyObject* listar(PyObject*, PyObject* args) {
    const char* t; if(!PyArg_ParseTuple(args,"s",&t)) return nullptr;
    try {
        auto filas=motor().listarTodo(t); PyObject* l=PyList_New(0);
        for(const auto& f:filas){ PyObject* d=mapa_a_dict(f); PyList_Append(l,d); Py_DECREF(d); }
        return l;
    } catch(const std::exception& e){ return error_cpp(e); }
}
static PyObject* buscar(PyObject*, PyObject* args) {
    const char *t,*c,*v; if(!PyArg_ParseTuple(args,"sss",&t,&c,&v)) return nullptr;
    try {
        auto filas=motor().buscarColumna(t,c,v); PyObject* l=PyList_New(0);
        for(const auto& f:filas){ PyObject* d=mapa_a_dict(f); PyList_Append(l,d); Py_DECREF(d); }
        return l;
    } catch(const std::exception& e){ return error_cpp(e); }
}
static PyObject* actualizar(PyObject*, PyObject* args) {
    const char* t; long long id; PyObject* l; if(!PyArg_ParseTuple(args,"sLO",&t,&id,&l)) return nullptr;
    std::vector<std::string> d; if(!lista_a_vector(l,d)) return nullptr;
    try { return PyBool_FromLong(motor().actualizar(t,id,d)); } catch(const std::exception& e){ return error_cpp(e); }
}
static PyObject* eliminar(PyObject*, PyObject* args) {
    const char* t; long long id; if(!PyArg_ParseTuple(args,"sL",&t,&id)) return nullptr;
    try { return PyBool_FromLong(motor().eliminar(t,id)); } catch(const std::exception& e){ return error_cpp(e); }
}

static PyObject* alterar_anadir(PyObject*, PyObject* args) { const char *t,*c; if(!PyArg_ParseTuple(args,"ss",&t,&c)) return nullptr; try{return PyBool_FromLong(motor().alteraTablaAnadir(t,c));}catch(const std::exception&e){return error_cpp(e);} }
static PyObject* alterar_eliminar_columna(PyObject*, PyObject* args) { const char *t,*c; if(!PyArg_ParseTuple(args,"ss",&t,&c)) return nullptr; try{return PyBool_FromLong(motor().alteraTablaEliminarColumna(t,c));}catch(const std::exception&e){return error_cpp(e);} }
static PyObject* alterar_renombrar_columna(PyObject*, PyObject* args) { const char *t,*a,*n; if(!PyArg_ParseTuple(args,"sss",&t,&a,&n)) return nullptr; try{return PyBool_FromLong(motor().alteraTablaRenombrarColumna(t,a,n));}catch(const std::exception&e){return error_cpp(e);} }
static PyObject* alterar_modificar_columna(PyObject*, PyObject* args) { const char *t,*a,*n; if(!PyArg_ParseTuple(args,"sss",&t,&a,&n)) return nullptr; try{return PyBool_FromLong(motor().alteraTablaModificarColumna(t,a,n));}catch(const std::exception&e){return error_cpp(e);} }
static PyObject* truncar_tabla(PyObject*, PyObject* args) { const char* t; if(!PyArg_ParseTuple(args,"s",&t)) return nullptr; try{return PyBool_FromLong(motor().truncarTabla(t));}catch(const std::exception&e){return error_cpp(e);} }
static PyObject* eliminar_tabla(PyObject*, PyObject* args) { const char* t; if(!PyArg_ParseTuple(args,"s",&t)) return nullptr; try{return PyBool_FromLong(motor().eliminarTabla(t));}catch(const std::exception&e){return error_cpp(e);} }
static PyObject* eliminar_base(PyObject*, PyObject* args) { const char* n; if(!PyArg_ParseTuple(args,"s",&n)) return nullptr; try{return PyBool_FromLong(motor().eliminarBaseDatos(n));}catch(const std::exception&e){return error_cpp(e);} }

static PyObject* insertar_columnas(PyObject*, PyObject* args) {
    const char* t; PyObject *cols,*vals; if(!PyArg_ParseTuple(args,"sOO",&t,&cols,&vals)) return nullptr;
    std::vector<std::string> c,v; if(!lista_a_vector(cols,c)||!lista_a_vector(vals,v)) return nullptr;
    try{return PyLong_FromLongLong(motor().insertarConColumnas(t,c,v));}catch(const std::exception&e){return error_cpp(e);}
}
static PyObject* insertar_desde(PyObject*, PyObject* args) {
    const char *d,*o; if(!PyArg_ParseTuple(args,"ss",&d,&o)) return nullptr;
    try{return PyLong_FromLongLong(motor().insertarDesdeSeleccion(d,o));}catch(const std::exception&e){return error_cpp(e);}
}
static bool dict_a_mapa(PyObject* d,std::map<std::string,std::string>& out){
    if(!PyDict_Check(d)){PyErr_SetString(PyExc_TypeError,"Los cambios deben ser un dict de strings");return false;}
    PyObject *k,*v; Py_ssize_t pos=0; while(PyDict_Next(d,&pos,&k,&v)){
        if(!PyUnicode_Check(k)||!PyUnicode_Check(v)){PyErr_SetString(PyExc_TypeError,"Claves y valores deben ser strings");return false;}
        out[PyUnicode_AsUTF8(k)]=PyUnicode_AsUTF8(v);
    } return true;
}
static PyObject* actualizar_donde(PyObject*, PyObject* args) {
    const char* t; PyObject* d; const char *c="",*v=""; if(!PyArg_ParseTuple(args,"sO|ss",&t,&d,&c,&v)) return nullptr;
    std::map<std::string,std::string> cambios; if(!dict_a_mapa(d,cambios)) return nullptr;
    try{return PyLong_FromLongLong(motor().actualizarDonde(t,cambios,c,v));}catch(const std::exception&e){return error_cpp(e);}
}
static PyObject* eliminar_donde(PyObject*, PyObject* args) {
    const char* t; const char *c="",*v=""; if(!PyArg_ParseTuple(args,"s|ss",&t,&c,&v)) return nullptr;
    try{return PyLong_FromLongLong(motor().eliminarDonde(t,c,v));}catch(const std::exception&e){return error_cpp(e);}
}


static PyObject* consultar_dql(PyObject*, PyObject* args) {
    const char* q; if(!PyArg_ParseTuple(args,"s",&q)) return nullptr;
    try {
        auto r=motor().consultar(q); PyObject* lista=PyList_New(0);
        for(const auto& fila:r.filas){ PyObject* d=PyDict_New(); for(size_t i=0;i<r.columnas.size();++i){PyObject* v=PyUnicode_FromString(fila[i].c_str());PyDict_SetItemString(d,r.columnas[i].c_str(),v);Py_DECREF(v);}PyList_Append(lista,d);Py_DECREF(d);}
        return lista;
    } catch(const std::exception& e){ return error_cpp(e); }
}

static PyObject* anadir_clave_foranea(PyObject*, PyObject* args) {
    const char *t,*c,*tr,*cr="id";
    if(!PyArg_ParseTuple(args,"sss|s",&t,&c,&tr,&cr)) return nullptr;
    try{return PyBool_FromLong(motor().anadirClaveForanea(t,c,tr,cr));}catch(const std::exception&e){return error_cpp(e);}
}

static PyObject* claves_foraneas(PyObject*, PyObject* args) {
    const char* t; if(!PyArg_ParseTuple(args,"s",&t)) return nullptr;
    try{
        auto fks=motor().obtenerClavesForaneas(t); PyObject* l=PyList_New(0);
        for(const auto&fk:fks){PyObject*d=PyDict_New();PyObject*v=PyUnicode_FromString(fk.columna.c_str());PyDict_SetItemString(d,"columna",v);Py_DECREF(v);v=PyUnicode_FromString(fk.tablaReferenciada.c_str());PyDict_SetItemString(d,"tabla_referenciada",v);Py_DECREF(v);v=PyUnicode_FromString(fk.columnaReferenciada.c_str());PyDict_SetItemString(d,"columna_referenciada",v);Py_DECREF(v);PyList_Append(l,d);Py_DECREF(d);}
        return l;
    }catch(const std::exception&e){return error_cpp(e);}
}
static PyObject* configuracion(PyObject*, PyObject*) {
    try {
        auto& m=motor(); PyObject* d=PyDict_New();
        PyObject* p=PyUnicode_FromString(m.instalacion.c_str()); PyDict_SetItemString(d,"instalacion",p); Py_DECREF(p);
        p=PyLong_FromSize_t(m.tamanoRegistro); PyDict_SetItemString(d,"tamanoRegistro",p); Py_DECREF(p);
        return d;
    } catch(const std::exception& e){ return error_cpp(e); }
}

static PyMethodDef Metodos[] = {
    {"crear_base",crear_base,METH_VARARGS,"Crea una base de datos"},
    {"usar_base",usar_base,METH_VARARGS,"Selecciona una base de datos"},
    {"crear_tabla",crear_tabla,METH_VARARGS,"Crea una tabla"},
    {"anadir_clave_foranea",anadir_clave_foranea,METH_VARARGS,"Anade una clave foranea"},
    {"claves_foraneas",claves_foraneas,METH_VARARGS,"Lista claves foraneas"},
    {"alterar_anadir",alterar_anadir,METH_VARARGS,"Anade una columna"},
    {"alterar_eliminar_columna",alterar_eliminar_columna,METH_VARARGS,"Elimina una columna"},
    {"alterar_renombrar_columna",alterar_renombrar_columna,METH_VARARGS,"Renombra una columna"},
    {"alterar_modificar_columna",alterar_modificar_columna,METH_VARARGS,"Modifica una columna"},
    {"truncar_tabla",truncar_tabla,METH_VARARGS,"Vacia una tabla"},
    {"eliminar_tabla",eliminar_tabla,METH_VARARGS,"Elimina una tabla"},
    {"eliminar_base",eliminar_base,METH_VARARGS,"Elimina una base de datos"},
    {"insertar",insertar,METH_VARARGS,"Inserta un registro"},
    {"insertar_columnas",insertar_columnas,METH_VARARGS,"Inserta indicando columnas"},
    {"insertar_desde",insertar_desde,METH_VARARGS,"Inserta desde otra tabla"},
    {"actualizar_donde",actualizar_donde,METH_VARARGS,"Actualiza registros que cumplen una condicion"},
    {"eliminar_donde",eliminar_donde,METH_VARARGS,"Elimina registros que cumplen una condicion o todos"},
    {"seleccionar",seleccionar,METH_VARARGS,"Selecciona un registro por id"},
    {"consultar",consultar_dql,METH_VARARGS,"Ejecuta una consulta DQL en espanol"},
    {"listar",listar,METH_VARARGS,"Lista registros activos"},
    {"buscar",buscar,METH_VARARGS,"Busca por columna y valor"},
    {"actualizar",actualizar,METH_VARARGS,"Actualiza un registro"},
    {"eliminar",eliminar,METH_VARARGS,"Borrado logico"},
    {"configuracion",configuracion,METH_NOARGS,"Devuelve la configuracion activa"},
    {nullptr,nullptr,0,nullptr}
};
static PyModuleDef modulo={PyModuleDef_HEAD_INIT,"jocarsabbdd","Conector nativo Python para Jocarsa BBDD",-1,Metodos};
PyMODINIT_FUNC PyInit_jocarsabbdd(void){ return PyModule_Create(&modulo); }
