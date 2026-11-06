# Corrección v15

Se corrige el error de JavaScript al añadir una acción de impresión: `field()` devuelve el control `textarea`, no su etiqueta contenedora. `syncFormat()` ahora obtiene el `span` desde `templateField.parentElement`.
