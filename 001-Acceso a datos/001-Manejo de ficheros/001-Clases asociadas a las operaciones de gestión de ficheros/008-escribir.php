<?php
// Función para escribir un archivo de texto
function escribirArchivo($ruta, $contenido) {
    // Abrir el archivo en modo escritura
    $archivo = fopen($ruta, 'w');
    
    // Verificar si el archivo se abrió correctamente
    if ($archivo) {
        // Escribir el contenido en el archivo
        fwrite($archivo, $contenido);
        
        // Cerrar el archivo
        fclose($archivo);
        
        // Devolver true si la escritura fue exitosa
        return true;
    } else {
        // Devolver false si hubo un error al abrir el archivo
        return false;
    }
}

// Ejemplo de uso
$ruta = 'ruta/al/archivo.txt';
$contenido = 'Este es el contenido del archivo de texto';

if (escribirArchivo($ruta, $contenido)) {
    echo 'El archivo se escribió correctamente.';
} else {
    echo 'Hubo un error al escribir el archivo.';
}
?>