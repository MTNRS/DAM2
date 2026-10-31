#include "GestorYAML.h"
#include <uuid/uuid.h>
#include <algorithm>
#include <fstream>
#include <regex>
#include <stdexcept>

namespace fs = std::filesystem;

GestorYAML::GestorYAML(fs::path raiz) : raiz_(fs::absolute(std::move(raiz))) {
    fs::create_directories(raiz_);
}

void GestorYAML::validarNombre(const std::string& nombre) {
    static const std::regex patron(R"(^[A-Za-z0-9_-]+$)");
    if (!std::regex_match(nombre, patron))
        throw std::invalid_argument("Nombre invalido: " + nombre);
}
void GestorYAML::validarId(const std::string& id) {
    static const std::regex patron(R"(^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$)");
    if (!std::regex_match(id, patron)) throw std::invalid_argument("UUID invalido: " + id);
}
fs::path GestorYAML::rutaBD(const std::string& bd) const {
    validarNombre(bd);
    return raiz_ / bd;
}
fs::path GestorYAML::rutaColeccion(const std::string& bd, const std::string& coleccion) const {
    validarNombre(coleccion);
    return rutaBD(bd) / coleccion;
}
fs::path GestorYAML::rutaDocumento(const std::string& bd, const std::string& coleccion, const std::string& id) const {
    validarId(id);
    return rutaColeccion(bd, coleccion) / (id + ".yaml");
}
void GestorYAML::crearBaseDatos(const std::string& bd) const {
    fs::create_directories(rutaBD(bd));
}
void GestorYAML::crearColeccion(const std::string& bd, const std::string& coleccion) const {
    fs::create_directories(rutaColeccion(bd, coleccion));
}
std::string GestorYAML::nuevoUUID() {
    uuid_t u;
    uuid_generate_random(u);
    char texto[37];
    uuid_unparse_lower(u, texto);
    return texto;
}
void GestorYAML::escribirAtomico(const fs::path& destino, const YAML::Node& nodo) {
    fs::create_directories(destino.parent_path());
    const fs::path temporal = destino.string() + "." + nuevoUUID() + ".tmp";
    try {
        {
            std::ofstream salida(temporal, std::ios::binary | std::ios::trunc);
            if (!salida) throw std::runtime_error("No se pudo abrir " + temporal.string());
            YAML::Emitter emisor;
            emisor.SetIndent(2);
            emisor << nodo;
            if (!emisor.good()) throw std::runtime_error("Error serializando YAML");
            salida << emisor.c_str() << '\n';
            salida.flush();
            if (!salida) throw std::runtime_error("Error escribiendo " + temporal.string());
        }
        fs::rename(temporal, destino);
    } catch (...) {
        std::error_code ec;
        fs::remove(temporal, ec);
        throw;
    }
}
std::string GestorYAML::guardar(const std::string& bd, const std::string& coleccion, const YAML::Node& objeto) const {
    if (!objeto.IsMap()) throw std::invalid_argument("El documento debe ser un mapa YAML");
    crearColeccion(bd, coleccion);
    std::string id;
    do { id = nuevoUUID(); } while (fs::exists(rutaDocumento(bd, coleccion, id)));
    YAML::Node copia = YAML::Clone(objeto);
    copia.remove("_id");
    copia["_id"] = id;
    escribirAtomico(rutaDocumento(bd, coleccion, id), copia);
    return id;
}
YAML::Node GestorYAML::leer(const std::string& bd, const std::string& coleccion, const std::string& id) const {
    auto ruta = rutaDocumento(bd, coleccion, id);
    if (!fs::is_regular_file(ruta)) return YAML::Node(YAML::NodeType::Undefined);
    return YAML::LoadFile(ruta.string());
}
std::vector<YAML::Node> GestorYAML::listar(const std::string& bd, const std::string& coleccion) const {
    std::vector<YAML::Node> resultado;
    const auto ruta = rutaColeccion(bd, coleccion);
    if (!fs::is_directory(ruta)) return resultado;
    std::vector<fs::path> archivos;
    for (const auto& entrada : fs::directory_iterator(ruta)) {
        if (entrada.is_regular_file() && entrada.path().extension() == ".yaml")
            archivos.push_back(entrada.path());
    }
    std::sort(archivos.begin(), archivos.end());
    for (const auto& archivo : archivos) resultado.push_back(YAML::LoadFile(archivo.string()));
    return resultado;
}
bool GestorYAML::actualizar(const std::string& bd, const std::string& coleccion, const std::string& id, const YAML::Node& objeto) const {
    if (!objeto.IsMap()) throw std::invalid_argument("El documento debe ser un mapa YAML");
    auto ruta = rutaDocumento(bd, coleccion, id);
    if (!fs::is_regular_file(ruta)) return false;
    YAML::Node copia = YAML::Clone(objeto);
    copia.remove("_id");
    copia["_id"] = id;
    escribirAtomico(ruta, copia);
    return true;
}
bool GestorYAML::eliminar(const std::string& bd, const std::string& coleccion, const std::string& id) const {
    return fs::remove(rutaDocumento(bd, coleccion, id));
}
bool GestorYAML::coincide(const YAML::Node& documento, const YAML::Node& condiciones) {
    if (!condiciones.IsMap()) throw std::invalid_argument("Las condiciones deben ser un mapa YAML");
    for (auto it = condiciones.begin(); it != condiciones.end(); ++it) {
        const auto clave = it->first.as<std::string>();
        const YAML::Node valor = documento[clave];
        if (!valor.IsDefined()) return false;
        if (it->second.IsMap()) {
            if (!valor.IsMap() || !coincide(valor, it->second)) return false;
        } else if (it->second.IsScalar()) {
            if (!valor.IsScalar() || valor.Scalar() != it->second.Scalar()) return false;
        } else if (YAML::Dump(valor) != YAML::Dump(it->second)) return false;
    }
    return true;
}
std::vector<YAML::Node> GestorYAML::filtrar(const std::string& bd, const std::string& coleccion, const YAML::Node& condiciones) const {
    if (!condiciones.IsMap()) throw std::invalid_argument("Las condiciones deben ser un mapa YAML");
    std::vector<YAML::Node> resultado;
    for (const auto& doc : listar(bd, coleccion))
        if (doc.IsMap() && coincide(doc, condiciones)) resultado.push_back(doc);
    return resultado;
}
std::size_t GestorYAML::contar(const std::string& bd, const std::string& coleccion) const {
    return listar(bd, coleccion).size();
}
std::vector<std::string> GestorYAML::basesDeDatos() const {
    std::vector<std::string> resultado;
    for (const auto& entrada : fs::directory_iterator(raiz_))
        if (entrada.is_directory()) resultado.push_back(entrada.path().filename().string());
    std::sort(resultado.begin(), resultado.end());
    return resultado;
}
std::vector<std::string> GestorYAML::colecciones(const std::string& bd) const {
    std::vector<std::string> resultado;
    auto ruta = rutaBD(bd);
    if (!fs::is_directory(ruta)) return resultado;
    for (const auto& entrada : fs::directory_iterator(ruta))
        if (entrada.is_directory()) resultado.push_back(entrada.path().filename().string());
    std::sort(resultado.begin(), resultado.end());
    return resultado;
}
