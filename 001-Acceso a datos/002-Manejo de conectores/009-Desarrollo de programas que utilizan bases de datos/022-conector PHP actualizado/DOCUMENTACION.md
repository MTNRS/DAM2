# JocarsaBBDD — documentación completa

## 1. Descripción

JocarsaBBDD es un SGBD didáctico escrito en C++20. Incluye un intérprete de comandos en terminal y un conector nativo para Python. Su objetivo es mostrar de forma transparente cómo pueden construirse las piezas fundamentales de un gestor de bases de datos: almacenamiento físico, esquema, índices, CRUD, consultas, integridad referencial, JOIN y agregaciones.

Esta versión incorpora:

- bases de datos y tablas;
- registros de tamaño fijo;
- identificador `id` automático;
- borrado lógico mediante `activo`;
- índice físico por `id`;
- DDL y DML en español;
- DQL con filtros y operadores lógicos;
- clave primaria implícita;
- claves foráneas con integridad referencial;
- `JOIN`/`UNIR` interno;
- `CONTAR`, `SUMA`, `MEDIA`, `MINIMO` y `MAXIMO`;
- `AGRUPAR POR`;
- `TENIENDO`;
- ordenación, límite, alias y valores distintos;
- módulo nativo Python.

---

## 2. Archivos del proyecto

- `JocarsaBBDD.hpp`: interfaz pública del motor.
- `JocarsaBBDD.cpp`: implementación del almacenamiento, restricciones y DQL.
- `main.cpp`: terminal interactiva y parser de comandos DDL/DML.
- `conector_python.cpp`: extensión nativa CPython.
- `Makefile`: compilación e instalación.
- `config.json`: configuración del directorio de datos y tamaño de registro.
- `PRUEBA_PK_FK_JOIN.txt`: prueba del hito relacional.
- `PRUEBA_AGREGACIONES.txt`: prueba de agregaciones, agrupación y HAVING/TENIENDO.

---

## 3. Compilación

Dependencias habituales en Ubuntu/Debian:
