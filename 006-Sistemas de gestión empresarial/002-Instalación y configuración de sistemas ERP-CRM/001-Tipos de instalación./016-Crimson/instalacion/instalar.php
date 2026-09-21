<?php
function escapar($texto){
  return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
}

function identificador($texto){
  return '"' . str_replace('"', '""', $texto) . '"';
}

function leerModelo($archivo){
  if(!file_exists($archivo)){
    throw new Exception("No existe modelodedatos.md");
  }

  $lineas = file($archivo, FILE_IGNORE_NEW_LINES);
  $tablas = [];
  $tablaActual = null;

  foreach($lineas as $numero => $linea){
    $linea = trim($linea);

    if($linea == "" || str_starts_with($linea, "#")){
      continue;
    }

    if(!str_starts_with($linea, "-")){
      $tablaActual = $linea;

      if(isset($tablas[$tablaActual])){
        throw new Exception("Tabla duplicada '$tablaActual'");
      }

      $tablas[$tablaActual] = [];
      continue;
    }

    if($tablaActual === null){
      throw new Exception("Campo sin tabla en la línea " . ($numero + 1));
    }

    $campo = trim(substr($linea, 1));

    /*
      Formatos:
        -nombre
        -precio:REAL
        -cliente FK Clientes
        -cliente:INTEGER FK Clientes
    */
    $fk = null;

    if(preg_match('/^(.*?)\s+FK\s+([^\s]+)$/i', $campo, $coincidencias)){
      $campo = trim($coincidencias[1]);
      $fk = trim($coincidencias[2]);
    }

    $partes = explode(":", $campo, 2);
    $nombre = trim($partes[0]);
    $tipo = isset($partes[1]) ? strtoupper(trim($partes[1])) : "TEXT";

    if($fk !== null && !isset($partes[1])){
      $tipo = "INTEGER";
    }

    $tiposPermitidos = ["TEXT", "INTEGER", "REAL", "BLOB", "NUMERIC"];

    if($nombre == ""){
      throw new Exception("Nombre de campo vacío en la línea " . ($numero + 1));
    }

    if(strtolower($nombre) == "identificador"){
      throw new Exception(
        "No debes declarar Identificador manualmente. Se crea automáticamente."
      );
    }

    if(!in_array($tipo, $tiposPermitidos)){
      throw new Exception("Tipo '$tipo' no permitido en la línea " . ($numero + 1));
    }

    if(isset($tablas[$tablaActual][$nombre])){
      throw new Exception("Campo duplicado '$nombre' en '$tablaActual'");
    }

    $tablas[$tablaActual][$nombre] = [
      "tipo" => $tipo,
      "fk" => $fk
    ];
  }

  foreach($tablas as $tabla => $campos){
    if(count($campos) == 0){
      throw new Exception("La tabla '$tabla' no tiene campos");
    }

    foreach($campos as $campo => $datos){
      if($datos["fk"] !== null && !isset($tablas[$datos["fk"]])){
        throw new Exception(
          "La FK '$tabla.$campo' referencia la tabla inexistente '" .
          $datos["fk"] . "'"
        );
      }
    }
  }

  return $tablas;
}

function definicionTabla($campos){
  $definiciones = [
    identificador("Identificador") . " INTEGER PRIMARY KEY AUTOINCREMENT"
  ];

  foreach($campos as $campo => $datos){
    $definicion = identificador($campo) . " " . $datos["tipo"];

    if($datos["fk"] !== null){
      $definicion .=
        " REFERENCES " . identificador($datos["fk"]) .
        "(" . identificador("Identificador") . ")";
    }

    $definiciones[] = $definicion;
  }

  return implode(", ", $definiciones);
}

$modelo = __DIR__ . "/modelodedatos.md";
$carpetaDatos = dirname(__DIR__) . "/data";
$baseDatos = $carpetaDatos . "/crimson.sqlite";

$error = null;
$tablas = [];
$mensajes = [];
$instalado = false;
$contenidoModelo = file_exists($modelo) ? file_get_contents($modelo) : "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
  try{
    if(!extension_loaded("pdo_sqlite")){
      throw new Exception("PHP no tiene habilitada la extensión pdo_sqlite");
    }

    if(file_exists($baseDatos)){
      throw new Exception(
        "La base de datos ya existe. Utiliza migrar.php para actualizarla."
      );
    }

    $contenidoModelo = $_POST["modelo"] ?? "";

    if(trim($contenidoModelo) == ""){
      throw new Exception("El modelo de datos está vacío");
    }

    if(file_put_contents($modelo,$contenidoModelo) === false){
      throw new Exception("No se ha podido guardar modelodedatos.md");
    }

    $tablas = leerModelo($modelo);

    if(!is_dir($carpetaDatos)){
      if(!mkdir($carpetaDatos,0775,true)){
        throw new Exception("No se ha podido crear la carpeta data");
      }

      $mensajes[] = "Se ha creado la carpeta data";
    }

    $creadaEnEsteIntento = true;
    $db = new PDO("sqlite:".$baseDatos);
    $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    $db->exec("PRAGMA foreign_keys = ON");
    $db->beginTransaction();

    foreach($tablas as $tabla => $campos){
      $sql =
        "CREATE TABLE ".identificador($tabla).
        " (".definicionTabla($campos).")";

      $db->exec($sql);
      $mensajes[] = "Tabla '$tabla' creada";
    }

    $db->commit();
    $instalado = true;

  }catch(Exception $errorCapturado){
    if(isset($db) && $db->inTransaction()){
      $db->rollBack();
    }

    if(isset($db)){
      $db = null;
    }

    /*
      Solo eliminamos una base de datos creada durante este intento.
      Una base ya existente nunca se borra.
    */
    if(isset($creadaEnEsteIntento) && $creadaEnEsteIntento && file_exists($baseDatos)){
      @unlink($baseDatos);
    }

    $error = $errorCapturado->getMessage();
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Instalación · Crimson ERP</title>
  <style>
    :root{
      --color-principal:crimson;
      --padding:10px;
      --gap:10px;
    }

    *{box-sizing:border-box}

    body{
      font-family:Ubuntu,sans-serif;
      max-width:1000px;
      margin:40px auto;
      padding:var(--padding);
    }

    h1,h2{color:var(--color-principal)}

    textarea{
      width:100%;
      min-height:320px;
      padding:var(--padding);
      font-family:monospace;
      font-size:14px;
      resize:vertical;
      border:1px solid #ccc;
    }

    button{
      border:0px;
      background:var(--color-principal);
      color:white;
      padding:var(--padding);
      margin-top:var(--gap);
      font:inherit;
      cursor:pointer;
    }

    table{
      width:100%;
      border-collapse:collapse;
      margin:var(--gap) 0px;
    }

    th,td{
      padding:var(--padding);
      border-bottom:1px solid #ddd;
      text-align:left;
    }

    th{
      background:var(--color-principal);
      color:white;
    }

    .ok,.error{
      border-left:5px solid var(--color-principal);
      padding:var(--padding);
      margin:var(--gap) 0px;
    }

    code{background:#eee;padding:2px 5px}
    a{color:var(--color-principal)}
  </style>
</head>
<body>
  <h1>Instalación de Crimson ERP</h1>

  <?php if(file_exists($baseDatos) && !$instalado){ ?>
    <div class="error">
      <strong>Crimson ERP ya está instalado.</strong>
      <p>La base de datos <code><?= escapar($baseDatos) ?></code> ya existe.</p>
    </div>
    <p><a href="../index.html">Abrir Crimson ERP</a></p>

  <?php }elseif($instalado){ ?>
    <div class="ok">
      <strong>Instalación completada correctamente</strong>
      <p>Base de datos: <code><?= escapar($baseDatos) ?></code></p>
      <p>Tablas creadas: <?= count($tablas) ?></p>
    </div>

    <?php foreach($tablas as $tabla => $campos){ ?>
      <h2><?= escapar($tabla) ?></h2>
      <table>
        <tr>
          <th>Campo</th>
          <th>Tipo</th>
          <th>Clave</th>
          <th>Referencia</th>
        </tr>
        <tr>
          <td>Identificador</td>
          <td>INTEGER</td>
          <td>PRIMARY KEY · AUTOINCREMENT</td>
          <td>-</td>
        </tr>
        <?php foreach($campos as $campo => $datos){ ?>
          <tr>
            <td><?= escapar($campo) ?></td>
            <td><?= escapar($datos["tipo"]) ?></td>
            <td><?= $datos["fk"] !== null ? "FOREIGN KEY" : "-" ?></td>
            <td><?= $datos["fk"] !== null ? escapar($datos["fk"].".Identificador") : "-" ?></td>
          </tr>
        <?php } ?>
      </table>
    <?php } ?>

    <p><a href="../index.html">Abrir Crimson ERP</a></p>

  <?php }else{ ?>
    <?php if($error !== null){ ?>
      <div class="error">
        <strong>Se ha producido un error:</strong>
        <p><?= escapar($error) ?></p>
      </div>
    <?php } ?>

    <p>Revisa o modifica el modelo de datos antes de realizar la instalación.</p>

    <form method="post">
      <textarea name="modelo"><?= escapar($contenidoModelo) ?></textarea>
      <button type="submit">Instalar Crimson ERP</button>
    </form>
  <?php } ?>
</body>
</html>
