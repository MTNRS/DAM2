<?php
function escapar($texto){ return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8"); }
function identificador($texto){ return '"' . str_replace('"', '""', $texto) . '"'; }

function leerModelo($archivo){
  if(!file_exists($archivo)) throw new Exception("No existe modelodedatos.md");
  $lineas=file($archivo,FILE_IGNORE_NEW_LINES); $tablas=[]; $tablaActual=null;
  foreach($lineas as $numero=>$linea){
    $linea=trim($linea);
    if($linea==="" || str_starts_with($linea,"#")) continue;
    if(!str_starts_with($linea,"-")){
      $tablaActual=$linea;
      if(isset($tablas[$tablaActual])) throw new Exception("Tabla duplicada '$tablaActual'");
      $tablas[$tablaActual]=[]; continue;
    }
    if($tablaActual===null) throw new Exception("Campo sin tabla en la línea ".($numero+1));
    $campo=trim(substr($linea,1)); $fk=null;
    if(preg_match('/^(.*?)\s+FK\s+([^\s]+)$/i',$campo,$m)){ $campo=trim($m[1]); $fk=trim($m[2]); }
    $partes=explode(":",$campo,2); $nombre=trim($partes[0]);
    $tipo=isset($partes[1]) ? strtoupper(trim($partes[1])) : "TEXT";
    if($fk!==null && !isset($partes[1])) $tipo="INTEGER";
    $permitidos=["TEXT","INTEGER","REAL","BLOB","NUMERIC"];
    if($nombre==="") throw new Exception("Nombre de campo vacío en la línea ".($numero+1));
    if(strtolower($nombre)==="identificador") throw new Exception("Identificador se crea automáticamente");
    if(!in_array($tipo,$permitidos,true)) throw new Exception("Tipo '$tipo' no permitido en la línea ".($numero+1));
    if(isset($tablas[$tablaActual][$nombre])) throw new Exception("Campo duplicado '$nombre' en '$tablaActual'");
    $tablas[$tablaActual][$nombre]=["tipo"=>$tipo,"fk"=>$fk];
  }
  foreach($tablas as $tabla=>$campos){
    if(!$campos) throw new Exception("La tabla '$tabla' no tiene campos");
    foreach($campos as $campo=>$datos){
      if($datos["fk"]!==null && !isset($tablas[$datos["fk"]])) throw new Exception("La FK '$tabla.$campo' referencia la tabla inexistente '".$datos["fk"]."'");
    }
  }
  return $tablas;
}

function definicionTabla($campos){
  $d=[identificador("Identificador")." INTEGER PRIMARY KEY AUTOINCREMENT"];
  foreach($campos as $campo=>$datos){
    $x=identificador($campo)." ".$datos["tipo"];
    if($datos["fk"]!==null) $x.=" REFERENCES ".identificador($datos["fk"])."(".identificador("Identificador").")";
    $d[]=$x;
  }
  return implode(", ",$d);
}

function contarRegistros($db){
  $total=0;
  $tablas=$db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
  foreach($tablas as $tabla) $total+=(int)$db->query("SELECT COUNT(*) FROM ".identificador($tabla))->fetchColumn();
  return $total;
}

$modelo=__DIR__."/modelodedatos.md";
$datosDemo=__DIR__."/datosdemostracion.sql";
$carpetaDatos=dirname(__DIR__)."/data";
$baseDatos=$carpetaDatos."/crimson.sqlite";
$error=null; $mensajes=[]; $tablas=[]; $completado=false; $registrosImportados=0;
$contenidoModelo=file_exists($modelo)?file_get_contents($modelo):"";

if($_SERVER["REQUEST_METHOD"]==="POST"){
  try{
    if(!extension_loaded("pdo_sqlite")) throw new Exception("PHP no tiene habilitada la extensión pdo_sqlite");
    $crearSchema=isset($_POST["crear_schema"]); $importarDemo=isset($_POST["importar_demo"]);
    if(!$crearSchema && !$importarDemo) throw new Exception("Selecciona al menos una acción");
    if(!is_dir($carpetaDatos) && !mkdir($carpetaDatos,0775,true)) throw new Exception("No se ha podido crear la carpeta data");

    if($crearSchema){
      if(file_exists($baseDatos)) throw new Exception("La base de datos ya existe. Para recrear el schema elimina crimson.sqlite o utiliza migrar.php.");
      $contenidoModelo=$_POST["modelo"]??"";
      if(trim($contenidoModelo)==="") throw new Exception("El modelo de datos está vacío");
      if(file_put_contents($modelo,$contenidoModelo)===false) throw new Exception("No se ha podido guardar modelodedatos.md");
      $tablas=leerModelo($modelo);
      $creadaEnEsteIntento=true;
      $db=new PDO("sqlite:".$baseDatos); $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); $db->exec("PRAGMA foreign_keys = ON");
      $db->beginTransaction();
      foreach($tablas as $tabla=>$campos){
        $db->exec("CREATE TABLE ".identificador($tabla)." (".definicionTabla($campos).")");
      }
      $db->commit(); $mensajes[]="Schema creado: ".count($tablas)." tablas";
    }else{
      if(!file_exists($baseDatos)) throw new Exception("No existe crimson.sqlite. Debes crear primero el schema o marcar ambas opciones.");
      $db=new PDO("sqlite:".$baseDatos); $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); $db->exec("PRAGMA foreign_keys = ON");
    }

    if($importarDemo){
      if(!file_exists($datosDemo)) throw new Exception("No existe datosdemostracion.sql");
      $antes=contarRegistros($db);
      $sql=file_get_contents($datosDemo);
      if(trim($sql)==="") throw new Exception("datosdemostracion.sql está vacío");
      // El fichero demo ya contiene su propia transacción.
      $db->exec($sql);
      $despues=contarRegistros($db); $registrosImportados=max(0,$despues-$antes);
      $mensajes[]="Datos de demostración importados: ".$registrosImportados." registros nuevos";
    }
    $completado=true;
  }catch(Throwable $e){
    if(isset($db) && $db->inTransaction()) $db->rollBack();
    if(isset($creadaEnEsteIntento) && $creadaEnEsteIntento && !$completado && file_exists($baseDatos)) @unlink($baseDatos);
    $error=$e->getMessage();
  }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Instalación · Crimson ERP</title>
<style>
:root{--color-principal:crimson;--padding:10px;--gap:10px}*{box-sizing:border-box}body{font-family:Ubuntu,sans-serif;max-width:1100px;margin:40px auto;padding:var(--padding);color:#222}h1,h2{color:var(--color-principal)}textarea{width:100%;min-height:420px;padding:var(--padding);font-family:monospace;font-size:13px;resize:vertical;border:1px solid #ccc}button{border:0;background:var(--color-principal);color:white;padding:12px 18px;margin-top:var(--gap);font:inherit;cursor:pointer}.ok,.error,.acciones{padding:14px;margin:var(--gap) 0;border-left:5px solid var(--color-principal);background:#fafafa}.acciones label{display:block;padding:7px 0}.meta{color:#666;font-size:14px}code{background:#eee;padding:2px 5px}a{color:var(--color-principal)}
</style></head><body>
<h1>Instalación de Crimson ERP</h1>
<?php if($error!==null){ ?><div class="error"><strong>Error</strong><p><?= escapar($error) ?></p></div><?php } ?>
<?php if($completado){ ?><div class="ok"><strong>Operación completada</strong><?php foreach($mensajes as $m){ ?><p><?= escapar($m) ?></p><?php } ?><p>Base de datos: <code><?= escapar($baseDatos) ?></code></p></div><p><a href="../index.html">Abrir Crimson ERP</a></p><?php }else{ ?>
<p>El instalador puede crear el schema desde <code>modelodedatos.md</code>, importar <code>datosdemostracion.sql</code>, o ejecutar ambas operaciones.</p>
<p class="meta">Estado actual: <?= file_exists($baseDatos)?"crimson.sqlite existe":"crimson.sqlite todavía no existe" ?>.</p>
<form method="post">
<div class="acciones">
<label><input type="checkbox" name="crear_schema" value="1" <?= file_exists($baseDatos)?"":"checked" ?>> Crear schema / base de datos</label>
<label><input type="checkbox" name="importar_demo" value="1" checked> Importar datos de demostración</label>
</div>
<h2>Modelo de datos</h2><textarea name="modelo"><?= escapar($contenidoModelo) ?></textarea>
<button type="submit">Ejecutar instalación</button>
</form>
<?php } ?></body></html>
