# v20 — Configuración general y paginación

- Endpoint GET `backend/api.php?bloque=erp_settings_get` para leer los ajustes compartidos.
- Endpoint POST `backend/api.php?bloque=erp_settings_save` para guardar `themeColor` (hexadecimal #RRGGBB) y `rowsPerPage` (entero de 5 a 500). Solo administradores pueden guardar.
- Persistencia en `crimson_ui_config` (`entidad=__erp_global__`, `tipo=settings`). Se crea automáticamente al guardar por primera vez.
- Botón **Configuración** visible para administradores en la barra de la entidad.
- Previsualización del tema al cambiar el selector; cancelar restaura el anterior.
- Paleta generada a partir del único color corporativo mediante mezcla RGB con blanco y negro. Variables CSS comunes actualizadas.
- Paginación en tabla, tarjetas y árbol, con botones Anterior/Siguiente y recuento.
- Los filtros y la ordenación se aplican antes de paginar. La exportación CSV sigue exportando los resultados filtrados completos.

## Alcance

La paginación es **del lado del navegador** sobre los registros obtenidos por Crimson; no reduce el volumen inicial de datos transferidos. Para grandes tablas conviene introducir paginación real en jocarsa-bbdd.

Los estilos de componentes heredados que no utilizan variables CSS podrían conservar algún color propio; los componentes principales del ERP usan la paleta derivada.

## Verificación

Validación estática de sintaxis JavaScript y PHP y estructura ZIP. Pendiente de prueba funcional con el motor jocarsa-bbdd del servidor.
