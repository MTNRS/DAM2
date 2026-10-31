import ctypes
import json
import os

class JocarsaORM:
    def __init__(self, base, config='config.json', biblioteca=None):
        ruta = biblioteca or os.environ.get('JOCARSAORM_LIB', 'libjocarsaorm.so')
        self.lib = ctypes.CDLL(ruta)
        L = self.lib
        L.jocarsa_orm_abrir.argtypes = [ctypes.c_char_p, ctypes.c_char_p]
        L.jocarsa_orm_abrir.restype = ctypes.c_void_p
        L.jocarsa_orm_cerrar.argtypes = [ctypes.c_void_p]
        L.jocarsa_orm_error.restype = ctypes.c_char_p
        L.jocarsa_orm_liberar.argtypes = [ctypes.c_void_p]
        for nombre, tipos, retorno in [
            ('guardar',[ctypes.c_char_p,ctypes.c_char_p],ctypes.c_longlong),
            ('obtener',[ctypes.c_char_p,ctypes.c_longlong],ctypes.c_void_p),
            ('todos',[ctypes.c_char_p],ctypes.c_void_p),
            ('buscar',[ctypes.c_char_p,ctypes.c_char_p,ctypes.c_char_p],ctypes.c_void_p),
            ('actualizar',[ctypes.c_char_p,ctypes.c_longlong,ctypes.c_char_p],ctypes.c_int),
            ('eliminar',[ctypes.c_char_p,ctypes.c_longlong],ctypes.c_int),
            ('contar',[ctypes.c_char_p],ctypes.c_longlong),
        ]:
            f=getattr(L,'jocarsa_orm_'+nombre)
            f.argtypes=[ctypes.c_void_p]+tipos
            f.restype=retorno
        self.handle=L.jocarsa_orm_abrir(config.encode(),base.encode())
        if not self.handle: raise RuntimeError(self.error())

    def error(self): return self.lib.jocarsa_orm_error().decode()
    def _call(self,nombre,*args,resultado_json=False):
        result=getattr(self.lib,'jocarsa_orm_'+nombre)(self.handle,*args)
        if result is None or (isinstance(result,int) and result == -1): raise RuntimeError(self.error())
        if resultado_json:
            try: return json.loads(ctypes.string_at(result).decode())
            finally: self.lib.jocarsa_orm_liberar(result)
        return result
    @staticmethod
    def _s(v): return v.encode('utf-8')
    @staticmethod
    def _j(v): return json.dumps(v,ensure_ascii=False).encode('utf-8')
    def guardar(self,c,d): return self._call('guardar',self._s(c),self._j(d))
    def obtener(self,c,id): return self._call('obtener',self._s(c),id,resultado_json=True)
    def todos(self,c): return self._call('todos',self._s(c),resultado_json=True)
    def buscar(self,c,campo,valor): return self._call('buscar',self._s(c),self._s(campo),self._j(valor),resultado_json=True)
    def actualizar(self,c,id,cambios): return bool(self._call('actualizar',self._s(c),id,self._j(cambios)))
    def eliminar(self,c,id): return bool(self._call('eliminar',self._s(c),id))
    def contar(self,c): return self._call('contar',self._s(c))
    def cerrar(self):
        if self.handle: self.lib.jocarsa_orm_cerrar(self.handle); self.handle=None
    def __enter__(self): return self
    def __exit__(self,*args): self.cerrar()
