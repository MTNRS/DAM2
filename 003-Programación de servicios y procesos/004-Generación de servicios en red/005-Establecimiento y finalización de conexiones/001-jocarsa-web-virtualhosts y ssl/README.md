# jocarsa | web - fase 4

Servidor C + intérprete independiente. VirtualHosts por Host y certificados TLS propios con OpenSSL/SNI. Sin Certbot.

## Compilar y probar

```bash
make
./bin/jocarsa-servidor --test-config
./bin/jocarsa-servidor --list-vhosts
./bin/jocarsa-servidor config/servidor.conf
```

Por defecto HTTP 8000 y TLS desactivado para poder arrancar sin certificados. Para activar HTTPS: poner `ssl_habilitado = 1`, indicar certificados PEM existentes en cada virtualhost y establecer `escuchar_https = 8443` (o 443 con permisos apropiados). `openssl s_client -connect localhost:8443 -servername localhost` permite probarlo.

Recarga de VirtualHosts/certificados: `kill -HUP PID_DEL_SERVIDOR`. El reload lee los archivos VirtualHost pero no reabre sockets ni reinterpreta la configuración global; para cambiar puertos o TLS hay que reiniciar. El reload no es transaccional: ante errores se detiene el servicio para evitar usar configuración incompleta.

VirtualHosts: un archivo `.conf` por sitio en `config/virtualhosts`, con `dominio`, `alias` (un alias), `document_root`, `certificado`, `clave`, `redirigir_https`. Todos los sitios requieren certificados si TLS está activo.

## Limitaciones de seguridad

**Prototipo local**: la lectura HTTP sigue siendo simple y secuencial, sin HTTP/2 ni aislamiento por virtualhost. La configuración del usuario de ejecución de scripts sigue siendo la de fase 3. No expongas este servidor directamente a Internet sin auditoría y endurecimiento adicional. Los certificados deben mantenerse fuera del directorio público. Para redirección con puerto HTTPS distinto de 443, el ejemplo usa 8443; ajustar antes de desplegar.
