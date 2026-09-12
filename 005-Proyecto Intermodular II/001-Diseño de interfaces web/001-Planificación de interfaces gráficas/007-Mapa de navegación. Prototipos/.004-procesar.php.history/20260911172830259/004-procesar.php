<?php

function leerSitemap($url) {

    $xml = simplexml_load_file($url);

    if ($xml === false) {
        return [];
    }

    $resultado = [];

    if (isset($xml->sitemap)) {

        foreach ($xml->sitemap as $sitemap) {

            $urlHija = (string)$sitemap->loc;

            $resultado[] = [
                "url" => $urlHija,
                "contenido" => leerSitemap($urlHija)
            ];
        }

    } elseif (isset($xml->url)) {

        foreach ($xml->url as $pagina) {

            $item = [
                "loc" => (string)$pagina->loc
            ];

            if (isset($pagina->lastmod)) {
                $item["lastmod"] = (string)$pagina->lastmod;
            }

            if (isset($pagina->changefreq)) {
                $item["changefreq"] = (string)$pagina->changefreq;
            }

            if (isset($pagina->priority)) {
                $item["priority"] = (string)$pagina->priority;
            }

            $resultado[] = $item;
        }

    }

    return $resultado;
}


function arrayAHtml($array, $nivel = 0) {

    $html = "";

    foreach ($array as $elemento) {

        $html .= '<div class="caja" style="margin-left:' . ($nivel * 30) . 'px">';

        // Sitemap intermedio
        if (isset($elemento["url"])) {

            $html .= '<strong>';
            $html .= htmlspecialchars($elemento["url"]);
            $html .= '</strong>';

        }

        // URL final
        if (isset($elemento["loc"])) {

            $html .= '<a href="' . htmlspecialchars($elemento["loc"]) . '">';
            $html .= htmlspecialchars($elemento["loc"]);
            $html .= '</a>';

        }

        $html .= '</div>';


        // Si tiene hijos, seguimos descendiendo
        if (isset($elemento["contenido"])) {

            $html .= arrayAHtml(
                $elemento["contenido"],
                $nivel + 1
            );

        }

    }

    return $html;
}


$sitemap = leerSitemap(
    "https://jocarsa.com/sitemap.xml"
);

?>
<!doctype html>
<html lang="es">
<head>

    <meta charset="utf-8">

    <title>Sitemap</title>

    <style>

        *{
            box-sizing:border-box;
        }

        body{
            font-family:Arial, sans-serif;
            margin:20px;
            background:#f5f5f5;
        }

        .caja{
            background:white;
            border:1px solid #ddd;
            padding:10px;
            margin-top:5px;
            margin-bottom:5px;
            max-width:900px;
        }

        .caja a{
            color:#333;
            text-decoration:none;
        }

        .caja a:hover{
            text-decoration:underline;
        }

    </style>

</head>
<body>

    <?php

    echo arrayAHtml($sitemap);

    ?>

</body>
</html>