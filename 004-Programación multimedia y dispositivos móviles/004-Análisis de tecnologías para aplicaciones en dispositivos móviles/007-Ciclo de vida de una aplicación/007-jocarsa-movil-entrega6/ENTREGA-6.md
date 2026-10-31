# Entrega 6 — Sensores y capacidades avanzadas

Se amplía `JocarsaDevice` con: `solicitarSensores`, `observarOrientacion`, `observarMovimiento`, `vibrar`, `pantallaCompleta`, `salirPantallaCompleta` y `mantenerPantallaActiva`. La vista **Dispositivo** incluye demostraciones y controles de inicio/parada.

## Notas

- Sensores y Wake Lock requieren un contexto seguro y soporte del navegador. iOS puede requerir `requestPermission()` desde un gesto de usuario.
- Los sensores se desuscriben al abandonar la vista.
- Wake Lock se libera al abandonar la vista y se intenta recuperar al volver a ella si sigue activa.
- La vibración y la pantalla completa pueden no estar disponibles en Safari iOS.
- El Service Worker incrementa su versión para invalidar la caché anterior.
