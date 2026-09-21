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
$baseDatos = dirname(__DIR__) . "/data/crimson.sqlite";

function leerSQLite($db){
  $tablas = [];

  $nombres = $db->query("
    SELECT name
    FROM sqlite_master
    WHERE type='table'
    AND name NOT LIKE 'sqlite_%'
    ORDER BY name
  ")->fetchAll(PDO::FETCH_COLUMN);

  foreach($nombres as $tabla){
    $tablas[$tabla] = [];

    $columnas = $db->query(
      "PRAGMA table_info(" . identificador($tabla) . ")"
    )->fetchAll(PDO::FETCH_ASSOC);

    $foraneas = $db->query(
      "PRAGMA foreign_key_list(" . identificador($tabla) . ")"
    )->fetchAll(PDO::FETCH_ASSOC);

    $mapaFK = [];

    foreach($foraneas as $fk){
      $mapaFK[$fk["from"]] = $fk["table"];
    }

    foreach($columnas as $columna){
      $tablas[$tabla][$columna["name"]] = [
        "tipo" => strtoupper($columna["type"] ?: "TEXT"),
        "pk" => (int)$columna["pk"],
        "fk" => $mapaFK[$columna["name"]] ?? null
      ];
    }
  }

  return $tablas;
}

function comparar($modelo, $actual){
  $cambios = [];

  foreach($modelo as $tabla => $campos){
    if(!isset($actual[$tabla])){
      $cambios[] = [
        "tipo" => "crear_tabla",
        "tabla" => $tabla,
        "destructivo" => false
      ];
      continue;
    }

    if(
      !isset($actual[$tabla]["Identificador"]) ||
      strtoupper($actual[$tabla]["Identificador"]["tipo"]) != "INTEGER" ||
      $actual[$tabla]["Identificador"]["pk"] != 1
    ){
      $cambios[] = [
        "tipo" => "reconstruir_identificador",
        "tabla" => $tabla,
        "destructivo" => true
      ];
    }

    foreach($campos as $campo => $datos){
      if(!isset($actual[$tabla][$campo])){
        $cambios[] = [
          "tipo" => "anadir_campo",
          "tabla" => $tabla,
          "campo" => $campo,
          "datos" => $datos,
          "destructivo" => false
        ];
        continue;
      }

      $existente = $actual[$tabla][$campo];

      if(
        strtoupper($existente["tipo"]) != strtoupper($datos["tipo"]) ||
        $existente["fk"] != $datos["fk"]
      ){
        $cambios[] = [
          "tipo" => "modificar_campo",
          "tabla" => $tabla,
          "campo" => $campo,
          "desde" => $existente,
          "hasta" => $datos,
          "destructivo" => true
        ];
      }
    }

    foreach($actual[$tabla] as $campo => $datos){
      if($campo == "Identificador"){
        continue;
      }

      if(!isset($campos[$campo])){
        $cambios[] = [
          "tipo" => "eliminar_campo",
          "tabla" => $tabla,
          "campo" => $campo,
          "destructivo" => true
        ];
      }
    }
  }

  foreach($actual as $tabla => $campos){
    if(!isset($modelo[$tabla])){
      $cambios[] = [
        "tipo" => "eliminar_tabla",
        "tabla" => $tabla,
        "destructivo" => true
      ];
    }
  }

  return $cambios;
}

function describirCambio($cambio){
  switch($cambio["tipo"]){
    case "crear_tabla":
      return "Crear tabla '" . $cambio["tabla"] .
             "' con Identificador INTEGER PRIMARY KEY AUTOINCREMENT";

    case "anadir_campo":
      $texto = "Añadir campo '" . $cambio["tabla"] . "." .
               $cambio["campo"] . "' (" . $cambio["datos"]["tipo"] . ")";

      if($cambio["datos"]["fk"] !== null){
        $texto .= " → FK " . $cambio["datos"]["fk"] . ".Identificador";
      }

      return $texto;

    case "modificar_campo":
      $texto = "Modificar campo '" . $cambio["tabla"] . "." .
               $cambio["campo"] . "'";

      if($cambio["desde"]["tipo"] != $cambio["hasta"]["tipo"]){
        $texto .= " · tipo " . $cambio["desde"]["tipo"] .
                  " → " . $cambio["hasta"]["tipo"];
      }

      if($cambio["desde"]["fk"] != $cambio["hasta"]["fk"]){
        $texto .= " · FK " .
          ($cambio["desde"]["fk"] ?? "ninguna") . " → " .
          ($cambio["hasta"]["fk"] ?? "ninguna");
      }

      return $texto;

    case "eliminar_campo":
      return "Eliminar campo '" . $cambio["tabla"] . "." .
             $cambio["campo"] . "'";

    case "eliminar_tabla":
      return "Eliminar tabla '" . $cambio["tabla"] . "'";

    case "reconstruir_identificador":
      return "Reconstruir '" . $cambio["tabla"] .
             "' para incorporar Identificador INTEGER PRIMARY KEY AUTOINCREMENT";
  }

  return "Cambio desconocido";
}

function crearTabla($db, $tabla, $campos){
  $db->exec(
    "CREATE TABLE " . identificador($tabla) .
    " (" . definicionTabla($campos) . ")"
  );
}

function reconstruirTabla($db, $tabla, $modelo, $actual){
  $temporal = "__migracion_" . preg_replace('/[^a-zA-Z0-9_]/', '_', $tabla);

  $db->exec("DROP TABLE IF EXISTS " . identificador($temporal));

  $db->exec(
    "CREATE TABLE " . identificador($temporal) .
    " (" . definicionTabla($modelo) . ")"
  );

  $destino = [];
  $origen = [];

  /*
    Conservamos Identificador si ya existe y es utilizable.
    Si no existía, SQLite generará uno nuevo automáticamente.
  */
  if(isset($actual["Identificador"])){
    $destino[] = identificador("Identificador");
    $origen[] = identificador("Identificador");
  }

  foreach($modelo as $campo => $datos){
    if(isset($actual[$campo])){
      $destino[] = identificador($campo);

      if(strtoupper($actual[$campo]["tipo"]) != strtoupper($datos["tipo"])){
        $origen[] =
          "CAST(" . identificador($campo) . " AS " . $datos["tipo"] . ")";
      }else{
        $origen[] = identificador($campo);
      }
    }
  }

  if(count($destino) > 0){
    $db->exec(
      "INSERT INTO " . identificador($temporal) .
      " (" . implode(", ", $destino) . ")" .
      " SELECT " . implode(", ", $origen) .
      " FROM " . identificador($tabla)
    );
  }

  $db->exec("DROP TABLE " . identificador($tabla));

  $db->exec(
    "ALTER TABLE " . identificador($temporal) .
    " RENAME TO " . identificador($tabla)
  );
}

$error = null;
$cambios = [];
$aplicados = [];
$modeloDatos = [];
$actualDatos = [];
$requiereConfirmacion = false;

try{
  if(!extension_loaded("pdo_sqlite")){
    throw new Exception("PHP no tiene habilitada la extensión pdo_sqlite");
  }

  if(!file_exists($baseDatos)){
    throw new Exception("No existe la base de datos. Ejecuta primero instalar.php");
  }

  $modeloDatos = leerModelo($modelo);

  $db = new PDO("sqlite:" . $baseDatos);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $db->exec("PRAGMA foreign_keys = ON");

  $actualDatos = leerSQLite($db);
  $cambios = comparar($modeloDatos, $actualDatos);

  foreach($cambios as $cambio){
    if($cambio["destructivo"]){
      $requiereConfirmacion = true;
    }
  }

  if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["ejecutar"])){
    if($requiereConfirmacion && !isset($_POST["confirmar_destructivos"])){
      throw new Exception(
        "Hay cambios destructivos. Debes confirmar que deseas continuar."
      );
    }

    /*
      Copia automática antes de cualquier migración.
    */
    $copia = $baseDatos . ".backup-" . date("Ymd-His");

    if(!copy($baseDatos, $copia)){
      throw new Exception("No se ha podido crear la copia de seguridad");
    }

    $db->exec("PRAGMA foreign_keys = OFF");
    $db->beginTransaction();

    /*
      Tablas completamente nuevas.
    */
    foreach($cambios as $cambio){
      if($cambio["tipo"] == "crear_tabla"){
        crearTabla(
          $db,
          $cambio["tabla"],
          $modeloDatos[$cambio["tabla"]]
        );

        $aplicados[] = describirCambio($cambio);
      }
    }

    /*
      ALTER TABLE ADD COLUMN solamente cuando no hay FK.
      SQLite no permite todas las modificaciones directamente,
      por eso las tablas con FK o cambios estructurales se reconstruyen.
    */
    $reconstruir = [];

    foreach($cambios as $cambio){
      if($cambio["tipo"] == "anadir_campo"){
        if($cambio["datos"]["fk"] === null){
          $db->exec(
            "ALTER TABLE " . identificador($cambio["tabla"]) .
            " ADD COLUMN " . identificador($cambio["campo"]) .
            " " . $cambio["datos"]["tipo"]
          );

          $aplicados[] = describirCambio($cambio);
        }else{
          $reconstruir[$cambio["tabla"]] = true;
        }
      }

      if(
        $cambio["tipo"] == "modificar_campo" ||
        $cambio["tipo"] == "eliminar_campo" ||
        $cambio["tipo"] == "reconstruir_identificador"
      ){
        $reconstruir[$cambio["tabla"]] = true;
      }
    }

    foreach(array_keys($reconstruir) as $tabla){
      reconstruirTabla(
        $db,
        $tabla,
        $modeloDatos[$tabla],
        $actualDatos[$tabla]
      );

      $aplicados[] = "Reconstruida tabla '$tabla'";
    }

    foreach($cambios as $cambio){
      if($cambio["tipo"] == "eliminar_tabla"){
        $db->exec("DROP TABLE " . identificador($cambio["tabla"]));
        $aplicados[] = describirCambio($cambio);
      }
    }

    $db->commit();
    $db->exec("PRAGMA foreign_keys = ON");

    $erroresFK = $db->query("PRAGMA foreign_key_check")
                    ->fetchAll(PDO::FETCH_ASSOC);

    if(count($erroresFK) > 0){
      throw new Exception(
        "La migración produjo referencias FK no válidas. " .
        "Se conserva la copia de seguridad: $copia"
      );
    }

    $actualDatos = leerSQLite($db);
    $cambios = comparar($modeloDatos, $actualDatos);

    if(count($cambios) > 0){
      throw new Exception(
        "La migración terminó pero todavía existen diferencias de esquema"
      );
    }

    $aplicados[] = "Copia de seguridad: $copia";
  }

}catch(Exception $errorCapturado){
  if(isset($db) && $db->inTransaction()){
    $db->rollBack();
  }

  if(isset($db)){
    try{$db->exec("PRAGMA foreign_keys = ON");}catch(Exception $ignorar){}
  }

  $error = $errorCapturado->getMessage();
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Migración · Crimson ERP</title>
  <style>
    body{font-family:sans-serif;max-width:1100px;margin:40px auto;padding:20px}
    h1,h2{color:mediumseagreen}
    table{width:100%;border-collapse:collapse;margin:15px 0 30px}
    th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left}
    th{background:mediumseagreen;color:white}
    .ok{border-left:5px solid mediumseagreen;padding:10px 20px;margin:20px 0}
    .error{border-left:5px solid crimson;padding:10px 20px}
    .aviso{border-left:5px solid darkorange;padding:10px 20px;margin:20px 0}
    .destructivo{color:crimson;font-weight:bold}
    .seguro{color:seagreen}
    button{background:mediumseagreen;color:white;border:0;padding:12px 20px;cursor:pointer}
    label{display:block;margin:20px 0}
    code{background:#eee;padding:2px 5px}
    a{color:mediumseagreen}
  </style>
</head>
<body>
  <h1>Migración de Crimson ERP</h1>

  <?php if($error !== null){ ?>
    <div class="error">
      <strong>Se ha producido un error:</strong>
      <p><?= escapar($error) ?></p>
    </div>
  <?php } ?>

  <?php if(count($aplicados) > 0){ ?>
    <div class="ok">
      <strong>Cambios aplicados</strong>
      <?php foreach($aplicados as $aplicado){ ?>
        <p><?= escapar($aplicado) ?></p>
      <?php } ?>
    </div>
  <?php } ?>

  <?php if(count($cambios) == 0 && $error === null){ ?>
    <div class="ok">
      <strong>La base de datos coincide con el modelo.</strong>
    </div>
  <?php }elseif(count($cambios) > 0){ ?>

    <h2>Cambios detectados</h2>

    <table>
      <tr>
        <th>Operación</th>
        <th>Riesgo</th>
      </tr>

      <?php foreach($cambios as $cambio){ ?>
        <tr>
          <td><?= escapar(describirCambio($cambio)) ?></td>
          <td class="<?= $cambio["destructivo"] ? "destructivo" : "seguro" ?>">
            <?= $cambio["destructivo"] ? "Destructivo" : "Seguro" ?>
          </td>
        </tr>
      <?php } ?>
    </table>

    <?php if($requiereConfirmacion){ ?>
      <div class="aviso">
        Algunos cambios requieren reconstruir o eliminar estructuras.
        Se realizará automáticamente una copia de seguridad de SQLite
        antes de modificar la base de datos.
      </div>
    <?php } ?>

    <form method="post">
      <?php if($requiereConfirmacion){ ?>
        <label>
          <input type="checkbox" name="confirmar_destructivos" value="1">
          Entiendo que algunos cambios pueden eliminar o transformar datos.
        </label>
      <?php } ?>

      <button name="ejecutar" value="1">
        Ejecutar migración
      </button>
    </form>
  <?php } ?>

  <h2>Modelo esperado</h2>

  <?php foreach($modeloDatos as $tabla => $campos){ ?>
    <table>
      <tr>
        <th colspan="4"><?= escapar($tabla) ?></th>
      </tr>
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
          <td>
            <?= $datos["fk"] !== null
              ? escapar($datos["fk"] . ".Identificador")
              : "-" ?>
          </td>
        </tr>
      <?php } ?>
    </table>
  <?php } ?>

  <p><a href="../index.html">Volver a Crimson ERP</a></p>
</body>
</html>
