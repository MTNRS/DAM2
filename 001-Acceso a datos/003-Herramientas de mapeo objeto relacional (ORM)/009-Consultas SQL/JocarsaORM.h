
#ifndef JOCARSA_ORM_H
#define JOCARSA_ORM_H

#include <sqlite3.h>
#include <nlohmann/json.hpp>

#include <algorithm>
#include <filesystem>
#include <fstream>
#include <iostream>
#include <memory>
#include <stdexcept>
#include <string>
#include <vector>

namespace fs = std::filesystem;
using json = nlohmann::json;

class JocarsaORM {
private:
    sqlite3* db = nullptr;

    static void comprobar(int rc, sqlite3* db) {
        if (rc != SQLITE_OK && rc != SQLITE_DONE &&
            rc != SQLITE_ROW) {
            throw std::runtime_error(sqlite3_errmsg(db));
        }
    }

    class Statement {
    public:
        sqlite3_stmt* stmt = nullptr;
        sqlite3* db;

        Statement(sqlite3* conexion, const std::string& sql)
            : db(conexion) {
            comprobar(
                sqlite3_prepare_v2(
                    db, sql.c_str(), -1, &stmt, nullptr
                ), db
            );
        }

        ~Statement() {
            sqlite3_finalize(stmt);
        }

        Statement(const Statement&) = delete;
        Statement& operator=(const Statement&) = delete;

        void texto(int indice, const std::string& valor) {
            comprobar(
                sqlite3_bind_text(
                    stmt, indice, valor.c_str(),
                    static_cast<int>(valor.size()),
                    SQLITE_TRANSIENT
                ), db
            );
        }

        void entero(int indice, sqlite3_int64 valor) {
            comprobar(
                sqlite3_bind_int64(stmt, indice, valor), db
            );
        }

        void nulo(int indice) {
            comprobar(sqlite3_bind_null(stmt, indice), db);
        }

        int paso() {
            int rc = sqlite3_step(stmt);
            if (rc != SQLITE_ROW && rc != SQLITE_DONE)
                throw std::runtime_error(sqlite3_errmsg(db));
            return rc;
        }
    };

    void ejecutar(const std::string& sql) {
        char* error = nullptr;
        int rc = sqlite3_exec(
            db, sql.c_str(), nullptr, nullptr, &error
        );

        if (rc != SQLITE_OK) {
            std::string mensaje = error ? error : "Error SQL";
            sqlite3_free(error);
            throw std::runtime_error(mensaje);
        }
    }

    void iniciar() {
        ejecutar("PRAGMA foreign_keys=ON");

        ejecutar(R"SQL(
            CREATE TABLE IF NOT EXISTS jocarsa_registros (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                coleccion TEXT NOT NULL,
                UNIQUE(coleccion, id)
            )
        )SQL");

        ejecutar(R"SQL(
            CREATE TABLE IF NOT EXISTS jocarsa_nodos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                registro_id INTEGER NOT NULL,
                padre_id INTEGER,
                clave TEXT,
                posicion INTEGER,
                tipo TEXT NOT NULL,
                valor TEXT,
                FOREIGN KEY(registro_id)
                    REFERENCES jocarsa_registros(id)
                    ON DELETE CASCADE,
                FOREIGN KEY(padre_id)
                    REFERENCES jocarsa_nodos(id)
                    ON DELETE CASCADE
            )
        )SQL");

        ejecutar(R"SQL(
            CREATE INDEX IF NOT EXISTS idx_jocarsa_nodos_registro
            ON jocarsa_nodos(registro_id)
        )SQL");

        ejecutar(R"SQL(
            CREATE INDEX IF NOT EXISTS idx_jocarsa_nodos_padre
            ON jocarsa_nodos(padre_id)
        )SQL");

        ejecutar(R"SQL(
            CREATE INDEX IF NOT EXISTS idx_jocarsa_registros_coleccion
            ON jocarsa_registros(coleccion)
        )SQL");
    }

    // Serialización recursiva de cualquier valor JSON.
    sqlite3_int64 insertarNodo(
        sqlite3_int64 registro,
        const json& dato,
        sqlite3_int64 padre = 0,
        const std::string& clave = "",
        int posicion = -1
    ) {
        std::string tipo;
        std::string valor;

        if (dato.is_object())
            tipo = "objeto";
        else if (dato.is_array())
            tipo = "array";
        else {
            tipo = "valor";
            valor = dato.dump();
        }

        Statement s(db, R"SQL(
            INSERT INTO jocarsa_nodos
            (registro_id, padre_id, clave, posicion, tipo, valor)
            VALUES (?, ?, ?, ?, ?, ?)
        )SQL");

        s.entero(1, registro);

        if (padre == 0)
            s.nulo(2);
        else
            s.entero(2, padre);

        if (padre == 0 || posicion >= 0)
            s.nulo(3);
        else
            s.texto(3, clave);

        if (posicion >= 0)
            s.entero(4, posicion);
        else
            s.nulo(4);

        s.texto(5, tipo);

        if (tipo == "valor")
            s.texto(6, valor);
        else
            s.nulo(6);

        s.paso();

        sqlite3_int64 id = sqlite3_last_insert_rowid(db);

        if (dato.is_object()) {
            for (auto it = dato.begin(); it != dato.end(); ++it) {
                insertarNodo(
                    registro, it.value(), id, it.key()
                );
            }
        }
        else if (dato.is_array()) {
            for (size_t i = 0; i < dato.size(); ++i) {
                insertarNodo(
                    registro, dato[i], id, "",
                    static_cast<int>(i)
                );
            }
        }

        return id;
    }

    json reconstruirNodo(sqlite3_int64 id) {
        std::string tipo;
        std::string valor;

        {
            Statement s(db,
                "SELECT tipo, valor FROM jocarsa_nodos WHERE id=?"
            );
            s.entero(1, id);

            if (s.paso() != SQLITE_ROW)
                throw std::runtime_error("Nodo inexistente");

            tipo = reinterpret_cast<const char*>(
                sqlite3_column_text(s.stmt, 0)
            );

            if (tipo == "valor") {
                const unsigned char* texto =
                    sqlite3_column_text(s.stmt, 1);

                valor = texto
                    ? reinterpret_cast<const char*>(texto)
                    : "null";
            }
        }

        if (tipo == "valor")
            return json::parse(valor);

        json resultado;

        if (tipo == "objeto")
            resultado = json::object();
        else if (tipo == "array")
            resultado = json::array();
        else
            throw std::runtime_error("Tipo de nodo desconocido");

        struct Hijo {
            sqlite3_int64 id;
            std::string clave;
        };

        std::vector<Hijo> hijos;

        {
            Statement s(db, R"SQL(
                SELECT id, clave
                FROM jocarsa_nodos
                WHERE padre_id=?
                ORDER BY posicion, id
            )SQL");

            s.entero(1, id);

            while (s.paso() == SQLITE_ROW) {
                Hijo hijo;
                hijo.id = sqlite3_column_int64(s.stmt, 0);

                const unsigned char* texto =
                    sqlite3_column_text(s.stmt, 1);

                if (texto)
                    hijo.clave =
                        reinterpret_cast<const char*>(texto);

                hijos.push_back(hijo);
            }
        }

        // No mantener una consulta activa mientras
        // reconstruimos recursivamente los descendientes.
        for (const auto& hijo : hijos) {
            json elemento = reconstruirNodo(hijo.id);

            if (tipo == "array")
                resultado.push_back(elemento);
            else
                resultado[hijo.clave] = elemento;
        }

        return resultado;
    }

    bool existe(const std::string& coleccion,
                sqlite3_int64 id) {
        Statement s(db, R"SQL(
            SELECT 1 FROM jocarsa_registros
            WHERE coleccion=? AND id=?
        )SQL");

        s.texto(1, coleccion);
        s.entero(2, id);

        return s.paso() == SQLITE_ROW;
    }

    sqlite3_int64 guardarInterno(
        const std::string& coleccion,
        const json& datos
    ) {
        Statement s(db,
            "INSERT INTO jocarsa_registros(coleccion) VALUES (?)"
        );
        s.texto(1, coleccion);
        s.paso();

        sqlite3_int64 id = sqlite3_last_insert_rowid(db);
        insertarNodo(id, datos);

        return id;
    }

    json obtenerInterno(
        const std::string& coleccion,
        sqlite3_int64 id
    ) {
        if (!existe(coleccion, id))
            return nullptr;

        Statement s(db, R"SQL(
            SELECT id FROM jocarsa_nodos
            WHERE registro_id=? AND padre_id IS NULL
        )SQL");

        s.entero(1, id);

        if (s.paso() != SQLITE_ROW)
            return nullptr;

        sqlite3_int64 nodo = sqlite3_column_int64(s.stmt, 0);
        return reconstruirNodo(nodo);
    }

    std::vector<sqlite3_int64> listarIds(
        const std::string& coleccion
    ) {
        Statement s(db, R"SQL(
            SELECT id FROM jocarsa_registros
            WHERE coleccion=?
            ORDER BY id
        )SQL");

        s.texto(1, coleccion);

        std::vector<sqlite3_int64> ids;

        while (s.paso() == SQLITE_ROW)
            ids.push_back(sqlite3_column_int64(s.stmt, 0));

        return ids;
    }

    void comenzar() {
        ejecutar("BEGIN IMMEDIATE");
    }

    void confirmar() {
        ejecutar("COMMIT");
    }

    void revertir() {
        ejecutar("ROLLBACK");
    }

public:
    explicit JocarsaORM(const std::string& archivo) {
        int rc = sqlite3_open(archivo.c_str(), &db);

        if (rc != SQLITE_OK) {
            std::string error = db
                ? sqlite3_errmsg(db)
                : "No se pudo abrir SQLite";

            if (db) sqlite3_close(db);
            db = nullptr;

            throw std::runtime_error(error);
        }

        sqlite3_busy_timeout(db, 5000);
        iniciar();
    }

    ~JocarsaORM() {
        if (db)
            sqlite3_close(db);
    }

    JocarsaORM(const JocarsaORM&) = delete;
    JocarsaORM& operator=(const JocarsaORM&) = delete;

    class Coleccion {
    private:
        JocarsaORM& orm;
        std::string nombre;

    public:
        Coleccion(JocarsaORM& orm, std::string nombre)
            : orm(orm), nombre(std::move(nombre)) {}

        sqlite3_int64 guardar(const json& datos) {
            orm.comenzar();

            try {
                auto id = orm.guardarInterno(nombre, datos);
                orm.confirmar();
                return id;
            }
            catch (...) {
                orm.revertir();
                throw;
            }
        }

        json obtener(sqlite3_int64 id) {
            return orm.obtenerInterno(nombre, id);
        }

        json todos() {
            json resultado = json::array();

            for (auto id : orm.listarIds(nombre)) {
                resultado.push_back(
                    orm.obtenerInterno(nombre, id)
                );
            }

            return resultado;
        }

        json buscar(const std::string& campo,
                    const json& valor) {
            json resultado = json::array();

            for (auto id : orm.listarIds(nombre)) {
                json dato = orm.obtenerInterno(nombre, id);

                if (dato.is_object() &&
                    dato.contains(campo) &&
                    dato[campo] == valor) {
                    resultado.push_back(dato);
                }
            }

            return resultado;
        }

        bool actualizar(sqlite3_int64 id,
                        const json& cambios) {
            if (!orm.existe(nombre, id))
                return false;

            json actual = orm.obtenerInterno(nombre, id);

            if (actual.is_object() && cambios.is_object()) {
                // Actualización parcial de campos superiores.
                for (auto it = cambios.begin();
                     it != cambios.end(); ++it) {
                    actual[it.key()] = it.value();
                }
            }
            else {
                // Sustitución completa para otros tipos.
                actual = cambios;
            }

            orm.comenzar();

            try {
                orm.ejecutar(
                    "DELETE FROM jocarsa_nodos "
                    "WHERE registro_id=" + std::to_string(id)
                );

                orm.insertarNodo(id, actual);
                orm.confirmar();
                return true;
            }
            catch (...) {
                orm.revertir();
                throw;
            }
        }

        bool eliminar(sqlite3_int64 id) {
            if (!orm.existe(nombre, id))
                return false;

            orm.comenzar();

            try {
                Statement s(orm.db, R"SQL(
                    DELETE FROM jocarsa_registros
                    WHERE coleccion=? AND id=?
                )SQL");

                s.texto(1, nombre);
                s.entero(2, id);
                s.paso();

                orm.confirmar();
                return true;
            }
            catch (...) {
                orm.revertir();
                throw;
            }
        }

        size_t contar() {
            Statement s(orm.db, R"SQL(
                SELECT COUNT(*) FROM jocarsa_registros
                WHERE coleccion=?
            )SQL");

            s.texto(1, nombre);
            s.paso();

            return static_cast<size_t>(
                sqlite3_column_int64(s.stmt, 0)
            );
        }

        size_t importarCarpeta(const std::string& carpeta) {
            if (!fs::exists(carpeta))
                throw std::runtime_error(
                    "No existe la carpeta: " + carpeta
                );

            std::vector<fs::path> archivos;

            for (const auto& entrada :
                 fs::directory_iterator(carpeta)) {
                if (entrada.is_regular_file() &&
                    entrada.path().extension() == ".json") {
                    archivos.push_back(entrada.path());
                }
            }

            std::sort(archivos.begin(), archivos.end());

            size_t total = 0;

            for (const auto& archivo : archivos) {
                try {
                    std::ifstream f(archivo);

                    if (!f)
                        throw std::runtime_error(
                            "No se puede abrir el archivo"
                        );

                    json datos;
                    f >> datos;

                    // Una transacción por archivo.
                    orm.comenzar();

                    try {
                        if (datos.is_array()) {
                            for (const auto& elemento : datos) {
                                orm.guardarInterno(nombre, elemento);
                                ++total;
                            }
                        }
                        else {
                            orm.guardarInterno(nombre, datos);
                            ++total;
                        }

                        orm.confirmar();
                    }
                    catch (...) {
                        orm.revertir();
                        throw;
                    }

                    std::cout << "OK: "
                              << archivo.filename().string()
                              << "\n";
                }
                catch (const std::exception& e) {
                    std::cerr << "ERROR: "
                              << archivo.filename().string()
                              << " - " << e.what() << "\n";
                }
            }

            return total;
        }

        size_t exportarCarpeta(const std::string& carpeta) {
            fs::create_directories(carpeta);

            auto ids = orm.listarIds(nombre);

            for (auto id : ids) {
                fs::path ruta = fs::path(carpeta) /
                    (nombre + "_" + std::to_string(id) + ".json");

                std::ofstream f(ruta);

                if (!f)
                    throw std::runtime_error(
                        "No se puede escribir: " + ruta.string()
                    );

                f << orm.obtenerInterno(nombre, id).dump(4);
            }

            return ids.size();
        }
    };

    Coleccion operator[](const std::string& nombre) {
        return Coleccion(*this, nombre);
    }
};

#endif
