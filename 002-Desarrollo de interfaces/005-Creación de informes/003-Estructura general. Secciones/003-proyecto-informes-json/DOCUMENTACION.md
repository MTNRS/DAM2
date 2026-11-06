# Motor de informes JSON — Documentación completa

## 1. Objetivo y arquitectura

Una aplicación externa envía un **objeto JSON**. `ProcesadorJSON` valida el documento y crea instancias de la fachada `Informe`, que delega en `Documento`, `Pagina`, `Seccion`, `Contenedor` y las subclases de `Componente`. El resultado es HTML con gráficos SVG. No requiere base de datos ni librerías de terceros.

Flujo: JSON → validación → objetos PHP → `render()` → HTML/CSS → navegador/impresión A4.

## 2. Requisitos

PHP **8.1 o superior** con JSON. Servidor HTTP PHP/Apache/Nginx o ejecución por CLI. El motor usa `match`, propiedades tipadas y `array_is_list`.

## 3. Estructura del proyecto

- `motor-informes.php`: clases base, renderizadores HTML/SVG.
- `Informe.php`: fachada de alto nivel.
- `ProcesadorJSON.php`: intérprete, validación y construcción.
- `plantilla.php`: HTML completo, título, enlace a CSS.
- `api.php`: endpoint HTTP POST.
- `index.php`: demostración navegable.
- `generar.php`: conversor JSON→HTML por CLI.
- `estilos.css`: diseño, adaptación móvil e impresión A4.
- `ejemplo-completo.json`: caso de uso con 4 páginas y todos los componentes.
- `DOCUMENTACION.md`: este documento.

## 4. Esquema del JSON

El JSON de entrada tiene la estructura exacta:

```json
{
  "version": 1,
  "titulo": "Informe de ejemplo",
  "paginas": [
    {
      "secciones": [
        {
          "titulo": "Resumen",
          "contenedores": [
            {
              "componentes": [
                { "tipo": "parrafo", "texto": "Contenido de prueba" }
              ]
            }
          ]
        }
      ]
    }
  ]
}
```

### Reglas de jerarquía

- `version`: opcional, número entero `1`; si se omite se interpreta como `1`.
- `titulo`: obligatorio, cadena; es el título global del documento.
- `paginas`: obligatorio, array de **1 a 100** objetos página.
- Cada página: `secciones` obligatorio, array (puede estar vacío).
- Cada sección: `titulo` opcional, cadena; `contenedores` obligatorio, array.
- Cada contenedor: `componentes` obligatorio, array.
- Cada componente: `tipo` obligatorio, uno de los 11 tipos de la tabla siguiente.
- Los objetos rechazan propiedades desconocidas. No hay evaluación de código, plantillas ni HTML arbitrario.
- La numeración de páginas es automática y sigue el orden de entrada.
- Se conserva el orden de secciones, contenedores y componentes.

## 5. Catálogo completo de componentes

| tipo | Campos obligatorios | Campos opcionales | Restricciones |
|---|---|---|---|
| `titulo` | `texto` (cadena) | — | `<h1>` |
| `subtitulo` | `texto` (cadena) | — | `<h2>` |
| `encabezado` | `texto` (cadena) | `nivel` entero, por defecto 3 | nivel 3–6 |
| `parrafo` | `texto` (cadena) | — | Saltos de línea respetados |
| `tabla` | `columnas` (array cadenas), `filas` (array de arrays) | — | Cada fila tiene exactamente tantas celdas como columnas; celdas escalares o null |
| `barras` | `titulo`, `etiquetas`, `valores` | `ancho`=600, `alto`=300 | etiquetas cadena, valores numéricos, misma longitud, hasta 50 puntos |
| `lineas` | `titulo`, `etiquetas`, `valores` | `ancho`=600, `alto`=300 | iguales reglas que barras |
| `circular` | `titulo`, `etiquetas`, `valores` | `ancho`=600, `alto`=300 | valores no negativos; 0 total muestra «Sin datos» |
| `kpi` | `etiqueta` (cadena), `valor` (cadena o número) | `descripcion` (cadena) | Muestra valor destacado |
| `contador` | `etiqueta` (cadena), `valor` (entero) | `descripcion` (cadena) | Muestra contador |
| `porcentaje` | `etiqueta` (cadena), `valor` (número) | `descripcion` (cadena) | 0–100, barra de progreso |

**Dimensiones de gráficas:** ancho entero entre 300 y 1600; alto entero entre 200 y 1200. Las gráficas se generan en SVG en el servidor. El tamaño visual se adapta al ancho disponible mediante CSS.

### Ejemplos individuales

```json
{"tipo":"titulo","texto":"Resultados"}
{"tipo":"subtitulo","texto":"Ejercicio 2026"}
{"tipo":"encabezado","texto":"Ventas","nivel":4}
{"tipo":"parrafo","texto":"Primera línea\nSegunda línea"}
{"tipo":"tabla","columnas":["Mes","Ventas"],"filas":[["Enero",1200],["Febrero",1500]]}
{"tipo":"barras","titulo":"Ventas","etiquetas":["Ene","Feb"],"valores":[12,18],"ancho":600,"alto":300}
{"tipo":"lineas","titulo":"Clientes","etiquetas":["Ene","Feb"],"valores":[8,14]}
{"tipo":"circular","titulo":"Cuotas","etiquetas":["A","B"],"valores":[70,30]}
{"tipo":"kpi","etiqueta":"Ingresos","valor":"15.000 €","descripcion":"Acumulado"}
{"tipo":"contador","etiqueta":"Pedidos","valor":240,"descripcion":"Total"}
{"tipo":"porcentaje","etiqueta":"Cumplimiento","valor":87.5,"descripcion":"Objetivo anual"}
```

## 6. Endpoint HTTP

`POST /api.php`, cabecera `Content-Type: application/json`, cuerpo JSON en UTF-8. Respuesta exitosa: **200** `text/html; charset=utf-8` con documento HTML completo. No devuelve JSON en éxito. CSS enlazado desde `/estilos.css` (la API debe estar alojada en la raíz del servidor o adaptar esa ruta).

```bash
curl -sS -X POST http://localhost:8000/api.php \
  -H 'Content-Type: application/json' \
  --data-binary @ejemplo-completo.json \
  -o informe.html
```

Abre `informe.html` desde el servidor HTTP para que se cargue `/estilos.css`; si lo abres como archivo local, el CSS absoluto puede no resolverse. Puedes imprimirlo desde el navegador en A4.

### Errores

- `400` JSON mal formado, versión incompatible, estructura incorrecta, propiedades no reconocidas, datos inválidos.
- `405` método distinto de POST; incluye `Allow: POST`.
- `413` cuerpo mayor de 1 MiB.
- `415` `Content-Type` distinto de `application/json`.
- `500` error interno inesperado, sin exponer detalles internos.

Ejemplo de error: `{"error":"paginas[0].secciones[0].contenedores[0].componentes[0]: tipo desconocido: mapa"}`.

## 7. Ejecución local

Desde la carpeta del proyecto:

```bash
php -S localhost:8000
```

- `http://localhost:8000/` presenta el informe completo de ejemplo.
- `http://localhost:8000/api.php` acepta documentos por POST.
- `http://localhost:8000/estilos.css` sirve los estilos.

## 8. Generación por línea de comandos

```bash
php generar.php ejemplo-completo.json informe.html
```

Si se omite el nombre de salida, el HTML se escribe por stdout. La plantilla HTML usa `/estilos.css`; para documentos HTML independientes, adapta esa referencia a una hoja de estilos embebida o una URL accesible.

## 9. Integración con otras aplicaciones

Cualquier cliente capaz de enviar HTTP JSON puede consumir el endpoint: PHP, Python, JavaScript, Java, C#, etc. Ejemplo Python:

```python
import json
import urllib.request

with open('ejemplo-completo.json', encoding='utf-8') as archivo:
    documento = json.load(archivo)

peticion = urllib.request.Request(
    'http://localhost:8000/api.php',
    data=json.dumps(documento, ensure_ascii=False).encode('utf-8'),
    headers={'Content-Type': 'application/json; charset=utf-8'},
    method='POST'
)

with urllib.request.urlopen(peticion) as respuesta:
    html = respuesta.read().decode('utf-8')

with open('informe.html', 'w', encoding='utf-8') as archivo:
    archivo.write(html)
```

## 10. Seguridad y límites

El texto y los datos de las tablas se escapan con `htmlspecialchars`; no se ejecuta HTML procedente del JSON. Los gráficos SVG también escapan etiquetas. Hay límites de 1 MiB por petición, 100 páginas y 50 puntos por gráfica. El endpoint incluye cabeceras `nosniff`, `no-store` y CSP restrictiva. **No incluye autenticación, autorización, límites de tasa, protección ante consumo intensivo ni CORS**: antes de publicarlo en Internet, sitúalo tras autenticación y límites de peticiones del servidor o proxy. Si se van a generar informes confidenciales, utiliza HTTPS. La salida es HTML imprimible; **no genera PDF de forma nativa**.

## 11. Personalización y extensión

- Estilos: modificar `estilos.css` (colores, tipografías, tamaños, saltos de página).
- Nuevos componentes: crear una subclase de `Componente`, implementar `render()`, añadir un caso a `ProcesadorJSON::componente()` y actualizar el catálogo.
- Plantilla: modificar `plantilla.php` para añadir encabezados globales, metadatos o CSS embebido.
- Salida PDF: conectar un conversor HTML→PDF en una capa adicional, no implementada en esta versión.
- Datos dinámicos: generarlos en la aplicación externa y enviarlos como valores JSON. El motor no consulta bases de datos ni resuelve expresiones.

## 12. Ejemplo integral

El archivo `ejemplo-completo.json` demuestra: 4 páginas, secciones, contenedores, título, subtítulo, encabezado, párrafo, tabla, barras, líneas, circular, KPI, contador y porcentaje. Puedes usarlo directamente con el endpoint, el CLI o `index.php`.

## 13. Limitaciones actuales

No hay imágenes, plantillas condicionales, cálculos, consultas a bases de datos, fórmulas, paginación automática del contenido, elección de orientación por página, archivos adjuntos ni exportación PDF. El diseño A4 tiene una altura fija al imprimir: contenido excesivo puede quedar cortado, por lo que el emisor debe distribuirlo en páginas. Las gráficas de barras y líneas usan una sola serie. El motor de gráficos heredado presenta etiquetas simples y puede solaparlas con demasiados puntos.
