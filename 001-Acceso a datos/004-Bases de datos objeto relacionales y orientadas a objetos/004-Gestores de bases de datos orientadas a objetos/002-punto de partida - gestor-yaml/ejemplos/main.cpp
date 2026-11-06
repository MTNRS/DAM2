#include "GestorYAML.h"
#include <iostream>
#include <stdexcept>

int main() {
    try {
        GestorYAML db("datos");
        db.crearBaseDatos("empresa");
        db.crearColeccion("empresa", "clientes");
        db.crearColeccion("empresa", "productos");
        db.crearColeccion("empresa", "pedidos");
        db.crearColeccion("instituto", "alumnos");

        // Escritura de objeto complejo con mapas, listas, booleanos y numeros
        YAML::Node ana;
        ana["nombre"] = "Ana Garcia";
        ana["edad"] = 28;
        ana["activo"] = true;
        ana["direccion"]["ciudad"] = "Valencia";
        ana["direccion"]["cp"] = "46001";
        ana["telefonos"].push_back("600111222");
        ana["telefonos"].push_back("600333444");
        ana["habilidades"].push_back("C++");
        ana["habilidades"].push_back("Python");
        YAML::Node proyecto;
        proyecto["nombre"] = "ERP";
        proyecto["horas"] = 120;
        ana["proyectos"].push_back(proyecto);
        const std::string idAna = db.guardar("empresa", "clientes", ana);
        std::cout << "Cliente insertado: " << idAna << "\n";

        // Alternativa: construir el documento leyendo una cadena YAML
        YAML::Node luis = YAML::Load(R"(
nombre: Luis
edad: 35
activo: false
preferencias:
  idioma: es
  temas: [C++, SQL]
)");
        const std::string idLuis = db.guardar("empresa", "clientes", luis);

        YAML::Node producto;
        producto["nombre"] = "Portatil";
        producto["precio"] = 899.99;
        producto["stock"] = 12;
        const std::string idProducto = db.guardar("empresa", "productos", producto);

        // Relacion documental mediante identificadores
        YAML::Node pedido;
        pedido["cliente_id"] = idAna;
        YAML::Node linea;
        linea["producto_id"] = idProducto;
        linea["cantidad"] = 2;
        pedido["lineas"].push_back(linea);
        pedido["estado"] = "pendiente";
        const std::string idPedido = db.guardar("empresa", "pedidos", pedido);

        YAML::Node alumno;
        alumno["nombre"] = "Marta";
        alumno["curso"] = "DAM";
        alumno["notas"]["programacion"] = 9.5;
        alumno["notas"]["bases_datos"] = 8;
        db.guardar("instituto", "alumnos", alumno);

        // Lectura por ID y acceso tipado
        YAML::Node recuperado = db.leer("empresa", "clientes", idAna);
        if (recuperado.IsDefined()) {
            std::cout << "Nombre: " << recuperado["nombre"].as<std::string>() << "\n";
            std::cout << "Edad: " << recuperado["edad"].as<int>() << "\n";
            std::cout << "Ciudad: " << recuperado["direccion"]["ciudad"].as<std::string>() << "\n";
            for (const auto& telefono : recuperado["telefonos"])
                std::cout << "Telefono: " << telefono.as<std::string>() << "\n";
        }

        // Listado y lectura de todos los documentos
        for (const auto& doc : db.listar("empresa", "clientes"))
            std::cout << "Documento:\n" << YAML::Dump(doc) << "\n";

        // Filtro por igualdad y campos anidados
        YAML::Node condiciones;
        condiciones["activo"] = true;
        condiciones["direccion"]["ciudad"] = "Valencia";
        for (const auto& doc : db.filtrar("empresa", "clientes", condiciones))
            std::cout << "Encontrado: " << doc["nombre"].as<std::string>() << "\n";

        // Actualizar sin perder el ID
        recuperado["edad"] = 29;
        recuperado["direccion"]["ciudad"] = "Madrid";
        recuperado["telefonos"].push_back("611222333");
        if (db.actualizar("empresa", "clientes", idAna, recuperado))
            std::cout << "Cliente actualizado\n";

        // Metadatos
        std::cout << "Total clientes: " << db.contar("empresa", "clientes") << "\n";
        for (const auto& nombre : db.basesDeDatos()) {
            std::cout << "Base de datos: " << nombre << "\n";
            for (const auto& col : db.colecciones(nombre))
                std::cout << "  Coleccion: " << col << "\n";
        }

        // Eliminacion de ejemplo (mantener Ana y pedido)
        if (db.eliminar("empresa", "clientes", idLuis))
            std::cout << "Luis eliminado\n";
        std::cout << "Pedido almacenado: " << idPedido << "\n";
        std::cout << "Producto almacenado: " << idProducto << "\n";
    } catch (const std::exception& e) {
        std::cerr << "Error: " << e.what() << "\n";
        return 1;
    }
}
