#pragma once
#include <cstdint>
#include <map>
#include <string>
#include <vector>

class JocarsaSerializador {
public:
    std::string serializar(const std::vector<std::string>& lista, char delimitador = ',') const;
    std::vector<std::string> desserializar(const std::string& cadena, char delimitador = ',') const;
};

struct ClaveForanea {
    std::string columna;
    std::string tablaReferenciada;
    std::string columnaReferenciada;
};

struct ResultadoConsulta {
    std::vector<std::string> columnas;
    std::vector<std::vector<std::string>> filas;
};

class JocarsaBBDD {
public:
    std::string instalacion;
    std::string basededatos;
    std::size_t tamanoRegistro = 512;

    explicit JocarsaBBDD(const std::string& config = "config.json");
    bool creaBaseDatos(const std::string& nombre);
    bool usaBaseDatos(const std::string& nombre);
    bool creaTabla(const std::string& nombre, const std::string& esquema);
    bool alteraTablaAnadir(const std::string& tabla, const std::string& columna);
    bool alteraTablaEliminarColumna(const std::string& tabla, const std::string& columna);
    bool alteraTablaRenombrarColumna(const std::string& tabla, const std::string& antigua, const std::string& nueva);
    bool alteraTablaModificarColumna(const std::string& tabla, const std::string& antigua, const std::string& nueva);
    bool truncarTabla(const std::string& tabla);
    bool eliminarTabla(const std::string& tabla);
    bool eliminarBaseDatos(const std::string& nombre);
    bool anadirClaveForanea(const std::string& tabla, const std::string& columna, const std::string& tablaReferenciada, const std::string& columnaReferenciada = "id");
    std::vector<ClaveForanea> obtenerClavesForaneas(const std::string& tabla) const;
    std::string obtenerClavePrimaria(const std::string& tabla) const;
    std::vector<std::string> obtenerEsquema(const std::string& tabla) const;
    long long siguienteId(const std::string& tabla) const;
    long long insertarDatos(const std::string& tabla, const std::vector<std::string>& datos);
    long long insertarConColumnas(const std::string& tabla, const std::vector<std::string>& columnas, const std::vector<std::string>& valores);
    long long insertarDesdeSeleccion(const std::string& destino, const std::string& origen);
    long long actualizarDonde(const std::string& tabla, const std::map<std::string,std::string>& cambios, const std::string& columnaDonde = "", const std::string& valorDonde = "");
    long long eliminarDonde(const std::string& tabla, const std::string& columnaDonde = "", const std::string& valorDonde = "");
    std::int64_t buscarPosicion(const std::string& tabla, long long id) const;
    std::vector<std::string> leerRegistro(const std::string& tabla, long long id) const;
    std::map<std::string,std::string> seleccionar(const std::string& tabla, long long id) const;
    std::vector<std::map<std::string,std::string>> listarTodo(const std::string& tabla) const;
    ResultadoConsulta consultar(const std::string& consulta) const;
    std::vector<std::map<std::string,std::string>> buscarColumna(const std::string& tabla, const std::string& columna, const std::string& valor) const;
    bool actualizar(const std::string& tabla, long long id, const std::vector<std::string>& datos);
    bool eliminar(const std::string& tabla, long long id);

private:
    std::string rutaTabla(const std::string& tabla, const std::string& extension) const;
    std::string prepararRegistro(const std::vector<std::string>& elementos) const;
    static std::string trimDerecha(std::string s);
    bool validarClavesForaneas(const std::string& tabla, const std::vector<std::string>& datos) const;
    bool registroReferenciado(const std::string& tabla, const std::string& columna, const std::string& valor) const;
    bool puedeEliminarRegistro(const std::string& tabla, const std::map<std::string,std::string>& registro) const;
};
