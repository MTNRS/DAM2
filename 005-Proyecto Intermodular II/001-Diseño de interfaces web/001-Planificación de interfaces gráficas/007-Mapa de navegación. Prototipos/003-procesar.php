<?php

function leerSitemap($url) {

    $xml = simplexml_load_file($url);

    if ($xml === false) {
        return [];
    }

    $resultado = [];

    // Es un índice de sitemaps
    if (isset($xml->sitemap)) {

        foreach ($xml->sitemap as $sitemap) {

            $urlHija = (string)$sitemap->loc;

            $resultado[] = [
                "url" => $urlHija,
                "contenido" => leerSitemap($urlHija)
            ];
        }

    }

    // Es un sitemap con URLs finales
    elseif (isset($xml->url)) {

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


$sitemap = leerSitemap(
    "https://jocarsa.com/sitemap.xml"
);

echo "<pre>";
var_dump($sitemap);
echo "</pre>";

?>