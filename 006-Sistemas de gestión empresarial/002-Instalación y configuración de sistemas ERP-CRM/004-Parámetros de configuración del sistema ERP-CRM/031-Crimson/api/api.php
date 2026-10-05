<?php
	header("Content-Type: application/json; charset=utf-8");
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	require_once __DIR__."/auth_common.php";

	function identificador($texto){
		return '"' . str_replace('"', '""', $texto) . '"';
	}

	function comprobarTabla($db,$tabla){
		$consulta = $db->prepare("
			SELECT name
			FROM sqlite_master
			WHERE type = 'table'
			AND name = :tabla
			AND name NOT LIKE 'sqlite_%'
		");

		$consulta->execute([
			":tabla" => $tabla
		]);

		if(!$consulta->fetchColumn()){
			throw new Exception("La tabla '".$tabla."' no existe");
		}
	}

	function estructuraTabla($db,$tabla){
		$estructura = $db->query(
			"PRAGMA table_info(".identificador($tabla).")"
		)->fetchAll(PDO::FETCH_ASSOC);

		$foraneas = $db->query(
			"PRAGMA foreign_key_list(".identificador($tabla).")"
		)->fetchAll(PDO::FETCH_ASSOC);

		foreach($estructura as &$campo){
			$campo["fk"] = null;

			foreach($foraneas as $foranea){
				if($foranea["from"] == $campo["name"]){
					$campo["fk"] = [
						"tabla" => $foranea["table"],
						"campo" => $foranea["to"]
					];
				}
			}
		}

		return $estructura;
	}

	try{
		$basededatos = dirname(__DIR__)."/data/crimson.sqlite";
		$bloque = $_GET["bloque"] ?? "";

		if($bloque == "estado"){
			$instalado = false;

			if(file_exists($basededatos)){
				try{
					$dbEstado = new PDO("sqlite:".$basededatos);
					$dbEstado->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

					$tablasSistema = $dbEstado->query("
						SELECT name
						FROM sqlite_master
						WHERE type='table'
						AND name IN ('__crimson_users','__crimson_permissions')
					")->fetchAll(PDO::FETCH_COLUMN);

					$instalado =
						in_array('__crimson_users',$tablasSistema,true) &&
						in_array('__crimson_permissions',$tablasSistema,true);
				}catch(Throwable $e){
					$instalado = false;
				}
			}

			echo json_encode([
				"instalado" => $instalado
			], JSON_UNESCAPED_UNICODE);
			exit;
		}

		if(!file_exists($basededatos)){
			throw new Exception("No existe la base de datos SQLite");
		}

		$usuarioActual = requireLogin(true);

		$db = new PDO("sqlite:".$basededatos);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->exec("PRAGMA foreign_keys = ON");

		switch($bloque){
			case "modulos":
                echo json_encode(userModules($usuarioActual),JSON_UNESCAPED_UNICODE);
                break;

            case "menu":
				$modulo = $_GET["modulo"] ?? "";

				if($modulo == ""){
					throw new Exception("No se ha indicado ningún módulo");
				}

				// Sólo permitimos nombres de carpeta sencillos.
				if(!preg_match('/^[a-zA-Z0-9_-]+$/',$modulo)){
					throw new Exception("Nombre de módulo no válido");
				}

				$archivo = dirname(__DIR__)."/modulos/".$modulo."/modulo.json";
				if(!is_file($archivo)){
					throw new Exception("No existe la configuración del módulo '".$modulo."'");
				}

				$configuracion = json_decode(file_get_contents($archivo),true);
				if(!is_array($configuracion) || !isset($configuracion["tablas"]) || !is_array($configuracion["tablas"])){
					throw new Exception("El modulo.json de '".$modulo."' no contiene una lista de tablas válida");
				}
                if($usuarioActual["rol"]!=="admin"){
                    $permitidos=array_column(userModules($usuarioActual),"modulo");
                    if(!in_array($modulo,$permitidos,true)){http_response_code(403);throw new Exception("No tienes acceso a este módulo");}
                }

				$tablas = [];
				foreach($configuracion["tablas"] as $tabla){
					$consulta = $db->prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name=:tabla AND name NOT LIKE 'sqlite_%'");
					$consulta->execute([":tabla" => $tabla]);
					if($consulta->fetchColumn()){
						$tablas[] = $tabla;
					}
				}

				echo json_encode($tablas,JSON_UNESCAPED_UNICODE);
				break;

			case "tabla":
				$tabla = $_GET["tabla"] ?? "";

				if($tabla == ""){
					throw new Exception("No se ha indicado ninguna tabla");
				}

				comprobarTabla($db,$tabla);
                if(!permissionForTable($usuarioActual,$tabla,"seleccionar")){http_response_code(403);throw new Exception("Sin permiso de lectura");}

				echo json_encode([
					"tabla" => $tabla,
					"estructura" => estructuraTabla($db,$tabla),
					"contenido" => $db->query(
						"SELECT * FROM ".identificador($tabla)
					)->fetchAll(PDO::FETCH_ASSOC),
                    "permisos" => [
                      "seleccionar"=>permissionForTable($usuarioActual,$tabla,"seleccionar"),
                      "insertar"=>permissionForTable($usuarioActual,$tabla,"insertar"),
                      "actualizar"=>permissionForTable($usuarioActual,$tabla,"actualizar"),
                      "eliminar"=>permissionForTable($usuarioActual,$tabla,"eliminar")
                    ]
				], JSON_UNESCAPED_UNICODE);
				break;

			case "opciones":
				$tabla = $_GET["tabla"] ?? "";

				if($tabla == ""){
					throw new Exception("No se ha indicado ninguna tabla");
				}

				comprobarTabla($db,$tabla);
                if(!permissionForTable($usuarioActual,$tabla,"seleccionar")){http_response_code(403);throw new Exception("Sin permiso de lectura");}

				$estructura = estructuraTabla($db,$tabla);
				$campos = [];

				foreach($estructura as $campo){
					$campos[] = identificador($campo["name"]);
				}

				$contenido = $db->query(
					"SELECT ".implode(",",$campos)." FROM ".identificador($tabla)
				)->fetchAll(PDO::FETCH_ASSOC);

				echo json_encode([
					"tabla" => $tabla,
					"estructura" => $estructura,
					"contenido" => $contenido
				], JSON_UNESCAPED_UNICODE);
				break;

			case "registro":
				$tabla = $_GET["tabla"] ?? "";
				$id = $_GET["id"] ?? "";

				if($tabla == "" || $id == ""){
					throw new Exception("Falta la tabla o el identificador");
				}

				comprobarTabla($db,$tabla);
                if(!permissionForTable($usuarioActual,$tabla,"seleccionar")){http_response_code(403);throw new Exception("Sin permiso de lectura");}

				$consulta = $db->prepare(
					"SELECT * FROM ".identificador($tabla)." WHERE ".identificador("Identificador")." = :id"
				);

				$consulta->execute([
					":id" => $id
				]);

				$registro = $consulta->fetch(PDO::FETCH_ASSOC);

				if(!$registro){
					throw new Exception("El registro no existe");
				}

				echo json_encode([
					"tabla" => $tabla,
					"estructura" => estructuraTabla($db,$tabla),
					"registro" => $registro
				], JSON_UNESCAPED_UNICODE);
				break;

			case "insertar":
				if($_SERVER["REQUEST_METHOD"] != "POST"){
					throw new Exception("La operación insertar requiere POST");
				}

				$datos = json_decode(file_get_contents("php://input"),true);
				$tabla = $datos["tabla"] ?? "";

				comprobarTabla($db,$tabla);
                if(!permissionForTable($usuarioActual,$tabla,"insertar")){http_response_code(403);throw new Exception("Sin permiso para insertar");}

				$estructura = estructuraTabla($db,$tabla);
				$campos = [];
				$valores = [];

				foreach($estructura as $campo){
					if($campo["name"] != "Identificador" && array_key_exists($campo["name"],$datos)){
						$campos[] = identificador($campo["name"]);
						$valores[$campo["name"]] = $datos[$campo["name"]];
					}
				}

				if(count($campos) == 0){
					$db->exec("INSERT INTO ".identificador($tabla)." DEFAULT VALUES");
				}else{
					$marcadores = [];

					foreach($valores as $nombre => $valor){
						$marcadores[] = ":".$nombre;
					}

					$consulta = $db->prepare(
						"INSERT INTO ".identificador($tabla).
						" (".implode(",",$campos).") VALUES (".implode(",",$marcadores).")"
					);

					$consulta->execute($valores);
				}

				echo json_encode([
					"ok" => true,
					"Identificador" => $db->lastInsertId()
				], JSON_UNESCAPED_UNICODE);
				break;

			case "actualizar":
				if($_SERVER["REQUEST_METHOD"] != "POST"){
					throw new Exception("La operación actualizar requiere POST");
				}

				$datos = json_decode(file_get_contents("php://input"),true);
				$tabla = $datos["tabla"] ?? "";
				$id = $datos["Identificador"] ?? "";

				if($id == ""){
					throw new Exception("No se ha indicado Identificador");
				}

				comprobarTabla($db,$tabla);
                if(!permissionForTable($usuarioActual,$tabla,"actualizar")){http_response_code(403);throw new Exception("Sin permiso para actualizar");}

				$estructura = estructuraTabla($db,$tabla);
				$asignaciones = [];
				$valores = [];

				foreach($estructura as $campo){
					if($campo["name"] != "Identificador" && array_key_exists($campo["name"],$datos)){
						$asignaciones[] = identificador($campo["name"])." = :".$campo["name"];
						$valores[$campo["name"]] = $datos[$campo["name"]];
					}
				}

				if(count($asignaciones) == 0){
					throw new Exception("No hay campos para actualizar");
				}

				$valores["Identificador"] = $id;

				$consulta = $db->prepare(
					"UPDATE ".identificador($tabla).
					" SET ".implode(",",$asignaciones).
					" WHERE ".identificador("Identificador")." = :Identificador"
				);

				$consulta->execute($valores);

				echo json_encode([
					"ok" => true
				], JSON_UNESCAPED_UNICODE);
				break;

			case "eliminar":
				if($_SERVER["REQUEST_METHOD"] != "POST"){
					throw new Exception("La operación eliminar requiere POST");
				}

				$datos = json_decode(file_get_contents("php://input"),true);
				$tabla = $datos["tabla"] ?? "";
				$id = $datos["Identificador"] ?? "";

				if($id == ""){
					throw new Exception("No se ha indicado Identificador");
				}

				comprobarTabla($db,$tabla);
                if(!permissionForTable($usuarioActual,$tabla,"eliminar")){http_response_code(403);throw new Exception("Sin permiso para eliminar");}

				$consulta = $db->prepare(
					"DELETE FROM ".identificador($tabla).
					" WHERE ".identificador("Identificador")." = :Identificador"
				);

				$consulta->execute([
					":Identificador" => $id
				]);

				echo json_encode([
					"ok" => true
				], JSON_UNESCAPED_UNICODE);
				break;

			default:
				http_response_code(400);

				echo json_encode([
					"error" => "Bloque no válido"
				], JSON_UNESCAPED_UNICODE);
		}

	}catch(Exception $error){
		http_response_code(500);

		echo json_encode([
			"error" => "Se ha producido un error",
			"debug" => $error->getMessage()
		], JSON_UNESCAPED_UNICODE);
	}
?>