# Changelog — Crimson ERP

Registro de cambios y evolución del proyecto **Crimson ERP**.

El proyecto se encuentra actualmente en una fase inicial de desarrollo y construcción de su arquitectura base.

---

## [0.3] — 2026-09-17

### Persistencia SQLite

* Incorporado sistema de persistencia basado en SQLite.
* La base de datos se almacena en `data/crimson.sqlite`.
* La estructura de la base de datos se define declarativamente mediante `instalacion/modelodedatos.md`.
* Separación entre definición del modelo, instalación inicial y migraciones posteriores.

### Modelo de datos

* Creado `instalacion/modelodedatos.md` como fuente de verdad del esquema.
* Las tablas se definen mediante una línea con el nombre de la entidad.
* Los campos se definen mediante líneas iniciadas por `-`.
* Los campos sin tipo explícito utilizan `TEXT`.
* Se permite indicar tipos SQLite mediante la sintaxis `campo:TIPO`.
* Tipos admitidos:
  * `TEXT`
  * `INTEGER`
  * `REAL`
  * `BLOB`
  * `NUMERIC`
* Validación de tablas duplicadas.
* Validación de campos duplicados.
* Validación de tipos no permitidos.
* Validación de referencias a tablas inexistentes.

### Identificadores automáticos

* Todas las tablas reciben automáticamente un primer campo llamado `Identificador`.
* `Identificador` se crea como `INTEGER PRIMARY KEY AUTOINCREMENT`.
* El campo no debe declararse manualmente en `modelodedatos.md`.
* El instalador detecta y rechaza una declaración manual de `Identificador`.

### Relaciones entre tablas

* Incorporada declaración de claves foráneas directamente desde el modelo.
* Nueva sintaxis:

  `-cliente FK Clientes`

* Una FK referencia automáticamente `Identificador` de la tabla indicada.
* Los campos FK sin tipo explícito se crean como `INTEGER`.
* También se admite tipo explícito, por ejemplo:

  `-cliente:INTEGER FK Clientes`

* Activación de `PRAGMA foreign_keys = ON`.
* Validación de integridad mediante `PRAGMA foreign_key_check`.

### Modelo inicial

Definidas las entidades:

* `Clientes`
  * nombre
  * apellidos
  * fecha_nacimiento
  * dni
  * email
* `Productos`
  * nombre
  * precio
  * dimensiones
  * descripcion
* `Pedidos`
  * fecha
  * numero
  * cliente → `Clientes.Identificador`
  * producto → `Productos.Identificador`

### Instalador

* Desarrollado `instalacion/instalar.php`.
* Lectura automática de `modelodedatos.md`.
* Creación automática de la carpeta `data` cuando no existe.
* Creación de `data/crimson.sqlite`.
* Creación automática de todas las tablas definidas en el modelo.
* Creación automática de `Identificador` en cada tabla.
* Creación de relaciones FK.
* Uso de transacciones durante la instalación.
* Rollback en caso de error.
* Comprobación de la extensión PHP `pdo_sqlite`.
* El instalador no sobreescribe una base existente y deriva las actualizaciones a `migrar.php`.

### Información de instalación

* Ampliada la interfaz del instalador para informar detalladamente del resultado.
* Muestra el nombre de cada tabla creada.
* Muestra todos los campos de cada tabla.
* Muestra el tipo SQLite de cada campo.
* Identifica la clave primaria.
* Identifica las claves foráneas.
* Muestra la tabla y el campo referenciados.
* Informa de la ubicación final de la base de datos.
* Informa del número de tablas creadas.
* Presentación diferenciada de instalación correcta y errores.

### Migraciones

* Desarrollado `instalacion/migrar.php`.
* Comparación automática entre `modelodedatos.md` y el esquema SQLite existente.
* Detección de:
  * tablas nuevas;
  * tablas eliminadas;
  * campos nuevos;
  * campos eliminados;
  * cambios de tipo;
  * claves foráneas nuevas;
  * cambios en claves foráneas;
  * tablas antiguas sin el campo `Identificador`.
* Clasificación de operaciones como seguras o destructivas.
* Presentación previa de todos los cambios antes de ejecutar la migración.
* Confirmación explícita para cambios destructivos.

### Seguridad de las migraciones

* Creación automática de una copia de seguridad antes de modificar el esquema.
* Formato de copia:

  `crimson.sqlite.backup-YYYYMMDD-HHMMSS`

* Uso de transacciones durante la migración.
* Rollback ante errores.
* Las modificaciones no soportadas directamente por `ALTER TABLE` se realizan reconstruyendo la tabla.
* Durante la reconstrucción se conservan los campos compatibles.
* Se conserva `Identificador` cuando ya existe.
* Si una tabla antigua no tiene `Identificador`, SQLite genera los nuevos identificadores automáticamente.
* Los cambios de tipo utilizan conversión mediante `CAST`.
* Verificación final del esquema después de migrar.
* Comprobación final de integridad de claves foráneas.

### Estado de persistencia

* Crimson ERP dispone ahora de una primera capa de persistencia real.
* El esquema queda desacoplado del código PHP mediante un modelo de datos declarativo.
* El sistema puede instalar una base desde cero y evolucionar posteriormente su estructura mediante migraciones.

---

## [0.2] — 2026-09-17

### Añadimos changelog

* Creado `changelog.md` para registrar la evolución del proyecto.

---

## [0.1] — 2026-09-17

### Estado inicial

Primera versión funcional de **Crimson ERP**.

Esta versión establece la estructura básica sobre la que se desarrollarán los diferentes módulos y funcionalidades del ERP.

### Arquitectura

* Creada la estructura inicial del proyecto.
* Separación del sistema en:
  * `api/`
  * `modulos/`
  * `nucleo/`
* Creado el núcleo común de la aplicación.
* Separación de componentes HTML reutilizables.
* Separación de estilos CSS.
* Separación de JavaScript.
* Preparación de la carpeta `modulos/` para futuros módulos del ERP.

### Interfaz

* Creada la estructura principal de la aplicación:
  * Cabecera.
  * Menú lateral.
  * Área principal de trabajo.
  * Pie de página.
* Cabecera con:
  * Identidad corporativa de jocarsa | crimson.
  * Herramientas principales.
  * Identificación del usuario.
  * Acceso a opciones.
* Pie de página con:
  * Identidad de la aplicación.
  * Estado/información del sistema.
  * Usuario y rol.
* Altura fija para cabecera y pie de página.
* Área central adaptable automáticamente al espacio disponible.
* Menú lateral integrado con el mismo lenguaje visual de la aplicación.
* Definición de variables CSS globales para color, márgenes y dimensiones principales.
* Estilo base para tablas de datos.

### Componentes

Implementados los primeros componentes reutilizables:

* `cabecera.html`
* `menu.html`
* `clientes.html`
* `principal.html`
* `piedepagina.html`

### Carga dinámica

* Implementado sistema de inclusión dinámica mediante el atributo `data-include`.
* Creado `incluir.js` como cargador de componentes.
* Los componentes se obtienen mediante `fetch()`.
* Ejecución automática de los scripts contenidos dentro de los componentes cargados.
* Gestión básica de errores cuando un componente no puede cargarse.

### API

* Creada API inicial en `api/api.php`.
* Implementado endpoint lógico `menu`.
* Implementado endpoint lógico `tabla`.
* El menú principal puede obtener sus elementos dinámicamente desde la API.
* Implementados datos JSON iniciales para pruebas.

### Datos

* Creado conjunto inicial de productos de demostración.
* Cada producto dispone actualmente de:
  * ID.
  * Nombre.
  * Categoría.
  * Precio.
  * Stock.
* Generación dinámica de tablas HTML a partir de objetos JSON.
* Las columnas se generan automáticamente utilizando las claves de los objetos.
* Las filas se generan automáticamente a partir de los registros recibidos.

### Estado de persistencia

* Todavía no existe una base de datos persistente.
* Los datos actuales son datos estáticos de demostración proporcionados por la API.
* La persistencia definitiva queda pendiente para versiones posteriores.

### Estado de la versión

**Versión:** `0.1`

**Fase:** prototipo funcional / arquitectura inicial.

La versión 0.1 proporciona la base estructural, visual y técnica necesaria para comenzar a implementar los módulos funcionales de Crimson ERP.
