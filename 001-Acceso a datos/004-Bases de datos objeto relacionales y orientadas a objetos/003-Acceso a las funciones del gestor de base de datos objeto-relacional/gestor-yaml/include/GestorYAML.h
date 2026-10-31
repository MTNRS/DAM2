#pragma once
#include <yaml-cpp/yaml.h>
#include <filesystem>
#include <string>
#include <vector>

class GestorYAML {
public:
    explicit GestorYAML(std::filesystem::path raiz = "datos");
    void crearBaseDatos(const std::string& bd) const;
    void crearColeccion(const std::string& bd, const std::string& coleccion) const;
    std::string guardar(const std::string& bd, const std::string& coleccion, const YAML::Node& objeto) const;
    YAML::Node leer(const std::string& bd, const std::string& coleccion, const std::string& id) const;
    std::vector<YAML::Node> listar(const std::string& bd, const std::string& coleccion) const;
    bool actualizar(const std::string& bd, const std::string& coleccion, const std::string& id, const YAML::Node& objeto) const;
    bool eliminar(const std::string& bd, const std::string& coleccion, const std::string& id) const;
    std::vector<YAML::Node> filtrar(const std::string& bd, const std::string& coleccion, const YAML::Node& condiciones) const;
    std::size_t contar(const std::string& bd, const std::string& coleccion) const;
    std::vector<std::string> basesDeDatos() const;
    std::vector<std::string> colecciones(const std::string& bd) const;
private:
    std::filesystem::path raiz_;
    static void validarNombre(const std::string& nombre);
    static void validarId(const std::string& id);
    std::filesystem::path rutaBD(const std::string& bd) const;
    std::filesystem::path rutaColeccion(const std::string& bd, const std::string& coleccion) const;
    std::filesystem::path rutaDocumento(const std::string& bd, const std::string& coleccion, const std::string& id) const;
    static std::string nuevoUUID();
    static void escribirAtomico(const std::filesystem::path& destino, const YAML::Node& nodo);
    static bool coincide(const YAML::Node& documento, const YAML::Node& condiciones);
};
