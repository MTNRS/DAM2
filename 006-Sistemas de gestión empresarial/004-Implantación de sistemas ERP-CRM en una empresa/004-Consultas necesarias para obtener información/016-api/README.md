# Crimson ERP v21 integrado — instalación incremental

**Partir de una copia de seguridad de v20.** Este ZIP contiene únicamente los archivos nuevos o modificados.

Copiar `frontend/index.html`, `frontend/js/erp.js`, `frontend/js/api-endpoints.js`, `frontend/css/api-endpoints.css` y los tres PHP de `backend/api/` a las mismas rutas del proyecto. No sustituir otras carpetas.

Desde cada tabla, botón **API endpoint** de la barra de acciones. Desde **Configuración** de la tabla, botón **Ver endpoints** con listado de todos los endpoints y editor integrado. Todo se abre en modal dentro del frontend, sin página de administración separada.

Se mantiene la autenticación de administrador de la API anterior y la creación de claves con hash. Las publicaciones nuevas están desactivadas. **No activar escritura en producción** sin integrar las validaciones de workflows del ERP. Se requiere HTTPS, CSRF y rate limiting antes de exposición pública.

No probado en una instancia activa de JocarsaBBDD.
