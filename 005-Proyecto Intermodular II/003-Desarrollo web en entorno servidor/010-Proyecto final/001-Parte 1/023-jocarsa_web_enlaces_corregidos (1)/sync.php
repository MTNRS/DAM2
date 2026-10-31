<?php
declare(strict_types=1);
function jocarsa_sync(string $publicDir): void {
 $config=require __DIR__.'/config.php';
 if(!is_dir($publicDir)) return;
 foreach($config as $name=>$endpoint){
  if(!preg_match('/^[a-z0-9-]+$/D',$name)) continue;
  $file=$publicDir.'/'.$name.'.json';
  $lock=fopen(sys_get_temp_dir().'/jocarsa-'.hash('sha256',$file).'.lock','c');
  if(!$lock) continue;
  if(!flock($lock,LOCK_EX|LOCK_NB)){fclose($lock);continue;}
  try {
   // Intervalo configurable: 0 = actualizar en cada recarga.
   $ttl=max(0,(int)(getenv('JOCARSA_CACHE_SECONDS') ?: 60));
   if(is_file($file) && time()-filemtime($file)<$ttl) continue;
   $key=(string)$endpoint['key'];
   if(str_starts_with($key,'CONFIGURAR_')){error_log('JOCARSA: falta clave de '.$name);continue;}
   $ch=curl_init((string)$endpoint['url']);
   curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Accept: application/json','X-API-Key: '.$key],CURLOPT_CONNECTTIMEOUT=>2,CURLOPT_TIMEOUT=>8,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTP|CURLPROTO_HTTPS]);
   $body=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
   $error=curl_error($ch);curl_close($ch);
   if($body===false || $status!==200){error_log('JOCARSA: '.$name.' HTTP '.$status.' '.$error);continue;}
   try {$decoded=json_decode($body,true,512,JSON_THROW_ON_ERROR);}catch(JsonException $e){error_log('JOCARSA: JSON inválido '.$name);continue;}
   if(!is_array($decoded) || isset($decoded['error'])){error_log('JOCARSA: respuesta inválida '.$name);continue;}
   // No publicar una respuesta de error o una estructura inesperada.
   $content=json_encode($decoded,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
   $tmp=tempnam($publicDir,'.sync-');if($tmp===false)continue;
   if(file_put_contents($tmp,$content,LOCK_EX)===false){@unlink($tmp);continue;}
   chmod($tmp,0644);
   if(!rename($tmp,$file))@unlink($tmp);
  } catch(Throwable $e){error_log('JOCARSA: '.$name.' '.$e->getMessage());}
  finally {flock($lock,LOCK_UN);fclose($lock);}
 }
}
