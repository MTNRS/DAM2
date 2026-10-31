<?php
require __DIR__.'/includes/bootstrap.php';
$all=products();$slugValue=(string)($_GET['slug']??'');$selected=null;
foreach($all as $product){if(slug((string)($product['nombre']??$product['titulo']??''))===$slugValue&&$slugValue!==''){$selected=$product;break;}}
if($slugValue!==''&&!$selected){http_response_code(404);}
$name=$selected['nombre']??$selected['titulo']??'';
$title=$selected?'JOCARSA | '.$name:'Productos y aplicaciones | JOCARSA';
$description=$selected?mb_substr((string)($selected['slogan']??$selected['descripcion']??$name),0,155):'Catálogo de aplicaciones y soluciones digitales de JOCARSA, organizado por categorías.';
$canonicalPath=$selected?'/productos/'.rawurlencode(slug($name)):'/productos';$ogType='website';
require __DIR__.'/includes/page-start.php';
if($selected) jsonld(['@context'=>'https://schema.org','@type'=>'SoftwareApplication','name'=>$name,'description'=>$description,'applicationCategory'=>'BusinessApplication','publisher'=>['@type'=>'Organization','name'=>'JOCARSA']]);
?>
<main class="subpage"><div class="breadcrumb"><a href="<?=h(site_base())?>/index.php">Inicio</a> / <a href="<?=h(site_base())?>/productos">Productos</a><?= $selected?' / '.h($name):'' ?></div>
<?php if($slugValue!==''&&!$selected): ?><h1>Producto no encontrado</h1><p>El producto solicitado no está disponible.</p>
<?php elseif($selected): ?><span class="eyebrow"><?=h($selected['categoria']??'Software')?></span><h1><?=h($name)?></h1><p class="lead"><?=h($description)?></p><a class="btn primary" href="<?=h(site_base())?>/index.php#contacto">Consultar este producto</a>
<?php else: ?><span class="eyebrow">CATÁLOGO</span><h1>Productos JOCARSA</h1><p class="lead">Explora las soluciones disponibles.</p><div class="listing"><?php foreach($all as $product):$n=(string)($product['nombre']??$product['titulo']??'');if($n==='')continue;?><article class="card"><span class="tag"><?=h($product['categoria']??'Software')?></span><h2><?=h($n)?></h2><p><?=h($product['slogan']??$product['descripcion']??'')?></p><a href="<?=h(site_base())?>/productos/<?=rawurlencode(slug($n))?>">Ver producto</a></article><?php endforeach; ?></div><?php endif; ?></main>
<?php require __DIR__.'/includes/page-end.php'; ?>
