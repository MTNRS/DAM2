## [0.6] — 2026-09-17

### Identidad visual y cabecera

* Cambiado el color corporativo principal de `mediumseagreen` a `crimson`.
* El color continúa centralizado mediante la variable CSS `--color-principal`.
* Establecida `Ubuntu` como fuente primaria de la interfaz, con `sans-serif` como alternativa.
* Recuperada la identidad corporativa permanente `jocarsa | crimson` en la zona izquierda de la cabecera.
* La zona central de la cabecera muestra la tabla activa, la operación actual y las acciones disponibles.
* La zona derecha mantiene la identidad y opciones del usuario.
* Reducida la altura de la cabecera para conseguir una interfaz más compacta.
* Normalizados los espaciados mediante las variables CSS `--padding` y `--gap`.
* Unificados los paddings de cabecera, navegación, contenido, tablas, formularios y pie de página.

### Formularios

* Añadida separación vertical entre grupos de campos mediante `--gap:10px`.
* La primera columna de los formularios se ajusta automáticamente al texto de campo más ancho.
* La segunda columna utiliza el espacio restante disponible.
* Los campos relacionados mediante clave foránea utilizan ahora `input` con `datalist` en lugar de `select`.
* El usuario puede escribir o seleccionar el valor descriptivo de un registro relacionado.
* Al guardar, el componente CRUD resuelve el valor mostrado y almacena el `Identificador` correspondiente.
* Al actualizar, las claves foráneas almacenadas se muestran nuevamente mediante su representación descriptiva.

### Detección automática de instalación

* Añadido el endpoint `api.php?bloque=estado`.
* El endpoint puede comprobar la existencia de `data/crimson.sqlite` sin necesitar abrir previamente la base de datos.
* Al cargar `index.html`, Crimson consulta automáticamente el estado de instalación.
* Si la base de datos existe, se carga normalmente la aplicación.
* Si la base de datos no existe, el usuario es redirigido automáticamente a `instalacion/instalar.php`.
* La carga de los componentes de la aplicación solamente comienza cuando se ha confirmado que Crimson está instalado.

### Instalador

* El instalador carga automáticamente el contenido de `modelodedatos.md`.
* El modelo de datos se presenta dentro de un `<textarea>` editable antes de realizar la instalación.
* El usuario puede modificar directamente el modelo antes de crear la base de datos.
* Al confirmar la instalación, el contenido del editor se guarda nuevamente en `modelodedatos.md`.
* La base de datos SQLite se genera utilizando el modelo guardado.
* Adaptada la interfaz del instalador a la identidad visual `crimson`.
* Aplicada la fuente Ubuntu y el mismo sistema básico de espaciado utilizado por la aplicación.
* El instalador detecta una instalación existente y evita sobrescribirla.
* Corregida la limpieza en caso de error para impedir que una ejecución del instalador pueda eliminar accidentalmente una base de datos preexistente.

### Flujo de arranque

El arranque de Crimson queda ahora automatizado:

`index.html → api.php?bloque=estado`

Si existe la base de datos:

`estado instalado → incluir.js → componentes → Crimson ERP`

Si no existe:

`estado no instalado → instalar.php → modelodedatos.md → crimson.sqlite → Crimson ERP`

---

# Changelog — Crimson ERP

Registro de cambios y evolución del proyecto **Crimson ERP**.

El proyecto se encuentra actualmente en una fase inicial de desarrollo y construcción de su arquitectura base.

---

## [0.4] — 2026-09-17

### API conectada a SQLite

* Sustituido el menú estático de la API por una consulta real a SQLite.
* El endpoint `menu` obtiene automáticamente las tablas existentes en `data/crimson.sqlite`.
* La consulta excluye las tablas internas de SQLite.
* Las nuevas tablas incorporadas mediante el modelo de datos aparecen automáticamente en el menú sin necesidad de modificar el código PHP o JavaScript.

### Endpoint de tablas

* Actualizado el endpoint `tabla` para trabajar con cualquier tabla de la base de datos.
* Nueva llamada:

  `api/api.php?bloque=tabla&tabla=Clientes`

* El endpoint devuelve:
  * nombre de la tabla;
  * estructura real de columnas;
  * contenido completo de la tabla.
* La estructura se obtiene mediante `PRAGMA table_info`.
* Los registros se obtienen mediante `SELECT *`.
* Eliminados los datos estáticos de productos del endpoint `tabla`.
* Validación previa de que la tabla solicitada existe.
* Se impide consultar mediante el endpoint una tabla SQLite interna.
* Mantenimiento de respuestas JSON con soporte Unicode.
* Captura de excepciones y mensajes de depuración.

### Menú dinámico

* Actualizado `nucleo/componentes/menu.html`.
* El menú continúa obteniendo automáticamente las tablas desde el endpoint `menu`.
* Cada elemento del menú incorpora ahora una acción de carga.
* Al pulsar una tabla se realiza una petición `fetch()` al endpoint `tabla`.
* El nombre de la tabla se envía mediante `encodeURIComponent()`.
* Se evita la navegación normal del enlace mediante `preventDefault()`.

### Visualización dinámica de tablas

* El área principal se actualiza al seleccionar una tabla del menú.
* Se muestra el nombre de la tabla seleccionada.
* Las cabeceras se construyen a partir de la estructura real devuelta por SQLite.
* Ya no se deducen las columnas a partir del primer registro.
* Las tablas sin registros pueden mostrar correctamente sus cabeceras.
* Las filas se construyen dinámicamente utilizando el orden definido por la estructura SQLite.
* Los valores `NULL` se representan como celdas vacías.
* El mismo código permite visualizar `Clientes`, `Productos`, `Pedidos` y cualquier tabla futura.

### Evolución de la arquitectura

El flujo de datos pasa a ser completamente dinámico:

`modelodedatos.md → SQLite → api.php → menu.html → interfaz`

Esto permite que una nueva tabla añadida al modelo, instalada o migrada a SQLite, pueda aparecer en el menú y visualizarse desde la interfaz sin crear código específico para esa entidad.

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
