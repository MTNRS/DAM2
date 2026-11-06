<?php
/** Envuelve el HTML del motor con estilos de presentación. */
function paginaHTML(Informe $informe): string {
    $titulo = escapar($informe->obtenerDocumento()->titulo);
    return '<!doctype html><html lang="es"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . $titulo . '</title><link rel="stylesheet" href="estilos.css">'
        . '</head><body>' . $informe->render() . '</body></html>';
}
