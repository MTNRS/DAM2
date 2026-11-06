# Gráficas SVG avanzadas

Todos los gráficos se definen dentro de `componentes` en el JSON y aceptan `tipo`, `titulo`, `ancho` (300–1600) y `alto` (200–1200).

| Tipo JSON | Datos requeridos |
|---|---|
| `area` | `etiquetas`, `valores` |
| `dispersion` | `puntos` (x, y)` |
| `indicador_grafico` | `valor`, `minimo`, `maximo` |
| `barras_horizontales` | `etiquetas`, `valores` |
| `barras_apiladas` | `etiquetas`, `series` (nombre, valores)` |
| `anillos` | `etiquetas`, `valores` |
| `histograma` | `valores`, `intervalos` opcional` |
| `radar` | `etiquetas`, `valores` |
| `burbujas` | `puntos` (x, y, z)` |
| `mapa_calor` | `matriz` rectangular numérica` |
| `caja` | `valores` |
| `cascada` | `etiquetas`, `valores` |
| `embudo` | `etiquetas`, `valores` |
| `gantt` | `tareas` (nombre, inicio, fin YYYY-MM-DD)` |
| `linea_temporal` | `eventos` (fecha YYYY-MM-DD, texto)` |
| `sparkline` | `etiquetas`, `valores` |

Los gráficos originales `barras`, `lineas` y `circular` siguen disponibles. Todos los SVG se generan en PHP sin CDN. `sparkline` tiene dimensiones mínimas heredadas de `Grafica` (300 × 200). El diagrama de caja utiliza cuantiles interpolados y extremos mínimo/máximo, no detección de atípicos. El Gantt usa fechas inclusivas visualmente aproximadas.

## Ejemplo
```json
{
  "tipo": "burbujas",
  "titulo": "Relación entre variables",
  "puntos": [
    {
      "x": 1,
      "y": 5,
      "z": 20
    },
    {
      "x": 3,
      "y": 2,
      "z": 50
    },
    {
      "x": 5,
      "y": 7,
      "z": 90
    }
  ]
}
```

Para probar: `php generar.php ejemplo-completo.json informe.html` o `php -S localhost:8000`. El CSS usa `href="estilos.css"` relativo.
