# Changelog — Crimson ERP

Registro de cambios y evolución del proyecto **Crimson ERP**.

El proyecto se encuentra actualmente en una fase inicial de desarrollo y construcción de su arquitectura base.

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
