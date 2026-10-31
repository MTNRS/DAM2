# Corrección de inicialización de usuarios

Las tablas internas de Crimson pasan a llamarse `crimson_users` y `crimson_permissions`. No se modifica el nombre físico de la columna `__crimson_activo`.

## Uso

1. Hacer copia de seguridad de la base `jocarsa-erp`.
2. Sustituir los archivos del proyecto.
3. Ejecutar `CRIMSON_ADMIN_PASSWORD=... php inicializar-usuarios-cli.php admin Administrador admin@example.com` con una contraseña de al menos 12 caracteres.
4. Comprobar que existen `crimson_users` y `crimson_permissions`.

Si falla de nuevo, comprobar `jocarsabbdd_crear_tabla_tipada` y permisos del directorio de la base; la causa original no está confirmada solo por el mensaje genérico de error.
