
#include <iostream>
#include "JocarsaORM.h"

int main() {
    try {
        JocarsaORM orm("curriculums.db");

        json curriculum = {
            {"nombre", "Ana García"},
            {"edad", 28},
            {"activo", true},
            {"habilidades", {
                "Python", "C++", "SQL"
            }},
            {"direccion", {
                {"ciudad", "Valencia"},
                {"pais", "España"}
            }},
            {"experiencia", {
                {
                    {"empresa", "Jocarsa"},
                    {"años", 3}
                },
                {
                    {"empresa", "Empresa B"},
                    {"años", 2}
                }
            }}
        };

        // GUARDAR
        auto id = orm["curriculum"].guardar(curriculum);
        std::cout << "ID: " << id << "\n";

        // RECUPERAR
        json recuperado = orm["curriculum"].obtener(id);
        std::cout << recuperado.dump(4) << "\n";

        // ACTUALIZAR
        orm["curriculum"].actualizar(id, {
            {"edad", 29},
            {"activo", false}
        });

        // BUSCAR
        json resultados = orm["curriculum"].buscar(
            "nombre", "Ana García"
        );

        std::cout << resultados.dump(4) << "\n";

        // CONTAR
        std::cout << "Total: "
                  << orm["curriculum"].contar() << "\n";

        // IMPORTAR CARPETA
        orm["curriculum"].importarCarpeta("curriculums");

        // EXPORTAR CARPETA
        orm["curriculum"].exportarCarpeta(
            "curriculums_recuperados"
        );

        // ELIMINAR (descomentar si se necesita)
        // orm["curriculum"].eliminar(id);
    }
    catch (const std::exception& e) {
        std::cerr << "ERROR: " << e.what() << "\n";
        return 1;
    }

    return 0;
}
