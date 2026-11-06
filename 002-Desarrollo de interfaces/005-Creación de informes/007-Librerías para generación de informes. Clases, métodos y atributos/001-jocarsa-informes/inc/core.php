<?php
declare(strict_types=1);
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    if (!extension_loaded('pdo_sqlite')) throw new RuntimeException('Instala php-sqlite3 y reinicia Apache.');
    $dir = dirname(__DIR__) . '/data';
    if (!is_dir($dir)) mkdir($dir, 0770, true);
    $pdo = new PDO('sqlite:' . $dir . '/informes.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000; PRAGMA journal_mode=WAL');
    $pdo->exec('CREATE TABLE IF NOT EXISTS usuarios (id INTEGER PRIMARY KEY, usuario TEXT UNIQUE NOT NULL, nombre TEXT NOT NULL, password_hash TEXT NOT NULL, rol TEXT NOT NULL CHECK(rol IN (\'superadmin\',\'admin\',\'usuario\')), activo INTEGER NOT NULL DEFAULT 1, creado TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS cuentas (id INTEGER PRIMARY KEY, nombre TEXT NOT NULL, activo INTEGER NOT NULL DEFAULT 1, creado TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS membresias (usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE, cuenta_id INTEGER NOT NULL REFERENCES cuentas(id) ON DELETE CASCADE, PRIMARY KEY(usuario_id,cuenta_id))');
    $pdo->exec('CREATE TABLE IF NOT EXISTS apis (id INTEGER PRIMARY KEY, cuenta_id INTEGER NOT NULL REFERENCES cuentas(id), usuario_id INTEGER NOT NULL REFERENCES usuarios(id), nombre TEXT NOT NULL, slug TEXT NOT NULL UNIQUE, key_hash TEXT NOT NULL, key_prefix TEXT NOT NULL, activo INTEGER NOT NULL DEFAULT 1, creado TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS solicitudes (id INTEGER PRIMARY KEY, api_id INTEGER NOT NULL REFERENCES apis(id), fecha TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, estado INTEGER NOT NULL, duracion_ms INTEGER NOT NULL, bytes_entrada INTEGER NOT NULL, bytes_salida INTEGER NOT NULL, error TEXT)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_solicitudes_api_fecha ON solicitudes(api_id,fecha)');
    $count=(int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    if (!$count) {
        $stmt=$pdo->prepare('INSERT INTO usuarios(usuario,nombre,password_hash,rol) VALUES (?,?,?,?)');
        $stmt->execute(['jocarsa','Superadministrador',password_hash('jocarsa',PASSWORD_DEFAULT),'superadmin']);
    }
    return $pdo;
}
function h(mixed $s): string {return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function session_start_safe(): void {
    if(session_status()!==PHP_SESSION_ACTIVE){
        session_name('jocarsa_informes');
        session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off']);
        session_start();
    }
}
function me(): ?array {
    session_start_safe();
    if(empty($_SESSION['uid'])) return null;
    $q=db()->prepare('SELECT id,usuario,nombre,rol,activo FROM usuarios WHERE id=?');
    $q->execute([$_SESSION['uid']]); $u=$q->fetch();return $u && $u['activo'] ? $u : null;
}
function require_login(): array { $u=me();if(!$u){header('Location: login.php');exit;}return $u;}
function csrf(): string {session_start_safe();return $_SESSION['csrf']??=bin2hex(random_bytes(24));}
function verify_csrf(): void {if(!hash_equals(csrf(),(string)($_POST['csrf']??''))) throw new RuntimeException('Token CSRF inválido.');}
function is_super(array $u): bool {return $u['rol']==='superadmin';}
function can_account(array $u,int $account): bool {
    if(is_super($u))return true;
    $q=db()->prepare('SELECT 1 FROM membresias WHERE usuario_id=? AND cuenta_id=?');$q->execute([$u['id'],$account]);return (bool)$q->fetchColumn();
}
function accounts(array $u): array {
    if(is_super($u))return db()->query('SELECT * FROM cuentas ORDER BY nombre')->fetchAll();
    $q=db()->prepare('SELECT c.* FROM cuentas c JOIN membresias m ON m.cuenta_id=c.id WHERE m.usuario_id=? ORDER BY c.nombre');$q->execute([$u['id']]);return $q->fetchAll();
}
function api_list(array $u): array {
    $sql='SELECT a.*,c.nombre cuenta,u.usuario creador,(SELECT COUNT(*) FROM solicitudes s WHERE s.api_id=a.id) llamadas FROM apis a JOIN cuentas c ON c.id=a.cuenta_id JOIN usuarios u ON u.id=a.usuario_id';
    if(is_super($u))return db()->query($sql.' ORDER BY a.id DESC')->fetchAll();
    $q=db()->prepare($sql.' WHERE a.cuenta_id IN (SELECT cuenta_id FROM membresias WHERE usuario_id=?) ORDER BY a.id DESC');$q->execute([$u['id']]);return $q->fetchAll();
}
function post_action(array $u): ?string {
    if($_SERVER['REQUEST_METHOD']!=='POST')return null;
    verify_csrf();$action=(string)($_POST['action']??'');$db=db();
    if($action==='cuenta') {
        if(!is_super($u))throw new RuntimeException('Acceso denegado.');
        $name=trim((string)($_POST['nombre']??''));if($name===''||strlen($name)>240)throw new RuntimeException('Nombre inválido.');
        $q=$db->prepare('INSERT INTO cuentas(nombre) VALUES (?)');$q->execute([$name]);return 'Cuenta creada.';
    }
    if($action==='usuario') {
        if(!is_super($u))throw new RuntimeException('Acceso denegado.');
        $username=trim((string)($_POST['usuario']??''));$pass=(string)($_POST['password']??'');$name=trim((string)($_POST['nombre']??''));$role=(string)($_POST['rol']??'usuario');$account=(int)($_POST['cuenta_id']??0);
        if(!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/',$username)||strlen($pass)<8||!in_array($role,['usuario','admin'],true)||$name==='')throw new RuntimeException('Datos de usuario inválidos (contraseña mínimo 8 caracteres).');
        if($account && !(bool)$db->query('SELECT 1 FROM cuentas WHERE id='.$account)->fetchColumn())throw new RuntimeException('Cuenta inexistente.');
        $db->beginTransaction();try{$q=$db->prepare('INSERT INTO usuarios(usuario,nombre,password_hash,rol) VALUES(?,?,?,?)');$q->execute([$username,$name,password_hash($pass,PASSWORD_DEFAULT),$role]);$id=(int)$db->lastInsertId();if($account){$q=$db->prepare('INSERT INTO membresias(usuario_id,cuenta_id) VALUES(?,?)');$q->execute([$id,$account]);}$db->commit();}catch(Throwable $e){$db->rollBack();throw $e;}return 'Usuario creado.';
    }
    if($action==='api') {
        $account=(int)($_POST['cuenta_id']??0);if(!can_account($u,$account)||$account<1)throw new RuntimeException('Cuenta no autorizada.');
        $name=trim((string)($_POST['nombre']??''));$slug=trim((string)($_POST['slug']??''));if(!$name||!preg_match('/^[a-z0-9][a-z0-9-]{2,63}$/',$slug))throw new RuntimeException('Nombre o identificador de API inválido.');
        $key='jca_'.bin2hex(random_bytes(32));$q=$db->prepare('INSERT INTO apis(cuenta_id,usuario_id,nombre,slug,key_hash,key_prefix) VALUES(?,?,?,?,?,?)');$q->execute([$account,$u['id'],$name,$slug,hash('sha256',$key),substr($key,0,12)]);
        $_SESSION['new_key']=$key;return 'API creada. Copia la clave: solo se mostrará una vez.';
    }
    if($action==='toggle_api') {
        $id=(int)($_POST['api_id']??0);$q=$db->prepare('SELECT cuenta_id FROM apis WHERE id=?');$q->execute([$id]);$a=$q->fetch();if(!$a||!can_account($u,(int)$a['cuenta_id']))throw new RuntimeException('Acceso denegado.');
        $q=$db->prepare('UPDATE apis SET activo=CASE activo WHEN 1 THEN 0 ELSE 1 END WHERE id=?');$q->execute([$id]);return 'Estado actualizado.';
    }
    if($action==='password') {
        $old=(string)($_POST['actual']??'');$new=(string)($_POST['nueva']??'');$q=$db->prepare('SELECT password_hash FROM usuarios WHERE id=?');$q->execute([$u['id']]);if(!password_verify($old,$q->fetchColumn())||strlen($new)<12)throw new RuntimeException('Contraseña actual incorrecta o nueva demasiado corta (mínimo 12).');
        $q=$db->prepare('UPDATE usuarios SET password_hash=? WHERE id=?');$q->execute([password_hash($new,PASSWORD_DEFAULT),$u['id']]);return 'Contraseña cambiada.';
    }
    throw new RuntimeException('Acción desconocida.');
}
