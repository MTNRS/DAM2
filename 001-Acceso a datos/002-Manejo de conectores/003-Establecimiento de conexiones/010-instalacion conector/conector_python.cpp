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
    {"insertar",insertar,METH_VARARGS,"Inserta un registro"},
    {"seleccionar",seleccionar,METH_VARARGS,"Selecciona un registro por id"},
    {"listar",listar,METH_VARARGS,"Lista registros activos"},
    {"buscar",buscar,METH_VARARGS,"Busca por columna y valor"},
    {"actualizar",actualizar,METH_VARARGS,"Actualiza un registro"},
    {"eliminar",eliminar,METH_VARARGS,"Borrado logico"},
    {"configuracion",configuracion,METH_NOARGS,"Devuelve la configuracion activa"},
    {nullptr,nullptr,0,nullptr}
};
static PyModuleDef modulo={PyModuleDef_HEAD_INIT,"jocarsabbdd","Conector nativo Python para Jocarsa BBDD",-1,Metodos};
PyMODINIT_FUNC PyInit_jocarsabbdd(void){ return PyModule_Create(&modulo); }
