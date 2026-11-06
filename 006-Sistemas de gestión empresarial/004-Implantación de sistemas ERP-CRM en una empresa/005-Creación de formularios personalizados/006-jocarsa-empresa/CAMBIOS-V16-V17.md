# v16 + v17: Formularios y flujos de trabajo

- Campos personalizados definidos por entidad, persistidos como metadatos compartidos en `crimson_ui_config`.
- Valores de campos personalizados en tabla `crimson_ui_values` de jocarsa-bbdd, asociados por entidad, registro y campo.
- Formularios dinámicos: tipos, etiquetas, secciones, orden, obligatoriedad y opciones.
- Flujos de trabajo: campo de estado, estados permitidos, transiciones y rol opcional; validación en servidor.
- Botones de transición por registro y comprobación de permisos de actualización.

Limitaciones: sin migración de valores si se renombra un campo personalizado; no hay transacciones entre tabla principal y tabla de valores, ni motor de automatizaciones. Validar en instalación real antes de producción.
