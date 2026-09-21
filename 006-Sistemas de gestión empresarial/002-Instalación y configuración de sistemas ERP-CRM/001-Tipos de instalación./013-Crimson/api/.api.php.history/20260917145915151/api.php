<?php
	header("Content-Type: application/json; charset=utf-8");

	try{
		$basededatos = dirname(__DIR__)."/data/crimson.sqlite";

		if(!file_exists($basededatos)){
			throw new Exception("No existe la base de datos SQLite");
		}

		$db = new PDO("sqlite:".$basededatos);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->exec("PRAGMA foreign_keys = ON");

		switch($_GET['bloque'] ?? ''){
			case "menu":
				/*
					Equivalente en SQLite a SHOW TABLES.
					Excluimos las tablas internas de SQLite.
				*/
				$consulta = $db->query("
					SELECT name
					FROM sqlite_master
					WHERE type = 'table'
					AND name NOT LIKE 'sqlite_%'
					ORDER BY name
				");

				echo json_encode(
					$consulta->fetchAll(PDO::FETCH_COLUMN),
					JSON_UNESCAPED_UNICODE
				);
				break;

			case "tabla":
				echo json_encode([
					"productos" => [
						[
							"id" => 1,
							"nombre" => "Portátil ProBook 15",
							"categoria" => "Informática",
							"precio" => 749.99,
							"stock" => 12
						],
						[
							"id" => 2,
							"nombre" => "Monitor UltraView 27",
							"categoria" => "Informática",
							"precio" => 229.90,
							"stock" => 25
						],
						[
							"id" => 3,
							"nombre" => "Teclado Mecánico K500",
							"categoria" => "Periféricos",
							"precio" => 79.95,
							"stock" => 40
						]
					]
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