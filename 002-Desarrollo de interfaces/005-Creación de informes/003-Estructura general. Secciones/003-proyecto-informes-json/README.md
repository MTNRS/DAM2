# Proyecto de informes declarativos JSON

Motor PHP 8.1+ para convertir documentos JSON en informes HTML imprimibles.

## Arranque

```bash
php -S localhost:8000
```

Visita http://localhost:8000/ para ver el ejemplo. Para enviar JSON:

```bash
curl -X POST http://localhost:8000/api.php -H 'Content-Type: application/json' --data-binary @ejemplo-completo.json -o informe.html
```

Para convertir por CLI:

```bash
php generar.php ejemplo-completo.json informe.html
```

**Lee `DOCUMENTACION.md`** para conocer todas las propiedades, componentes, validaciones, ejemplos y limitaciones.
