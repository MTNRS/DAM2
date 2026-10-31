# JOCARSA — web con blog ERP

El blog consume `articulosblog` (campos `titulo`, `fecha`, `contenido`) y el catálogo consume `productos`. Cada endpoint tiene su propia clave `apiKey` en `endpoints.js`. Los demás contenidos siguen usando JSON locales.

La configuración de autenticación está en JavaScript por petición expresa; las claves son visibles públicamente. En producción deberán trasladarse al servidor.

Si la petición desde el navegador falla, verificar CORS y la cabecera `X-API-Key` en Apache. El endpoint `articulosblog` debe estar **activo** y permitir **GET** (en la captura aparece inactivo).

Ejecutar con un servidor HTTP, no `file://`. El blog muestra contenido real; no se inventan artículos. La API debe devolver `{"data":[{"titulo":"...","fecha":"YYYY-MM-DD","contenido":"..."}]}`. La carga se realiza desde el navegador del equipo, donde `localhost` debe apuntar al Apache del ERP.
