<?php
/** Instalador CLI, no accesible por web. Uso: php backend/instalar.php --usuario=admin --nombre="Administrador" --email=admin@example.com */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo CLI'); }
require_once __DIR__.'/nucleo/JocarsaDB.php';
$steps=[];
function paso(string $name, callable $fn): void {
    global $steps;
    try { $detail=$fn(); $steps[]=['paso'=>$name,'estado'=>'OK','detalle'=>$detail]; echo "[OK] $name".($detail ? ": $detail" : '').PHP_EOL; }
    catch(Throwable $e){ $steps[]=['paso'=>$name,'estado'=>'ERROR','detalle'=>$e->getMessage()]; fwrite(STDERR,"[ERROR] $name: {$e->getMessage()}\n"); informe(1); }
}
function informe(int $status): never {
    global $steps;
    echo json_encode(['resultado'=>$status===0?'OK':'ERROR','pasos'=>$steps],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL;
    exit($status);
}
$options=getopt('',['usuario:','nombre:','email:','sin-admin','ayuda']);
if(isset($options['ayuda'])) {echo "CRIMSON_ADMIN_PASSWORD='...' php backend/instalar.php --usuario=admin --nombre='Administrador' --email=admin@example.com\n";exit(0);}
$db=null;
paso('Extensión PHP',function(){
    if(!extension_loaded('jocarsabbdd')) throw new RuntimeException('No está cargada jocarsabbdd.so en PHP CLI');
    $required=['jocarsabbdd_listar_bases','jocarsabbdd_crear_base','jocarsabbdd_usar_base','jocarsabbdd_crear_tabla_tipada','jocarsabbdd_insertar_columnas','jocarsabbdd_listar','jocarsabbdd_seleccionar','jocarsabbdd_eliminar'];
    $missing=array_values(array_filter($required,fn($x)=>!function_exists($x)));
    if($missing)throw new RuntimeException('Funciones ausentes: '.implode(', ',$missing));
    return 'API básica disponible';
});
paso('Base de datos',function()use(&$db){
    $db=JocarsaDB::instance(false);
    $db->createDatabase();
    return JocarsaDB::BASE;
});
paso('Tablas de autenticación',function()use(&$db){
    $db->createSystemTables();
    return 'crimson_users, crimson_permissions';
});
$extra=[
 'crimson_ui_config'=>[['entidad','TEXT'],['tipo','TEXT'],['contenido','TEXT']],
 'crimson_ui_values'=>[['entidad','TEXT'],['registro','TEXT'],['campo','TEXT'],['valor','TEXT']],
 'crimson_api_endpoints'=>[['entidad','TEXT'],['ruta','TEXT'],['activo','INTEGER'],['campos','TEXT'],['metodos','TEXT'],['key_hash','TEXT'],['key_prefix','TEXT'],['creado','TEXT']],
 'crimson_api_logs'=>[['ruta','TEXT'],['metodo','TEXT'],['estado','INTEGER'],['fecha','TEXT']],
];
paso('Tablas auxiliares',function()use(&$db,$extra){
    foreach($extra as $table=>$fields)if(!$db->tableExists($table))$db->createTable($table,array_map(fn($f)=>['name'=>$f[0],'type'=>$f[1]],$fields));
    return implode(', ',array_keys($extra));
});
paso('Configuración inicial',function()use(&$db){
    foreach($db->rows('crimson_ui_config') as $r)if(($r['entidad']??'')==='__erp_global__'&&($r['tipo']??'')==='settings')return 'Ya existe, sin cambios';
    $db->insert('crimson_ui_config',['entidad'=>'__erp_global__','tipo'=>'settings','contenido'=>json_encode(['themeColor'=>'#8b4513','rowsPerPage'=>25])]);
    return 'Valores predeterminados';
});
if(!isset($options['sin-admin']))paso('Administrador inicial',function()use(&$db,$options){
    $existing=$db->rows('crimson_users');
    if($existing)return 'Ya hay usuarios: no se crean ni modifican credenciales';
    $user=trim((string)($options['usuario']??''));$name=trim((string)($options['nombre']??''));$email=trim((string)($options['email']??''));
    $password=getenv('CRIMSON_ADMIN_PASSWORD');
    if(!$user||!$name||!filter_var($email,FILTER_VALIDATE_EMAIL)||!is_string($password)||strlen($password)<12)
        throw new RuntimeException('Indica --usuario, --nombre, --email válido y CRIMSON_ADMIN_PASSWORD de al menos 12 caracteres');
    $id=$db->insert('crimson_users',['username'=>$user,'nombre'=>$name,'email'=>$email,'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'rol'=>'admin','activo'=>1,'creado_en'=>date('Y-m-d H:i:s')]);
    $row=$db->find('crimson_users',$id);
    if(!$row||!password_verify($password,(string)($row['password_hash']??'')))throw new RuntimeException('No se pudo verificar el administrador persistido');
    return 'ID '.$id;
});
paso('Prueba CRUD',function()use(&$db){
    $table='crimson_v2_test';
    if($db->tableExists($table))throw new RuntimeException('Ya existe tabla de prueba; no se altera');
    $db->createTable($table,[['name'=>'mensaje','type'=>'TEXT']]);
    try {
        $id=$db->insert($table,['mensaje'=>'prueba-v2']);
        $r=$db->find($table,$id);
        if(($r['mensaje']??null)!=='prueba-v2')throw new RuntimeException('Falló lectura');
        if(!$db->update($table,$id,['mensaje'=>'actualizado-v2']))throw new RuntimeException('Falló actualización');
        if(($db->find($table,$id)['mensaje']??null)!=='actualizado-v2')throw new RuntimeException('Falló verificación de actualización');
        if(!$db->delete($table,$id)||$db->find($table,$id)!==null)throw new RuntimeException('Falló borrado');
    }finally { $db->dropTable($table); }
    return 'CREATE / INSERT / SELECT / UPDATE / DELETE correctos';
});
paso('Módulos',function()use(&$db){
    if(!function_exists('jocarsabbdd_listar_colecciones'))throw new RuntimeException('El conector no implementa colecciones');
    $collections=$db->collections();
    return $collections?'Colecciones existentes: '.implode(', ',$collections):'Sin colecciones de negocio; importar un modelo para crear módulos';
});
informe(0);
