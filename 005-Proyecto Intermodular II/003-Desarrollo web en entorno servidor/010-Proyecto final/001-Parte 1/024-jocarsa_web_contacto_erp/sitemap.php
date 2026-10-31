<?php
require __DIR__.'/includes/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');
$urls=[site_base().'/index.php',site_base().'/productos',site_base().'/blog'];
foreach(products() as $p){$s=slug((string)($p['nombre']??$p['titulo']??''));if($s)$urls[]=site_base().'/productos/'.$s;}
foreach(posts() as $p){$s=slug((string)($p['titulo']??''));if($s)$urls[]=site_base().'/blog/'.$s;}
echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach(array_unique($urls) as $url)echo '<url><loc>'.htmlspecialchars($url,ENT_XML1|ENT_QUOTES,'UTF-8').'</loc></url>';
echo '</urlset>';
