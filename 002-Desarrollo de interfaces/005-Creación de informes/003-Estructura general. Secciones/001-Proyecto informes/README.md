# Proyecto de informes PHP

Proyecto completo basado en el motor de clases proporcionado.

## Archivos

- `motor-informes.php`: clases del motor (Documento, Pagina, Seccion, Contenedor y componentes).
- `Informe.php`: fachada pública para generar informes.
- `estilos.css`: estilos de pantalla e impresión A4.
- `ejemplo.php`: informe completo de cuatro páginas.
- `index.php`: entrada del proyecto.

## Ejecutar

Requiere PHP 8.0 o superior. Desde esta carpeta:

```bash
php -S localhost:8000
```

Abre http://localhost:8000 en el navegador.

## API de Informe

`new Informe($titulo)` crea el informe.

Estructura: `nuevaPagina()`, `nuevaSeccion($titulo = "")`, `nuevoContenedor()`, `agregar(Componente $componente)`.

Texto: `titulo($texto)`, `subtitulo($texto)`, `encabezado($texto, $nivel = 3)`, `parrafo($texto)`.

Datos: `tabla($columnas, $filas)`.

Gráficas: `barras($titulo, $etiquetas, $valores, $ancho = 600, $alto = 300)`, `lineas(...)`, `circular(...)`.

Indicadores: `kpi($etiqueta, $valor, $descripcion = "")`, `contador(...)`, `porcentaje(...)`.

Salida: `render()` devuelve HTML; `obtenerDocumento()` devuelve el objeto de dominio.

Los métodos de construcción devuelven `$this` para permitir encadenamiento. Las páginas, secciones y contenedores se crean automáticamente cuando sea necesario.

## Ejemplo mínimo

```php
require_once 'motor-informes.php';
require_once 'Informe.php';
$informe = new Informe('Mi informe');
$informe->nuevaPagina()->nuevaSeccion('Resumen')->titulo('Resultados')->parrafo('Contenido de prueba');
echo $informe->render();
```

Para obtener el diseño completo, carga `estilos.css` desde el HTML que contenga el resultado.
