<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Solo CLI');}
require_once __DIR__.'/nucleo/JocarsaDB.php';
if($argc<4){fwrite(STDERR,"Uso: php inicializar-usuarios-cli.php usuario nombre email\nLa contraseña se lee de CRIMSON_ADMIN_PASSWORD\n");exit(1);}
$password=getenv('CRIMSON_ADMIN_PASSWORD');if(!$password||strlen($password)<12){fwrite(STDERR,"Define CRIMSON_ADMIN_PASSWORD con 12 caracteres o más\n");exit(1);}
$db=JocarsaDB::instance(true);$db->createSystemTables();
if($db->rows('crimson_users')){fwrite(STDERR,"Ya existen usuarios. No se modifica ninguno.\n");exit(1);}
$id=$db->insert('crimson_users',['username'=>$argv[1],'nombre'=>$argv[2],'email'=>$argv[3],'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'rol'=>'admin','activo'=>1,'creado_en'=>date('Y-m-d H:i:s')]);
echo "Administrador creado: #$id\n";
