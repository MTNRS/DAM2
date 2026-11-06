<?php
require __DIR__.'/includes/bootstrap.php';
$all=posts();$slugValue=(string)($_GET['slug']??'');$selected=null;
foreach($all as $post){if(slug((string)($post['titulo']??''))===$slugValue&&$slugValue!==''){$selected=$post;break;}}
if($slugValue!==''&&!$selected)http_response_code(404);
$name=(string)($selected['titulo']??'');$description=$selected?mb_substr(trim(preg_replace('/\s+/u',' ',strip_tags((string)($selected['contenido']??'')))),0,155):'Artículos y noticias sobre desarrollo de software, automatización e inteligencia artificial en JOCARSA.';
$title=$selected?$name.' | Blog JOCARSA':'Blog de tecnología | JOCARSA';$canonicalPath=$selected?'/blog/'.rawurlencode(slug($name)):'/blog';$ogType=$selected?'article':'website';
require __DIR__.'/includes/page-start.php';
if($selected){$schema=['@context'=>'https://schema.org','@type'=>'BlogPosting','headline'=>$name,'description'=>$description,'mainEntityOfPage'=>site_base().$canonicalPath,'publisher'=>['@type'=>'Organization','name'=>'JOCARSA']];if(!empty($selected['fecha'])&&preg_match('/^\d{4}-\d{2}-\d{2}/',(string)$selected['fecha']))$schema['datePublished']=substr($selected['fecha'],0,10);jsonld($schema);}
?>
<main class="subpage"><div class="breadcrumb"><a href="<?=h(site_base())?>/index.php">Inicio</a> / <a href="<?=h(site_base())?>/blog">Blog</a><?= $selected?' / '.h($name):'' ?></div>
<?php if($slugValue!==''&&!$selected): ?><h1>Artículo no encontrado</h1>
<?php elseif($selected): ?><span class="eyebrow"><?=h($selected['fecha']??'BLOG')?></span><h1><?=h($name)?></h1><article class="article-body"><?=nl2br(h($selected['contenido']??''))?></article>
<?php else: ?><span class="eyebrow">ACTUALIDAD</span><h1>Blog JOCARSA</h1><p class="lead">Artículos sobre tecnología, software e inteligencia artificial.</p><div class="listing"><?php foreach($all as $post):$n=(string)($post['titulo']??'');if($n==='')continue;?><article class="card"><span class="tag"><?=h($post['fecha']??'')?></span><h2><?=h($n)?></h2><p><?=h(mb_substr((string)($post['contenido']??''),0,200))?></p><a href="<?=h(site_base())?>/blog/<?=rawurlencode(slug($n))?>">Leer artículo</a></article><?php endforeach; ?></div><?php endif; ?></main>
<?php require __DIR__.'/includes/page-end.php'; ?>
