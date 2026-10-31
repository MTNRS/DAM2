<?php
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function reply(int $status,string $message,bool $ok=false,array $extra=[]): never {
 http_response_code($status);
 echo json_encode(['ok'=>$ok,'message'=>$message]+$extra,JSON_UNESCAPED_UNICODE);
 exit;
}
if ($_SERVER['REQUEST_METHOD']!=='POST') {header('Allow: POST');reply(405,'Método no permitido.');}
if (session_status()!==PHP_SESSION_ACTIVE) session_start();
if (!isset($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals($_SESSION['contact_csrf']??'',$_POST['csrf'])) reply(403,'Sesión caducada. Recarga la página.');
if (!empty($_POST['sitio_web'])) reply(200,'Consulta recibida.',true);
if (($_SESSION['contact_last']??0)>time()-30) reply(429,'Espera unos segundos antes de enviar otra consulta.');
function field(string $key,int $max): string {
 $v=$_POST[$key]??'';
 if(!is_string($v)) reply(422,'Datos no válidos.');
 $v=trim($v);
 if(mb_strlen($v,'UTF-8')>$max) reply(422,'Un campo supera la longitud permitida.');
 return $v;
}
$name=field('nombre',120);$email=field('email',200);$empresa=field('empresa',150);$tipo=field('servicio',100);$mensaje=field('mensaje',5000);
$allowed=['Desarrollo a medida','Software como servicio (SaaS)','IA como servicio','Servicios gestionados','Consultoría tecnológica','Formación empresarial','Otra consulta'];
if(mb_strlen($name)<2||!filter_var($email,FILTER_VALIDATE_EMAIL)||!in_array($tipo,$allowed,true)||mb_strlen($mensaje)<15||($_POST['consentimiento']??'')==='') reply(422,'Revisa los campos del formulario.');
$payload=['nombre y apellidos'=>$name,'email'=>$email,'empresa'=>$empresa,'tipodeproyecto'=>$tipo,'mensaje'=>$mensaje];
$config=require __DIR__.'/config.php';$endpoint=$config['oportunidades'];
$curl=curl_init($endpoint['url']);
curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['X-API-Key: '.$endpoint['key'],'Accept: application/json','Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>12,CURLOPT_FOLLOWLOCATION=>false]);
$result=curl_exec($curl);$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);$failed=$result===false;curl_close($curl);
if($failed||$code<200||$code>=300){error_log('JOCARSA oportunidades: upstream HTTP '.$code.' curl_failed='.($failed?'yes':'no'));reply(502,'No se pudo registrar la consulta. Inténtalo más tarde.');}
$_SESSION['contact_last']=time();$_SESSION['contact_csrf']=bin2hex(random_bytes(32));
reply(200,'Consulta registrada.',true,['csrf'=>$_SESSION['contact_csrf']]);
