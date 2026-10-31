#pragma once
#include <nlohmann/json.hpp>
#include <sqlite3.h>
#include <string>
#include <vector>
#include <cstdint>
#include <functional>

class JocarsaORM {
public:
    using json = nlohmann::json;
    explicit JocarsaORM(const std::string& ruta);
    ~JocarsaORM();
    JocarsaORM(const JocarsaORM&) = delete;
    JocarsaORM& operator=(const JocarsaORM&) = delete;
    int64_t guardar(const std::string& coleccion, const json& dato);
    json obtener(const std::string& coleccion, int64_t id);
    json todos(const std::string& coleccion);
    json buscar(const std::string& coleccion, const std::string& campo, const json& valor);
    bool actualizar(const std::string& coleccion, int64_t id, const json& cambios);
    bool eliminar(const std::string& coleccion, int64_t id);
    int64_t contar(const std::string& coleccion);
    size_t importar(const std::string& coleccion, const std::string& carpeta);
    size_t exportar(const std::string& coleccion, const std::string& carpeta);
private:
    sqlite3* db_ = nullptr;
    void ejecutar(const std::string& sql);
    void insertarNodo(int64_t registro, const json& dato, int64_t padre = 0, const std::string& clave = "", int64_t posicion = -1);
    json reconstruirNodo(int64_t nodo);
    bool existe(const std::string& coleccion, int64_t id);
    std::vector<int64_t> ids(const std::string& coleccion);
    int64_t guardarInterno(const std::string& coleccion, const json& dato);
    void transaccion(const std::function<void()>& fn);
};
