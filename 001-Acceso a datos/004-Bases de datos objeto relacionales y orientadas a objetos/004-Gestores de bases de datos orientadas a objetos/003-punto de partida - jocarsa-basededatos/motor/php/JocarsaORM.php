<?php
declare(strict_types=1);
class JocarsaORM {
    private FFI $ffi;
    private FFI\CData $handle;
    public function __construct(string $base, string $config='config.json', string $lib='libjocarsaorm.so') {
        $this->ffi=FFI::cdef(<<<'C'
void* jocarsa_orm_abrir(const char*,const char*);
void jocarsa_orm_cerrar(void*);
const char* jocarsa_orm_error(void);
void jocarsa_orm_liberar(char*);
long long jocarsa_orm_guardar(void*,const char*,const char*);
char* jocarsa_orm_obtener(void*,const char*,long long);
char* jocarsa_orm_todos(void*,const char*);
char* jocarsa_orm_buscar(void*,const char*,const char*,const char*);
int jocarsa_orm_actualizar(void*,const char*,long long,const char*);
int jocarsa_orm_eliminar(void*,const char*,long long);
long long jocarsa_orm_contar(void*,const char*);
int jocarsa_coleccion_crear(void*,const char*);
int jocarsa_coleccion_eliminar(void*,const char*);
int jocarsa_coleccion_agregar(void*,const char*,const char*);
int jocarsa_coleccion_quitar(void*,const char*,const char*);
char* jocarsa_coleccion_listar(void*);
char* jocarsa_coleccion_tablas(void*,const char*);
char* jocarsa_tabla_colecciones(void*,const char*);
C, $lib);
        $this->handle=$this->ffi->jocarsa_orm_abrir($config,$base);
        if(FFI::isNull($this->handle))throw new RuntimeException($this->error());
    }
    private function error():string{return FFI::string($this->ffi->jocarsa_orm_error());}
    private function texto($ptr):mixed {
        if($ptr===null || FFI::isNull($ptr))throw new RuntimeException($this->error());
        try{return json_decode(FFI::string($ptr),true,512,JSON_THROW_ON_ERROR);}
        finally{$this->ffi->jocarsa_orm_liberar($ptr);}
    }
    private function json(mixed $v):string{return json_encode($v,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);}
    public function guardar(string $c,mixed $v):int{$r=$this->ffi->jocarsa_orm_guardar($this->handle,$c,$this->json($v));if($r<0)throw new RuntimeException($this->error());return $r;}
    public function obtener(string $c,int $id):mixed{return $this->texto($this->ffi->jocarsa_orm_obtener($this->handle,$c,$id));}
    public function todos(string $c):array{return $this->texto($this->ffi->jocarsa_orm_todos($this->handle,$c));}
    public function buscar(string $c,string $campo,mixed $valor):array{return $this->texto($this->ffi->jocarsa_orm_buscar($this->handle,$c,$campo,$this->json($valor)));}
    public function actualizar(string $c,int $id,mixed $v):bool{$r=$this->ffi->jocarsa_orm_actualizar($this->handle,$c,$id,$this->json($v));if($r<0)throw new RuntimeException($this->error());return (bool)$r;}
    public function eliminar(string $c,int $id):bool{$r=$this->ffi->jocarsa_orm_eliminar($this->handle,$c,$id);if($r<0)throw new RuntimeException($this->error());return (bool)$r;}
    public function contar(string $c):int{$r=$this->ffi->jocarsa_orm_contar($this->handle,$c);if($r<0)throw new RuntimeException($this->error());return $r;}
    private function coleccionBool(string $fn,string ...$args):bool{
        $r=$this->ffi->$fn($this->handle,...$args);
        if($r<0)throw new RuntimeException($this->error());
        return (bool)$r;
    }
    public function crearColeccion(string $n):bool{return $this->coleccionBool('jocarsa_coleccion_crear',$n);}
    public function eliminarColeccion(string $n):bool{return $this->coleccionBool('jocarsa_coleccion_eliminar',$n);}
    public function agregarTablaColeccion(string $tabla,string $coleccion):bool{return $this->coleccionBool('jocarsa_coleccion_agregar',$coleccion,$tabla);}
    public function quitarTablaColeccion(string $tabla,string $coleccion):bool{return $this->coleccionBool('jocarsa_coleccion_quitar',$coleccion,$tabla);}
    public function listarColecciones():array{return $this->texto($this->ffi->jocarsa_coleccion_listar($this->handle));}
    public function tablasDeColeccion(string $n):array{return $this->texto($this->ffi->jocarsa_coleccion_tablas($this->handle,$n));}
    public function coleccionesDeTabla(string $tabla):array{return $this->texto($this->ffi->jocarsa_tabla_colecciones($this->handle,$tabla));}
    public function crearSubcoleccion(string $padre,string $nombre):bool{return $this->coleccionBool('jocarsa_coleccion_subcrear',$padre,$nombre);}
    public function moverColeccion(string $nombre,string $padre=''):bool{return $this->coleccionBool('jocarsa_coleccion_mover',$nombre,$padre);}
    public function subcoleccionesDe(string $nombre):array{return $this->texto($this->ffi->jocarsa_coleccion_hijas($this->handle,$nombre));}
    public function tablasDeColeccionRecursiva(string $nombre):array{return $this->texto($this->ffi->jocarsa_coleccion_recursivo($this->handle,$nombre));}
    public function arbolColecciones():array{return $this->texto($this->ffi->jocarsa_coleccion_arbol($this->handle));}
    public function __destruct(){if(isset($this->handle)&&!FFI::isNull($this->handle))$this->ffi->jocarsa_orm_cerrar($this->handle);}
}
