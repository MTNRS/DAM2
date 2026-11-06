from __future__ import annotations
import json, sqlite3, shutil, hashlib, mimetypes, html
from pathlib import Path
from datetime import datetime, timezone

class MotorDocumental:
    def __init__(self, raiz='jocarsa-documental'):
        self.raiz = Path(raiz).expanduser().resolve()
        self.raiz.mkdir(parents=True, exist_ok=True)
        self.db = sqlite3.connect(self.raiz / 'documental.db')
        self.db.row_factory = sqlite3.Row
        self.db.execute('PRAGMA foreign_keys=ON')
        self.db.executescript('''
        CREATE TABLE IF NOT EXISTS documentos (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          ruta TEXT NOT NULL UNIQUE,
          tipo TEXT NOT NULL,
          descripcion TEXT NOT NULL DEFAULT '',
          creado TEXT NOT NULL,
          actualizado TEXT NOT NULL
        );
        CREATE INDEX IF NOT EXISTS idx_documentos_tipo ON documentos(tipo);
        ''')
        self.db.commit()
        self.sincronizar()

    def cerrar(self): self.db.close()
    def _ahora(self): return datetime.now(timezone.utc).isoformat(timespec='seconds')
    def _ruta(self, ruta):
        p = (self.raiz / str(ruta)).resolve()
        if not p.is_relative_to(self.raiz): raise ValueError('Ruta fuera de la base documental')
        if p == self.raiz / 'documental.db': raise ValueError('Archivo interno protegido')
        return p
    def _rel(self, p): return p.relative_to(self.raiz).as_posix()
    def _tipo(self, p):
        return {'.json':'json','.md':'markdown','.html':'html','.htm':'html'}.get(p.suffix.lower(), 'externo')
    def _registrar(self, p, descripcion='', actualizar=False):
        rel = self._rel(p)
        ahora = self._ahora()
        self.db.execute('''INSERT INTO documentos(ruta,tipo,descripcion,creado,actualizado)
          VALUES(?,?,?,?,?) ON CONFLICT(ruta) DO UPDATE SET tipo=excluded.tipo,
          actualizado=CASE WHEN ? THEN excluded.actualizado ELSE documentos.actualizado END''',
          (rel, self._tipo(p), descripcion, ahora, ahora, int(actualizar)))
        self.db.commit()
    def sincronizar(self):
        reales = set()
        for p in self.raiz.rglob('*'):
            if p.is_file() and not p.is_symlink() and p.name not in ('documental.db','documental.db-wal','documental.db-shm'):
                reales.add(self._rel(p))
                self._registrar(p)
        for fila in self.db.execute('SELECT id,ruta FROM documentos').fetchall():
            if fila['ruta'] not in reales:
                self.db.execute('DELETE FROM documentos WHERE id=?', (fila['id'],))
        self.db.commit()
    def crear_carpeta(self, ruta):
        p = self._ruta(ruta)
        if p.exists(): raise FileExistsError('La carpeta ya existe')
        p.mkdir(parents=True)
        return self._rel(p)
    def crear(self, ruta, contenido='', descripcion=''):
        p = self._ruta(ruta)
        if p.suffix.lower() not in ('.json','.md','.html'): raise ValueError('Usa .json, .md o .html; para otros formatos utiliza importar')
        if not p.parent.is_dir(): raise FileNotFoundError('La carpeta de destino no existe')
        if p.exists(): raise FileExistsError('El documento ya existe')
        if p.suffix.lower()=='.json': contenido=json.dumps(json.loads(contenido),ensure_ascii=False,indent=2)
        p.write_text(contenido, encoding='utf-8')
        self._registrar(p, descripcion, True)
        return self._rel(p)
    def importar(self, origen, carpeta='', nombre=None):
        src=Path(origen).expanduser().resolve(strict=True)
        if not src.is_file(): raise ValueError('El origen no es un archivo')
        destino=self._ruta(str(Path(carpeta)/ (nombre or src.name)))
        if not destino.parent.is_dir(): raise FileNotFoundError('La carpeta de destino no existe')
        if destino.exists(): raise FileExistsError('El archivo de destino ya existe')
        shutil.copy2(src,destino)
        self._registrar(destino,actualizar=True)
        return self._rel(destino)
    def _fila(self, identificador):
        fila=self.db.execute('SELECT * FROM documentos WHERE id=?',(identificador,)).fetchone()
        if fila is None: raise KeyError('No existe ese identificador')
        return fila
    def leer(self, identificador):
        fila=self._fila(identificador)
        p=self._ruta(fila['ruta'])
        datos=dict(fila)
        datos.update(nombre=p.name,tamano=p.stat().st_size,mime=mimetypes.guess_type(p.name)[0] or 'application/octet-stream')
        if fila['tipo'] in ('json','markdown','html'):
            datos['contenido']=p.read_text(encoding='utf-8')
        else:
            datos['contenido']='[Archivo binario: utilizar exportar para obtenerlo]'
        return datos
    def actualizar(self, identificador, contenido=None, descripcion=None):
        fila=self._fila(identificador)
        p=self._ruta(fila['ruta'])
        if contenido is not None:
            if fila['tipo']=='externo': raise ValueError('No se edita contenido binario; importa un nuevo archivo')
            if fila['tipo']=='json': contenido=json.dumps(json.loads(contenido),ensure_ascii=False,indent=2)
            p.write_text(contenido,encoding='utf-8')
        self.db.execute('UPDATE documentos SET descripcion=?,actualizado=? WHERE id=?',
                        (fila['descripcion'] if descripcion is None else descripcion,self._ahora(),identificador))
        self.db.commit()
    def eliminar(self, identificador):
        fila=self._fila(identificador)
        self._ruta(fila['ruta']).unlink()
        self.db.execute('DELETE FROM documentos WHERE id=?',(identificador,))
        self.db.commit()
    def eliminar_carpeta(self, ruta):
        p=self._ruta(ruta)
        if p==self.raiz: raise ValueError('No se puede eliminar la raíz')
        if not p.is_dir(): raise FileNotFoundError('Carpeta inexistente')
        p.rmdir()  # Solo carpetas vacías
    def listar(self, carpeta=''):
        p=self._ruta(carpeta)
        if not p.is_dir(): raise FileNotFoundError('Carpeta inexistente')
        return [{'nombre':x.name,'tipo':'carpeta' if x.is_dir() else self._tipo(x),'ruta':self._rel(x)}
                for x in sorted(p.iterdir()) if x.name not in ('documental.db','documental.db-wal','documental.db-shm')]
    def buscar(self, texto='', tipo=None, contenido=False):
        filas=self.db.execute('SELECT * FROM documentos WHERE (? IS NULL OR tipo=?) ORDER BY ruta',(tipo,tipo)).fetchall()
        salida=[]
        for f in filas:
            if not texto or texto.casefold() in (f['ruta']+' '+f['descripcion']).casefold():
                salida.append(dict(f)); continue
            if contenido and f['tipo'] in ('json','markdown','html'):
                try:
                    if texto.casefold() in self._ruta(f['ruta']).read_text(encoding='utf-8').casefold(): salida.append(dict(f))
                except (OSError,UnicodeError): pass
        return salida
    def mover(self, identificador, nueva_ruta):
        fila=self._fila(identificador)
        origen=self._ruta(fila['ruta']); destino=self._ruta(nueva_ruta)
        if not destino.parent.is_dir(): raise FileNotFoundError('Carpeta de destino inexistente')
        if destino.exists(): raise FileExistsError('Ya existe el destino')
        origen.rename(destino)
        self.db.execute('UPDATE documentos SET ruta=?,tipo=?,actualizado=? WHERE id=?',
                        (self._rel(destino),self._tipo(destino),self._ahora(),identificador))
        self.db.commit()
    def copiar(self, identificador, nueva_ruta):
        fila=self._fila(identificador)
        origen=self._ruta(fila['ruta']); destino=self._ruta(nueva_ruta)
        if not destino.parent.is_dir(): raise FileNotFoundError('Carpeta de destino inexistente')
        if destino.exists(): raise FileExistsError('Ya existe el destino')
        shutil.copy2(origen,destino)
        self._registrar(destino,fila['descripcion'],True)
    def exportar(self, identificador, destino):
        fila=self._fila(identificador)
        p=Path(destino).expanduser().resolve()
        if p.exists(): raise FileExistsError('El destino ya existe')
        if not p.parent.is_dir(): raise FileNotFoundError('Directorio de exportación inexistente')
        shutil.copy2(self._ruta(fila['ruta']),p)
        return str(p)
    def estadisticas(self):
        self.sincronizar()
        filas=self.db.execute('SELECT tipo,COUNT(*) cantidad FROM documentos GROUP BY tipo').fetchall()
        total=sum(p.stat().st_size for p in self.raiz.rglob('*') if p.is_file() and p.name not in ('documental.db','documental.db-wal','documental.db-shm'))
        return {'documentos':sum(f['cantidad'] for f in filas),'por_tipo':{f['tipo']:f['cantidad'] for f in filas},'bytes':total}

def pedir_multilinea():
    print('Escribe el contenido. Termina con una línea que contenga solo FIN:')
    lineas=[]
    while (linea:=input())!='FIN': lineas.append(linea)
    return '\n'.join(lineas)

def ejecutar():
    motor=MotorDocumental()
    opciones='''\n=== JOCARSA | DOCUMENTAL ===
1. Crear carpeta
2. Crear documento JSON, Markdown o HTML
3. Importar archivo externo
4. Listar carpeta
5. Leer documento
6. Buscar documentos
7. Actualizar documento
8. Eliminar documento
9. Mover documento
10. Copiar documento
11. Exportar documento
12. Estadísticas
13. Sincronizar archivos del disco
14. Eliminar carpeta vacía
0. Salir'''
    try:
        while True:
            print(opciones)
            op=input('Opción: ').strip()
            if op=='0': break
            try:
                if op=='1': print('Creada:',motor.crear_carpeta(input('Ruta de carpeta nueva: ')))
                elif op=='2':
                    ruta=input('Ruta y nombre con extensión (.json/.md/.html): ')
                    contenido=pedir_multilinea()
                    print('Creado:',motor.crear(ruta,contenido,input('Descripción (opcional): ')))
                elif op=='3': print('Importado:',motor.importar(input('Ruta de origen: '),input('Carpeta de destino (vacía = raíz): ')))
                elif op=='4':
                    for x in motor.listar(input('Carpeta (vacía = raíz): ')): print(x)
                elif op=='5': print(json.dumps(motor.leer(int(input('ID: '))),ensure_ascii=False,indent=2))
                elif op=='6':
                    texto=input('Buscar texto: '); tipo=input('Tipo (json/markdown/html/externo o vacío): ') or None
                    en_contenido=input('¿Buscar también dentro de documentos de texto? (s/n): ').lower()=='s'
                    for x in motor.buscar(texto,tipo,en_contenido): print(f"{x['id']:>5} | {x['tipo']:<9} | {x['ruta']}")
                elif op=='7':
                    i=int(input('ID: ')); c=input('¿Editar contenido? (s/n): ').lower()=='s'
                    d=input('Nueva descripción (vacía = conservar): ')
                    motor.actualizar(i,pedir_multilinea() if c else None,d if d else None)
                    print('Actualizado')
                elif op=='8':
                    i=int(input('ID: ')); print(motor.leer(i)['ruta'])
                    if input('Escribe ELIMINAR para confirmar: ')=='ELIMINAR': motor.eliminar(i); print('Eliminado')
                elif op=='9': motor.mover(int(input('ID: ')),input('Nueva ruta completa: ')); print('Movido')
                elif op=='10': motor.copiar(int(input('ID: ')),input('Ruta de la copia: ')); print('Copiado')
                elif op=='11': print('Exportado:',motor.exportar(int(input('ID: ')),input('Ruta de exportación: ')))
                elif op=='12': print(json.dumps(motor.estadisticas(),ensure_ascii=False,indent=2))
                elif op=='13': motor.sincronizar(); print('Sincronización terminada')
                elif op=='14': motor.eliminar_carpeta(input('Carpeta vacía a eliminar: ')); print('Carpeta eliminada')
                else: print('Opción desconocida')
            except (OSError,ValueError,KeyError,sqlite3.Error,json.JSONDecodeError) as e:
                print('Error:',e)
    finally: motor.cerrar()

if __name__=='__main__': ejecutar()
