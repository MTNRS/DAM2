<?php

const JOCARSA_NS = 'https://jocarsa.com/ns/sitemap';

function cargarXml(string $url): ?SimpleXMLElement{
    libxml_use_internal_errors(true);
    $xml = simplexml_load_file($url);
    if($xml === false){
        return null;
    }
    return $xml;
}

function leerSitemaps(string $url, array &$paginas): void{
    $xml = cargarXml($url);
    if($xml === null){
        return;
    }

    // Si es un índice de sitemaps, seguimos cada sitemap.
    if(isset($xml->sitemap)){
        foreach($xml->sitemap as $sitemap){
            $loc = trim((string)$sitemap->loc);
            if($loc !== ''){
                leerSitemaps($loc, $paginas);
            }
        }
        return;
    }

    // Si es un urlset, recogemos cada URL y su padre jocarsa:parent.
    if(isset($xml->url)){
        foreach($xml->url as $url){
            $loc = trim((string)$url->loc);
            if($loc === ''){
                continue;
            }

            $jocarsa = $url->children(JOCARSA_NS);
            $parent = isset($jocarsa->parent)
                ? trim((string)$jocarsa->parent)
                : null;

            $paginas[$loc] = [
                'loc' => $loc,
                'parent' => $parent ?: null,
                'hijos' => []
            ];
        }
    }
}

function construirArbol(array $paginas): array{
    // Trabajamos con referencias para que cada nodo pueda contener sus hijos.
    $nodos = [];

    foreach($paginas as $loc => $pagina){
        $nodos[$loc] = $pagina;
    }

    $raices = [];

    foreach(array_keys($nodos) as $loc){
        $parent = $nodos[$loc]['parent'];

        if($parent !== null && isset($nodos[$parent]) && $parent !== $loc){
            $nodos[$parent]['hijos'][] =& $nodos[$loc];
        }else{
            $raices[] =& $nodos[$loc];
        }
    }

    return $raices;
}

function tituloDesdeUrl(string $url): string{
    $partes = parse_url($url);

    if($url === 'https://jocarsa.com/'){
        return 'Inicio';
    }

    if(isset($partes['query'])){
        parse_str($partes['query'], $query);

        if(isset($query['id'])){
            return $query['id'];
        }

        if(isset($query['page'])){
            return ucfirst(str_replace('-', ' ', $query['page']));
        }
    }

    $path = trim($partes['path'] ?? '', '/');
    return $path !== '' ? $path : $url;
}

function pintarArbol(array $nodos, int $nivel = 0): void{
    foreach($nodos as $nodo){
        $loc = htmlspecialchars($nodo['loc'], ENT_QUOTES, 'UTF-8');
        $titulo = htmlspecialchars(tituloDesdeUrl($nodo['loc']), ENT_QUOTES, 'UTF-8');
        $margen = $nivel * 30;

        echo '<div class="caja" style="margin-left:'.$margen.'px">';
        echo '<a href="'.$loc.'" target="_blank">'.$titulo.'</a>';
        echo '<small>'.$loc.'</small>';
        echo '</div>';

        if(!empty($nodo['hijos'])){
            pintarArbol($nodo['hijos'], $nivel + 1);
        }
    }
}

$paginas = [];
leerSitemaps('https://jocarsa.com/sitemap.xml', $paginas);
$arbol = construirArbol($paginas);

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sitemap jerárquico</title>
    <style>
        *{box-sizing:border-box;}
        body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;color:#222;}
        .caja{background:white;border:1px solid #ddd;padding:10px 12px;margin-top:5px;margin-bottom:5px;max-width:1000px;}
        .caja a{display:block;color:#222;text-decoration:none;font-weight:bold;}
        .caja a:hover{text-decoration:underline;}
        .caja small{display:block;margin-top:3px;color:#777;word-break:break-all;}
    </style>
</head>
<body>
<?php pintarArbol($arbol); ?>
</body>
</html>
