<?php
/** Temas instalados mediante carpetas motor/temas/<slug>/tema.css + tema.json. */
function temas_disponibles(): array {
    $base=dirname(__DIR__).'/motor/temas';
    $temas=[];
    foreach(glob($base.'/*',GLOB_ONLYDIR)?:[] as $dir){
        $slug=basename($dir);
        if(!preg_match('/^[a-z0-9][a-z0-9-]{0,40}$/',$slug))continue;
        if(!is_file($dir.'/tema.css')||!is_file($dir.'/tema.json'))continue;
        $meta=json_decode(file_get_contents($dir.'/tema.json'),true);
        if(!is_array($meta)||!isset($meta['nombre'])||!is_string($meta['nombre']))continue;
        $temas[$slug]=['slug'=>$slug,'nombre'=>$meta['nombre'],'descripcion'=>(string)($meta['descripcion']??'')];
    }
    ksort($temas);
    return $temas;
}
function tema_valido(string $slug): bool {return isset(temas_disponibles()[$slug]);}
function css_tema(string $slug): string {
    if(!tema_valido($slug))$slug='clasico';
    $base=dirname(__DIR__).'/motor';
    $css=file_get_contents($base.'/estilos.css');
    $extra=file_get_contents($base.'/temas/'.$slug.'/tema.css');
    return $css."\n/* Tema activo: ".$slug." */\n".$extra;
}
