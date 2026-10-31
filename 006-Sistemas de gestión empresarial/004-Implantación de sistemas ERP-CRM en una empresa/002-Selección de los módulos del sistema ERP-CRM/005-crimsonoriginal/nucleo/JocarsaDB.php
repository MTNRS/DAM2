<?php
/** Crimson ERP -> JocarsaBBDD native PHP adapter. */
final class JocarsaDB {
    public const BASE = 'crimson';
    private static ?self $instance = null;
    private bool $selected = false;

    public static function instance(bool $requireInstalled=false): self {
        if(!extension_loaded('jocarsabbdd')) throw new RuntimeException('La extensión PHP jocarsabbdd no está cargada');
        if(self::$instance===null) self::$instance=new self();
        if($requireInstalled) self::$instance->useBase();
        return self::$instance;
    }
    public static function installed(): bool {
        if(!extension_loaded('jocarsabbdd')) return false;
        try { return in_array(self::BASE, jocarsabbdd_listar_bases(), true); } catch(Throwable $e){ return false; }
    }
    public function createBase(): void {
        if(self::installed()) throw new RuntimeException('Crimson ya está instalado');
        if(!jocarsabbdd_crear_base(self::BASE)) throw new RuntimeException('No se pudo crear la base '.self::BASE);
        $this->useBase();
    }
    public function createDatabase(): bool {
        if(self::installed()) { $this->useBase(); return true; }
        $this->createBase();
        return true;
    }
    public function useDatabase(): bool { $this->useBase(); return true; }
    public function databaseExists(): bool { return self::installed(); }
    public function dropBase(): bool { $this->selected=false; return (bool)jocarsabbdd_eliminar_base(self::BASE); }
    public function useBase(): void {
        if($this->selected) return;
        if(!self::installed()) throw new RuntimeException('Crimson no está instalado');
        if(!jocarsabbdd_usar_base(self::BASE)) throw new RuntimeException('No se pudo seleccionar la base Crimson');
        $this->selected=true;
    }
    public function tables(bool $includeSystem=true): array {
        $this->useBase(); $x=jocarsabbdd_listar_tablas();
        return $includeSystem?$x:array_values(array_filter($x,fn($t)=>!str_starts_with($t,'__crimson_')));
    }
    public function tableExists(string $t): bool { return in_array($t,$this->tables(true),true); }
    private function pc(string $c): string { if($c==='Identificador'||$c==='id')return 'id'; if($c==='activo')return '__crimson_activo'; return $c; }
    private function lc(string $c): ?string { if($c==='id')return 'Identificador'; if($c==='activo')return null; if($c==='__crimson_activo')return 'activo'; return $c; }
    private function pv(mixed $v): mixed { if($v===null)return null; if(is_bool($v))return $v?'1':'0'; return (string)$v; }
    private function row(array $r): array { $o=[]; foreach($r as $k=>$v){$l=$this->lc((string)$k);if($l!==null)$o[$l]=$v;} return $o; }
    public function rows(string $t): array { $this->useBase(); $this->assertTable($t); return array_map(fn($r)=>$this->row($r),jocarsabbdd_listar($t)); }
    public function find(string $t,int|string $id): ?array { $this->useBase(); $this->assertTable($t);$r=jocarsabbdd_seleccionar($t,(int)$id);return $r?$this->row($r):null; }
    public function search(string $t,string $c,mixed $v): array { $this->useBase(); $this->assertTable($t);return array_map(fn($r)=>$this->row($r),jocarsabbdd_buscar($t,$this->pc($c),(string)$this->pv($v))); }
    public function insert(string $t,array $data): int {
        $this->useBase(); $this->assertTable($t);$cols=[];$vals=[];foreach($data as $k=>$v){if($k==='Identificador'||$k==='id')continue;$cols[]=$this->pc($k);$vals[]=$this->pv($v);} 
        return (int)jocarsabbdd_insertar_columnas($t,$cols,$vals);
    }
    public function update(string $t,int|string $id,array $data): bool {
        $this->useBase(); $this->assertTable($t);$d=[];foreach($data as $k=>$v){if($k==='Identificador'||$k==='id')continue;$d[$this->pc($k)]=$this->pv($v);}return jocarsabbdd_actualizar_donde($t,$d,'id',(string)(int)$id)>0;
    }
    public function delete(string $t,int|string $id): bool { $this->useBase(); $this->assertTable($t);return (bool)jocarsabbdd_eliminar($t,(int)$id); }
    public function deleteWhere(string $t,string $c,mixed $v): int {$this->useBase(); return (int)jocarsabbdd_eliminar_donde($t,$this->pc($c),(string)$this->pv($v));}
    public function updateWhere(string $t,array $changes,string $c,mixed $v): int {$this->useBase(); $d=[];foreach($changes as $k=>$x)$d[$this->pc($k)]=$this->pv($x);return (int)jocarsabbdd_actualizar_donde($t,$d,$this->pc($c),(string)$this->pv($v));}
    public function begin(): void { $this->useBase(); if(!jocarsabbdd_iniciar_transaccion())throw new RuntimeException('No se pudo iniciar la transacción'); }
    public function commit(): void { if(!jocarsabbdd_confirmar())throw new RuntimeException('No se pudo confirmar la transacción'); }
    public function rollback(): void { if(jocarsabbdd_en_transaccion())jocarsabbdd_deshacer(); }
    public function inTransaction(): bool { return (bool)jocarsabbdd_en_transaccion(); }
    public function schema(string $t): array {
        $this->useBase(); $this->assertTable($t);$types=jocarsabbdd_tipos($t);$fks=$this->foreignKeys($t);$out=[];$pos=0;
        foreach(jocarsabbdd_esquema($t) as $p){$l=$this->lc($p);if($l===null)continue;$out[]=['cid'=>$pos++,'name'=>$l,'type'=>$this->logicalType($types[$p]??'TEXTO'),'notnull'=>0,'dflt_value'=>null,'pk'=>$p==='id'?1:0,'fk'=>$fks[$l]??null];}
        return $out;
    }
    public function foreignKeys(string $t): array {$this->useBase(); $this->assertTable($t);$o=[];foreach(jocarsabbdd_claves_foraneas($t) as $fk){$from=$this->lc($fk['columna']??'');if($from===null)continue;$o[$from]=['tabla'=>$fk['tabla_referenciada']??($fk['tabla']??''),'campo'=>$this->lc($fk['columna_referenciada']??'id')??'Identificador'];}return $o;}
    public function createTable(string $t,array $columns): void {
        $this->useBase(); $defs=[];foreach($columns as $c){$n=$c['name']??'';if($n===''||$n==='Identificador'||$n==='id')continue;$defs[$this->pc($n)]=$this->engineType($c['type']??'TEXT');}
        if(!jocarsabbdd_crear_tabla_tipada($t,$defs))throw new RuntimeException("No se pudo crear la tabla $t");
    }
    public function addForeignKey(string $t,string $c,string $rt,string $rc='Identificador'): void {$this->useBase(); if(!jocarsabbdd_anadir_clave_foranea($t,$this->pc($c),$rt,$this->pc($rc)))throw new RuntimeException("No se pudo crear FK $t.$c -> $rt.$rc");}
    public function addColumn(string $t,string $c): void {$this->useBase(); if(!jocarsabbdd_alterar_anadir($t,$this->pc($c)))throw new RuntimeException("No se pudo añadir $t.$c");}
    public function dropColumn(string $t,string $c): void {$this->useBase(); if(!jocarsabbdd_alterar_eliminar_columna($t,$this->pc($c)))throw new RuntimeException("No se pudo eliminar $t.$c");}
    public function dropTable(string $t): void {$this->useBase(); if(!jocarsabbdd_eliminar_tabla($t))throw new RuntimeException("No se pudo eliminar $t");}
    public function createSystemTables(): void {
        if(!$this->tableExists('__crimson_users')){$this->createTable('__crimson_users',[
          ['name'=>'username','type'=>'TEXT'],['name'=>'password_hash','type'=>'TEXT'],['name'=>'nombre','type'=>'TEXT'],['name'=>'email','type'=>'TEXT'],['name'=>'rol','type'=>'TEXT'],['name'=>'activo','type'=>'INTEGER'],['name'=>'creado_en','type'=>'TEXT']]);jocarsabbdd_establecer_unico('__crimson_users','username');}
        if(!$this->tableExists('__crimson_permissions')){$this->createTable('__crimson_permissions',[
          ['name'=>'usuario_id','type'=>'INTEGER'],['name'=>'modulo','type'=>'TEXT'],['name'=>'puede_seleccionar','type'=>'INTEGER'],['name'=>'puede_insertar','type'=>'INTEGER'],['name'=>'puede_actualizar','type'=>'INTEGER'],['name'=>'puede_eliminar','type'=>'INTEGER']]);$this->addForeignKey('__crimson_permissions','usuario_id','__crimson_users','Identificador');}
    }
    public function installModel(array $model): void {
        foreach($model['tables']??[] as $t)$this->createTable($t['name'],$t['columns']??[]);
        foreach($model['relations']??[] as $r)$this->addForeignKey($r['fromTable'],$r['fromColumn'],$r['toTable'],$r['toColumn']??'Identificador');
    }
    public function actualModel(): array {
        $tables=[];$relations=[];$x=40;$y=40;foreach($this->tables(false) as $t){$cols=[];foreach($this->schema($t) as $c)$cols[]=['name'=>$c['name'],'type'=>$c['type'],'pk'=>(bool)$c['pk']];$tables[]=['name'=>$t,'x'=>$x,'y'=>$y,'columns'=>$cols];$x+=30;$y+=30;foreach($this->foreignKeys($t) as $c=>$fk)$relations[]=['fromTable'=>$t,'fromColumn'=>$c,'toTable'=>$fk['tabla'],'toColumn'=>$fk['campo']];}return ['version'=>1,'tables'=>$tables,'relations'=>$relations];
    }
    private function assertTable(string $t): void {if(!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/',$t)||!$this->tableExists($t))throw new RuntimeException("La tabla '$t' no existe");}
    private function engineType(string $t): string {$t=strtoupper($t);return match($t){'INTEGER','INT'=>'ENTERO','REAL','FLOAT','DOUBLE'=>'REAL','NUMERIC','DECIMAL'=>'DECIMAL','BOOLEAN','BOOL'=>'BOOLEANO','DATE','FECHA'=>'FECHA',default=>'TEXTO'};}
    private function logicalType(string $t): string {return match(strtoupper($t)){'ENTERO'=>'INTEGER','REAL'=>'REAL','DECIMAL'=>'NUMERIC','BOOLEANO'=>'INTEGER','FECHA'=>'TEXT',default=>'TEXT'};}
}
