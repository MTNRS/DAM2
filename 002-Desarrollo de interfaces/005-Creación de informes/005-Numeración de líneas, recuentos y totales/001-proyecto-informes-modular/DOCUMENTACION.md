# Motor de informes JSON — documentación

## Instalación

Requiere PHP 8.1 o superior. Copia todos los archivos en una carpeta servida por PHP. `index.php` muestra el ejemplo. La plantilla usa `href="estilos.css"` **relativo**; no accede a `/estilos.css`.

```bash
php -S localhost:8000
curl -X POST http://localhost:8000/api.php -H 'Content-Type: application/json' --data-binary @ejemplo-completo.json -o informe.html
php generar.php ejemplo-completo.json informe.html
```

El archivo HTML descargado por curl debe estar junto a `estilos.css`, ya que el CSS es relativo a la URL del HTML. El endpoint no incorpora autenticación: protégelo antes de exponerlo públicamente.

## Contrato general

```json
{
  "version": 1,
  "titulo": "Informe",
  "datos": {"empresa": "Ejemplo S.L."},
  "paginas": [{"secciones": [{"titulo": "Sección", "contenedores": [{"componentes": [{"tipo": "parrafo", "texto": "Hola"}]}]}]}]
}
```

`datos` es opcional. Los componentes se representan en orden de aparición. Hasta 100 páginas; el endpoint limita el JSON a 1 MiB. Los valores de `origen` son rutas con puntos dentro de `datos` (por ejemplo `ventas` o `departamento.ventas`). El motor no se conecta directamente a bases de datos: la aplicación emisora debe suministrar los registros en `datos`.

## Textos (16 tipos)

| Tipo | Propiedades | Descripción |
|---|---|---|
| `titulo` | `texto` | Título principal |
| `subtitulo` | `texto` | Subtítulo |
| `encabezado` | `texto`, `nivel` (3–6) | Encabezado |
| `parrafo` | `texto` | Texto normal |
| `texto_enriquecido` | `fragmentos` | Array de `{texto,negrita,cursiva,subrayado,color,enlace}`; `color` en hexadecimal `#RRGGBB`, `enlace` http(s) |
| `etiqueta` | `texto` | Campo en negrita |
| `valor` | `texto` | Valor del campo |
| `etiqueta_valor` | `etiqueta`, `valor` | Pareja de campo y valor |
| `lista_vinetas` | `elementos` | Array de textos |
| `lista_numerada` | `elementos` | Array de textos |
| `cita` | `texto`, `autor` opcional | Cita destacada |
| `nota` | `texto` | Nota resaltada |
| `nota_pie` | `texto` | Nota con marcador, al final del contenedor donde se coloca (no sistema automático de paginación) |
| `texto_legal` | `texto` | Texto legal con formato discreto |
| `texto_dinamico` | `texto` | Sustitución de `{{empresa}}` desde `datos` |
| `texto_condicional` | `texto`, `regla` | Texto si se cumple la regla |

Las variables `{{campo}}` se interpolan en los componentes de texto extendidos, incluidas listas y fragmentos enriquecidos. No se interpolan automáticamente en los componentes originales (`titulo`, `parrafo`, etc.).

### Condicional

```json
{"tipo":"texto_condicional","texto":"Objetivo superado","regla":{"campo":"facturacion","operador":"mayor","valor":100000}}
```

Operadores: `igual`, `distinto` (comparación estricta), `mayor`, `menor`, `mayor_igual`, `menor_igual`, `existe`. No se ejecuta PHP ni JavaScript procedente del JSON.

## Tablas (15 tipos)

| Tipo | Propiedades relevantes | Función |
|---|---|---|
| `tabla` / `tabla_simple` | `columnas`, `filas` | Tabla básica; filas como arrays de valores |
| `tabla_dinamica` | `origen`, `columnas` opcional | Registros asociativos; columnas automáticas si se omiten |
| `tabla_agrupada` | `origen` o `filas`, `agrupar_por`, `columnas` | Una tabla por grupo |
| `tabla_jerarquica` | `columnas`, `filas` | Árbol anidado mediante propiedad `hijos`, máximo 12 niveles |
| `tabla_pivote` | `origen`, `campo_fila`, `campo_columna`, `campo_valor`, `operacion` | Agregación de datos cruzados |
| `matriz` | `filas`, `columnas`, `valores`, `cabecera_filas` | Cruce de etiquetas de filas y columnas |
| `tabla_comparativa` | `columnas`, `filas` | Comparación tabular |
| `tabla_totales` | `columnas`, `filas`, `columna_total`, `impuesto_porcentaje` | Subtotal, impuesto y total |
| `tabla_frecuencias` | `origen`, `campo` | Conteos y porcentajes |
| `tabla_calendario` | `mes` (`YYYY-MM`), `filas` | Calendario lunes-domingo, eventos `{fecha,texto}` |
| `tabla_doble_entrada` | Igual que `tabla_pivote` | Cruce agregado de variables |
| `tabla_celdas_fusionadas` | `columnas`, `filas` | Cada celda admite `{valor,colspan,rowspan}` |
| `tabla_formato_condicional` | `columnas`, `filas`, `reglas` | Reglas por índice de columna |
| `tabla_formulas` | `columnas`, `filas`, `formulas` | Cálculos entre índices de columnas |

Para tablas con `origen`, los registros se reciben como objetos JSON en `datos`. Para tablas con `filas`, el formato depende del componente: arrays numéricos para totales y fórmulas, objetos para agrupadas, jerárquicas y calendario.

### Tabla pivote

```json
{"tipo":"tabla_pivote","origen":"ventas","campo_fila":"area","campo_columna":"periodo","campo_valor":"importe","operacion":"suma"}
```

Operaciones: `suma`, `conteo`, `promedio`, `minimo`, `maximo`.

### Fórmulas

```json
{"tipo":"tabla_formulas","columnas":["Precio","Cantidad","Total"],"filas":[[10,3,0]],"formulas":[{"destino":2,"a":0,"b":1,"operador":"multiplicar"}]}
```

Operadores: `sumar`, `restar`, `multiplicar`, `dividir`. `a`, `b` y `destino` son índices de columna basados en cero. Se admite `constante` en lugar de `b`. Una división entre cero devuelve `N/D`. Las fórmulas se evalúan secuencialmente por fila, sin `eval`.

### Formato condicional

```json
{"tipo":"tabla_formato_condicional","columnas":["Alumno","Nota"],"filas":[["Ana",9],["Luis",4]],"reglas":[{"columna":1,"operador":"menor","valor":5,"clase":"celda-negativa"}]}
```

Operadores: `mayor`, `menor`, `igual`. Clases: `celda-positiva`, `celda-negativa`, `celda-alerta`.

## Componentes anteriores

Se mantienen `barras`, `lineas`, `circular` (`titulo`, `etiquetas`, `valores`, `ancho`, `alto`), `kpi`, `contador`, `porcentaje` (`etiqueta`, `valor`, `descripcion`) y los textos básicos. Las gráficas son SVG sin dependencias externas.

## Limitaciones

- Salida HTML e impresión mediante navegador; no genera PDF nativo.
- Las notas al pie se insertan donde se declaran; no se recolocan automáticamente en el pie físico de una página impresa.
- Las tablas comparativas y de doble entrada comparten infraestructura con las tablas genéricas y pivote respectivamente.
- El formato condicional se aplica por reglas simples; no incluye expresiones arbitrarias.
- Las fórmulas son operaciones aritméticas por fila, no un intérprete de hojas de cálculo.
- La validación de los nuevos componentes verifica campos obligatorios y determinadas reglas, pero no constituye un JSON Schema exhaustivo de todos los valores anidados.
- No hay paginación automática del contenido desbordado: las páginas se declaran en JSON.

Consulta `ejemplo-completo.json` para ver todos los componentes en un único documento.


## Opciones transversales de tablas (versión modular)

Todos los componentes de tabla aceptan opcionalmente:

- `numerar_filas`: booleano, añade columna `Nº` a las filas de datos.
- `mostrar_recuento`: booleano, añade al pie el número de filas HTML representadas.
- `totales`: array de índices numéricos de columnas (base cero) que se suman. Solo cuentan valores numéricos simples; no interpreta importes con símbolos ni fórmulas.

```json
{
  "tipo": "tabla_simple",
  "columnas": ["Producto", "Unidades", "Precio"],
  "filas": [["A", 2, 12.5], ["B", 3, 9]],
  "numerar_filas": true,
  "mostrar_recuento": true,
  "totales": [1, 2]
}
```

En tablas agrupadas, cada grupo lleva su propia numeración/recuento/total. En tablas con celdas fusionadas, los índices de columnas se corresponden con las celdas físicas, no con su extensión visual. En tablas con totales internos (`tabla_totales`), no actives totales adicionales salvo que desees sumar también las filas calculadas; el recuento incluye las filas de subtotal existentes.

### Organización del código

`bootstrap.php` registra el mapa de clases y utiliza `spl_autoload_register` con `require_once`. Cada clase está en un archivo independiente dentro de `src/documento/`, `src/componentes/texto/`, `src/componentes/tablas/`, `src/componentes/graficas/` o `src/componentes/indicadores/`. `PresentacionTabla.php` es un auxiliar transversal. Los antiguos `motor-informes.php` y `ComponentesExtendidos.php` se mantienen como adaptadores de compatibilidad.
