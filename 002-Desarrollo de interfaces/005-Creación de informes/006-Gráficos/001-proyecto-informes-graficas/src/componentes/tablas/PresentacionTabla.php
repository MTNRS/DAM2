<?php
/** Opciones transversales para cualquier tabla HTML generada por el motor. */
final class PresentacionTabla {
    public static function aplicar(string $html, array $opciones): string {
        $numerar = $opciones['numerar_filas'] ?? false;
        $recuento = $opciones['mostrar_recuento'] ?? false;
        $totales = $opciones['totales'] ?? [];
        if (!$numerar && !$recuento && !$totales) return $html;
        $inicio = strpos($html, '<table');
        if ($inicio === false) return $html;
        $fin = strpos($html, '</table>', $inicio);
        if ($fin === false) return $html;
        $fin += strlen('</table>');
        $tabla = substr($html, $inicio, $fin - $inicio);
        preg_match_all('~<th\b[^>]*>.*?</th>~s', $tabla, $ths);
        $columnas = count($ths[0]);
        if ($columnas === 0) return $html;
        if ($numerar) $tabla = preg_replace('~<thead>\s*<tr>~', '<thead><tr><th scope="col">Nº</th>', $tabla, 1);
        $sumas = array_fill(0, $columnas, 0.0);
        $contados = 0;
        $tabla = preg_replace_callback('~<tbody>(.*?)</tbody>~s', function($m) use ($numerar, &$contados, &$sumas, $columnas) {
            $cuerpo = preg_replace_callback('~<tr\b([^>]*)>(.*?)</tr>~s', function($tr) use ($numerar, &$contados, &$sumas, $columnas) {
                $contados++;
                preg_match_all('~<td\b[^>]*>(.*?)</td>~s', $tr[2], $celdas);
                foreach ($celdas[1] as $j => $contenido) {
                    if ($j >= $columnas) break;
                    $texto = trim(strip_tags($contenido));
                    $texto = str_replace(["\xc2\xa0", ' '], '', $texto);
                    if (is_numeric($texto)) $sumas[$j] += (float)$texto;
                }
                return '<tr'.$tr[1].'>'.($numerar?'<th scope="row" class="tabla-numero">'.$contados.'</th>':'').$tr[2].'</tr>';
            }, $m[1]);
            return '<tbody>'.$cuerpo.'</tbody>';
        }, $tabla, 1);
        $filasPie = '';
        if ($totales) {
            $fila = array_fill(0, $columnas, '');
            foreach ($totales as $indice) {
                if (is_int($indice) && $indice >= 0 && $indice < $columnas) {
                    $fila[$indice] = number_format($sumas[$indice], 2, ',', '.');
                }
            }
            $celdas = '';
            foreach ($fila as $j => $valor) $celdas .= '<td>'.($j===0 && $valor===''?'TOTAL':htmlspecialchars($valor, ENT_QUOTES, 'UTF-8')).'</td>';
            $filasPie .= '<tr class="tabla-fila-total">'.($numerar?'<th></th>':'').$celdas.'</tr>';
        }
        if ($recuento) {
            $filasPie .= '<tr class="tabla-fila-recuento"><td colspan="'.($columnas+($numerar?1:0)).'">Registros: '.$contados.'</td></tr>';
        }
        if ($filasPie) $tabla = str_replace('</table>', '<tfoot>'.$filasPie.'</tfoot></table>', $tabla);
        return substr($html, 0, $inicio).$tabla.substr($html, $fin);
    }
}
