<?php
declare(strict_types=1);
require_once __DIR__.'/../sync.php';
jocarsa_sync(__DIR__.'/../api');
function h(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function slug(string $s):string{$s=preg_replace('/^jocarsa\s*[|\-]\s*/iu','',$s);$s=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s)?:$s;$s=strtolower($s);return trim(preg_replace('/[^a-z0-9]+/','-',$s),'-');}
function site_base():string { $https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off');$host=$_SERVER['HTTP_HOST']??'localhost';$path=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')),'/');return ($https?'https':'http').'://'.$host.$path; }
function data_json(string $name):array {$path=__DIR__.'/../api/'.$name.'.json';$v=is_file($path)?json_decode(file_get_contents($path),true):null;return is_array($v)?$v:[];}
function rows(array $data):array {
 if(array_is_list($data))return $data;
 foreach(['datos','data','items','registros','resultados','productos'] as $key){
  if(isset($data[$key])&&is_array($data[$key])){
   if(array_is_list($data[$key]))return $data[$key];
   return rows($data[$key]);
  }
 }
 return [];
}
function products():array {
 $d=data_json('productos-categorias');
 $source=$d['datos']??$d['data']??$d;
 if(isset($source['categorias'])&&is_array($source['categorias'])){
  $out=[];
  foreach($source['categorias'] as $c)foreach(($c['productos']??[]) as $item){
   if(!is_array($item))continue;
   $item['categoria']=$c['nombre']??'';$out[]=$item;
  }
  return $out;
 }
 $out=[];
 foreach(rows($d) as $item){
  if(!is_array($item))continue;
  $item['nombre']=preg_replace('/^jocarsa[-\s|]+/iu','',(string)($item['nombre']??$item['name']??$item['titulo']??''));
  $category=$item['categoria_nombre']??$item['categoria']??$item['category']??$item['nombre_categoria']??$item['categoria_id']??'Sin categoría';
  if(is_array($category))$category=$category['nombre']??$category['id']??'Sin categoría';
  $item['categoria']=is_numeric($category)?'Categoría '.$category:(string)$category;
  $item['slogan']=$item['slogan']??$item['descripcion_corta']??$item['descripcion']??'';
  $out[]=$item;
 }
 return $out;
}
function posts():array {return rows(data_json('articulosblog'));}
function head_tags(string $title,string $description,string $path,string $type='website',?string $image=null):void {
 $url=site_base().$path;$image=$image?:'https://jocarsa.com/jocarsa-imagotipo.svg';
 echo '<meta name="description" content="'.h($description).'"><link rel="canonical" href="'.h($url).'">';
 foreach(['og:type'=>$type,'og:site_name'=>'JOCARSA','og:title'=>$title,'og:description'=>$description,'og:url'=>$url,'og:image'=>$image,'twitter:card'=>'summary_large_image','twitter:title'=>$title,'twitter:description'=>$description,'twitter:image'=>$image] as $k=>$v)echo '<meta '.(str_starts_with($k,'og:')?'property':'name').'="'.h($k).'" content="'.h($v).'">';
}
function jsonld(array $data):void {echo '<script type="application/ld+json">'.json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</script>';}
