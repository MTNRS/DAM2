#!/usr/bin/env python3
"""Extrae funcionalidades de un proyecto y redacta documentación comercial con Ollama.

Ejemplos:
  python3 generar_comercial_ollama.py --directorio /var/www/html/jocarsa-asistencias
  python3 generar_comercial_ollama.py --markdown proyecto.md
  python3 generar_comercial_ollama.py --directorio ./miapp --modelo-redactor qwen2.5-coder:7b
  python3 generar_comercial_ollama.py --markdown proyecto.md --rehacer-redaccion
"""
import argparse
import hashlib
import json
import os
import re
import sys
import time
from collections import defaultdict
from pathlib import Path

try:
    import requests
except ImportError:
    sys.exit('Instala requests: python3 -m pip install requests')

EXCLUIR_DIR = {'.git','.get','venv','.venv','env','node_modules','__pycache__','.idea','.vscode','dist','build','.pytest_cache','.mypy_cache','vendor','coverage','.next','.cache'}
EXCLUIR_ARCHIVOS = {'.env','.env.local','.env.production','.env.development','secrets.json','credentials.json','id_rsa','id_ed25519','audit-hmac.key'}
EXTENSIONES = {'.py','.php','.js','.ts','.jsx','.tsx','.html','.htm','.css','.scss','.json','.xml','.yaml','.yml','.md','.txt','.sql','.sh','.c','.cpp','.h','.hpp','.java','.rb','.go','.rs','.ini','.cfg','.conf','.toml','.gitignore','.htaccess'}
NOMBRES = {'Dockerfile','Makefile','.gitignore','.htaccess'}
IGNORAR_PATRONES = [re.compile(p,re.I) for p in [r'password\s*[:=]',r'api[_-]?key\s*[:=]',r'authorization\s*:',r'private[_-]?key\s*[:=]',r'client[_-]?secret\s*[:=]',r'\btoken\s*[:=]']]


def digest(s):
    return hashlib.sha256(s.encode('utf-8')).hexdigest()[:20]


def guardar_atomico(ruta, contenido):
    ruta.parent.mkdir(parents=True, exist_ok=True)
    tmp = ruta.with_name(ruta.name + '.tmp')
    tmp.write_text(contenido, encoding='utf-8')
    tmp.replace(ruta)


def limpiar_secretos(texto):
    """Redacción conservadora; revisar código antes de enviarlo a un servicio externo."""
    lineas = []
    for linea in texto.splitlines():
        if any(p.search(linea) for p in IGNORAR_PATRONES):
            lineas.append('[LÍNEA OMITIDA: posible secreto]')
        else:
            lineas.append(linea)
    return '\n'.join(lineas)


def cargar_directorio(raiz, limite_bytes):
    archivos = []
    for carpeta, dirs, nombres in os.walk(raiz):
        dirs[:] = sorted(d for d in dirs if d not in EXCLUIR_DIR and not (Path(carpeta)/d).is_symlink())
        for nombre in sorted(nombres):
            ruta = Path(carpeta)/nombre
            if ruta.is_symlink() or nombre in EXCLUIR_ARCHIVOS or (ruta.suffix.lower() not in EXTENSIONES and nombre not in NOMBRES):
                continue
            try:
                if ruta.stat().st_size > limite_bytes:
                    print('  OMITIDO (demasiado grande):', ruta)
                    continue
                texto = ruta.read_text(encoding='utf-8')
                if '\x00' in texto:
                    continue
                archivos.append((str(ruta.relative_to(raiz)), limpiar_secretos(texto)))
            except (OSError, UnicodeError) as exc:
                print('  OMITIDO:', ruta, exc)
    return archivos


def cargar_markdown(ruta):
    """Lee el formato ### ruta + bloque de código generado por el script original."""
    texto = ruta.read_text(encoding='utf-8')
    resultado = []
    patron = re.compile(r'^### (.+?)\s*\n\s*(`{3,})[^\n]*\n(.*?)\n\2\s*$', re.M | re.S)
    # Evitar regex global con .*? que atraviese varios bloques si hay fences internos.
    lineas = texto.splitlines(keepends=True)
    i = 0
    while i < len(lineas):
        m = re.match(r'^### (.+?)\s*$', lineas[i].rstrip('\n\r'))
        if not m:
            i += 1
            continue
        nombre = m.group(1).strip()
        i += 1
        while i < len(lineas) and not lineas[i].strip():
            i += 1
        if i >= len(lineas):
            break
        fm = re.match(r'^(`{3,})[^`\n]*\s*$', lineas[i].rstrip('\n\r'))
        if not fm:
            continue
        fence = fm.group(1)
        i += 1
        contenido = []
        while i < len(lineas) and lineas[i].strip() != fence:
            contenido.append(lineas[i])
            i += 1
        if i < len(lineas):
            i += 1
        if nombre not in EXCLUIR_ARCHIVOS:
            resultado.append((nombre, limpiar_secretos(''.join(contenido))))
    if not resultado:
        raise ValueError('No se encontraron bloques ### archivo + ``` en el Markdown.')
    return resultado


def partir(texto, max_chars):
    """Cortes preferentemente por líneas, preservando todo el contenido."""
    lineas = texto.splitlines(keepends=True)
    bloques, actual, longitud = [], [], 0
    for linea in lineas:
        if len(linea) > max_chars:
            if actual:
                bloques.append(''.join(actual)); actual=[]; longitud=0
            bloques.extend(linea[i:i+max_chars] for i in range(0,len(linea),max_chars))
            continue
        if actual and longitud + len(linea) > max_chars:
            bloques.append(''.join(actual)); actual=[]; longitud=0
        actual.append(linea); longitud += len(linea)
    if actual:
        bloques.append(''.join(actual))
    return bloques


class Ollama:
    def __init__(self, url, contexto, timeout, intentos):
        self.url = url.rstrip('/') + '/api/generate'
        self.contexto = contexto
        self.timeout = timeout
        self.intentos = intentos
        self.session = requests.Session()

    def generar(self, modelo, prompt, salida_tokens=900, json_mode=False):
        payload = {'model': modelo, 'prompt': prompt, 'stream': False,
                   'options': {'num_ctx': self.contexto, 'num_predict': salida_tokens, 'temperature': 0.15}}
        if json_mode:
            payload['format'] = 'json'
        for intento in range(1,self.intentos+1):
            try:
                respuesta = self.session.post(self.url, json=payload, timeout=self.timeout)
                respuesta.raise_for_status()
                dato = respuesta.json()
                if dato.get('error'):
                    raise RuntimeError(dato['error'])
                salida = dato.get('response','').strip()
                if not salida:
                    raise RuntimeError('Respuesta vacía')
                return salida
            except (requests.RequestException, ValueError, RuntimeError) as exc:
                print(f'    Error intento {intento}/{self.intentos}: {exc}', flush=True)
                if intento == self.intentos:
                    raise
                time.sleep(min(3*intento,15))


def leer_json(texto):
    try:
        return json.loads(texto)
    except json.JSONDecodeError:
        a, b = texto.find('{'), texto.rfind('}')
        if a >= 0 and b > a:
            return json.loads(texto[a:b+1])
        raise


def extraer(ollama, modelo, nombre, bloque, indice, total, cache, rehacer):
    instrucciones = f'''Eres auditor funcional de software. Analiza exclusivamente este fragmento real de código.
Archivo: {nombre} (fragmento {indice}/{total}).
Extrae funcionalidades que puedan interesar a clientes; NO redactes publicidad todavía.
No inventes prestaciones. Si solo ves una referencia a una funcionalidad, marca confianza="inferida".
Devuelve exclusivamente un JSON con este esquema:
{{"funcionalidades":[{{"modulo":"nombre de módulo","funcion":"acción o capacidad específica","descripcion":"qué permite hacer","usuarios":"perfil implicado o no determinado","confianza":"verificada o inferida","evidencia":"identificador, ruta, tabla, endpoint o función que lo demuestra"}}]}}
Si no hay funcionalidades, devuelve {{"funcionalidades":[]}}.
Máximo 12 funcionalidades diferentes por fragmento. Usa español. No repitas detalles de implementación irrelevantes.
CÓDIGO:\n{bloque}'''
    clave = digest(modelo+'\n'+str(ollama.contexto)+'\n'+instrucciones)
    destino = cache/'extraccion'/f'{clave}.json'
    if destino.exists() and not rehacer:
        return json.loads(destino.read_text(encoding='utf-8'))
    respuesta = ollama.generar(modelo, instrucciones, salida_tokens=1600, json_mode=True)
    datos = leer_json(respuesta)
    if not isinstance(datos,dict) or not isinstance(datos.get('funcionalidades'),list):
        raise ValueError('JSON sin lista funcionalidades')
    normalizadas = []
    for f in datos['funcionalidades']:
        if not isinstance(f,dict) or not f.get('funcion'):
            continue
        normalizadas.append({k:str(f.get(k,'')) for k in ('modulo','funcion','descripcion','usuarios','confianza','evidencia')} | {'archivo':nombre,'fragmento':indice})
    guardar_atomico(destino,json.dumps(normalizadas,ensure_ascii=False,indent=2))
    return normalizadas


def consolidar_local(funciones):
    """Deduplicación exacta conservadora; la IA consolidará semánticamente por módulos."""
    unicas = {}
    for f in funciones:
        clave = (f['archivo'].casefold(),f['funcion'].strip().casefold(),f['evidencia'].strip().casefold())
        unicas.setdefault(clave,f)
    return list(unicas.values())


def grupos_de_funciones(funciones, max_chars):
    grupos = defaultdict(list)
    for f in funciones:
        grupos[f['modulo'].strip() or 'Otras funcionalidades'].append(f)
    lotes = []
    for modulo, items in sorted(grupos.items()):
        actual, tam = [], 0
        for item in items:
            entrada = json.dumps(item,ensure_ascii=False)
            if actual and tam+len(entrada)>max_chars:
                lotes.append((modulo,actual)); actual=[]; tam=0
            actual.append(item); tam += len(entrada)
        if actual:
            lotes.append((modulo,actual))
    return lotes


def sintetizar_lote(ollama, modelo, modulo, items, cache, rehacer):
    datos = json.dumps(items,ensure_ascii=False)
    prompt = f'''Redacta un resumen funcional comprobable en Markdown para el módulo "{modulo}" a partir de las evidencias JSON siguientes.
Elimina duplicados conceptuales, pero NO omitas capacidades distintas. Cada funcionalidad debe quedar reflejada.
Usa viñetas con **nombre**: descripción clara para un cliente y referencia de evidencia entre paréntesis.
No inventes capacidades ni afirmes cumplimiento legal, certificaciones o integraciones sin evidencia explícita.
Distingue claramente capacidades inferidas o dudosas. No uses lenguaje técnico salvo que sea imprescindible.
Responde solo con Markdown.\nDATOS:\n{datos}'''
    clave = digest(modelo+str(ollama.contexto)+prompt)
    destino = cache/'sintesis'/f'{clave}.md'
    if destino.exists() and not rehacer:
        return destino.read_text(encoding='utf-8')
    respuesta = ollama.generar(modelo,prompt,salida_tokens=1900)
    guardar_atomico(destino,respuesta+'\n')
    return respuesta


def redactar_seccion(ollama, modelo, titulo, catalogo, cache, rehacer):
    prompt = f'''Eres redactor comercial B2B especializado en software empresarial. Escribe la sección "{titulo}" de un dossier comercial de producto.
Producto: {catalogo['producto']}.
Usa EXCLUSIVAMENTE el catálogo funcional aportado. No inventes funcionalidades, resultados cuantificados, integraciones, certificaciones, garantías ni cumplimiento normativo.
Habla de beneficios concretos para empresas, con lenguaje comercial natural y claro. Evita frases genéricas y redundancias.
Si la sección es de funcionalidades, explica con suficiente detalle todas las funciones incluidas en el catálogo.
No menciones archivos, código fuente, nombres de clases ni rutas técnicas en el texto comercial.
No uses encabezado H1; comienza con ## {titulo} y subsecciones ### cuando convenga.
Catálogo de referencia:\n{catalogo['texto']}'''
    clave = digest(modelo+str(ollama.contexto)+prompt)
    destino = cache/'redaccion'/f'{clave}.md'
    if destino.exists() and not rehacer:
        return destino.read_text(encoding='utf-8')
    respuesta = ollama.generar(modelo,prompt,salida_tokens=2300)
    guardar_atomico(destino,respuesta+'\n')
    return respuesta


def main():
    ap = argparse.ArgumentParser(description='Generador comercial con Ollama, sin RAG')
    fuente = ap.add_mutually_exclusive_group(required=True)
    fuente.add_argument('--directorio',type=Path,help='Directorio de proyecto original')
    fuente.add_argument('--markdown',type=Path,help='proyecto.md generado con el script original')
    ap.add_argument('--salida',type=Path,default=Path('jocarsa-asistencias-comercial.md'))
    ap.add_argument('--trabajo',type=Path,default=Path('.comercial_ollama'))
    ap.add_argument('--modelo-analista',default='qwen2.5-coder:7b')
    ap.add_argument('--modelo-redactor',default='granite4.2:latest')
    ap.add_argument('--url',default='http://localhost:11434')
    ap.add_argument('--contexto',type=int,default=16384)
    ap.add_argument('--caracteres-chunk',type=int,default=9000,help='Tamaño conservador: 9000 caracteres, no tokens')
    ap.add_argument('--max-archivo-mb',type=float,default=2)
    ap.add_argument('--timeout',type=int,default=600)
    ap.add_argument('--intentos',type=int,default=3)
    ap.add_argument('--rehacer-extraccion',action='store_true')
    ap.add_argument('--rehacer-redaccion',action='store_true')
    args = ap.parse_args()
    if args.caracteres_chunk < 1000 or args.contexto < 4096:
        ap.error('Usa al menos 1000 caracteres por chunk y 4096 tokens de contexto')
    if args.directorio:
        raiz = args.directorio.expanduser().resolve()
        if not raiz.is_dir():
            ap.error(f'Directorio inexistente: {raiz}')
        archivos = cargar_directorio(raiz,int(args.max_archivo_mb*1024*1024))
        producto = raiz.name
    else:
        ruta = args.markdown.expanduser().resolve()
        if not ruta.is_file():
            ap.error(f'Markdown inexistente: {ruta}')
        archivos = cargar_markdown(ruta)
        producto = re.sub(r'(?i)^proyecto[:\s-]*','',ruta.stem) or ruta.stem
        cabecera = ruta.open(encoding='utf-8').readline().strip()
        if cabecera.startswith('# Proyecto: '):
            producto = cabecera[len('# Proyecto: '):].strip()
    if not archivos:
        sys.exit('No hay archivos de texto para analizar.')
    args.trabajo.mkdir(parents=True,exist_ok=True)
    ollama = Ollama(args.url,args.contexto,args.timeout,args.intentos)
    trabajos = [(nombre,i,len(bloques),bloque) for nombre,contenido in archivos for bloques in [partir(contenido,args.caracteres_chunk)] for i,bloque in enumerate(bloques,1)]
    print(f'Proyecto: {producto} | archivos: {len(archivos)} | fragmentos: {len(trabajos)}',flush=True)
    print(f'Analista: {args.modelo_analista} | Redactor: {args.modelo_redactor}',flush=True)
    funciones = []
    errores = []
    for n,(nombre,i,total,bloque) in enumerate(trabajos,1):
        print(f'[{n}/{len(trabajos)}] {nombre} ({i}/{total})',flush=True)
        try:
            datos = extraer(ollama,args.modelo_analista,nombre,bloque,i,total,args.trabajo,args.rehacer_extraccion)
            funciones.extend(datos)
            print(f'    {len(datos)} funcionalidades',flush=True)
        except Exception as exc:
            errores.append({'archivo':nombre,'fragmento':i,'error':str(exc)})
            print(f'    FALLO: {exc}',flush=True)
    funciones = consolidar_local(funciones)
    guardar_atomico(args.trabajo/'catalogo_funcional.json',json.dumps(funciones,ensure_ascii=False,indent=2))
    guardar_atomico(args.trabajo/'errores.json',json.dumps(errores,ensure_ascii=False,indent=2))
    if not funciones:
        sys.exit('No se extrajeron funcionalidades. Revisa Ollama y errores.json')
    print(f'Funcionalidades extraídas: {len(funciones)} | fragmentos fallidos: {len(errores)}',flush=True)
    lotes = grupos_de_funciones(funciones,9500)
    sintesis = []
    for n,(modulo,items) in enumerate(lotes,1):
        print(f'[Síntesis {n}/{len(lotes)}] {modulo}',flush=True)
        try:
            sintesis.append(f'### {modulo}\n'+sintetizar_lote(ollama,args.modelo_redactor,modulo,items,args.trabajo,args.rehacer_redaccion))
        except Exception as exc:
            errores.append({'fase':'sintesis','modulo':modulo,'error':str(exc)})
            # Fallback sin IA: no perder funcionalidades.
            sintesis.append('### '+modulo+'\n'+'\n'.join(f"- **{x['funcion']}**: {x['descripcion']} (evidencia: {x['archivo']}, {x['evidencia']}; {x['confianza']})" for x in items))
    catalogo = '\n\n'.join(sintesis)
    guardar_atomico(args.trabajo/'catalogo_consolidado.md','# Catálogo funcional: '+producto+'\n\n'+catalogo+'\n')
    # Cada sección se redacta a partir de lotes limitados; evita truncar silenciosamente el catálogo.
    secciones = []
    titulos = ['Presentación del producto','Problemas que resuelve','Beneficios para las empresas','Perfiles de usuario y escenarios de uso','Valor diferencial y conclusión']
    # Secciones generales: resumen de cada módulo, no el catálogo completo si es demasiado largo.
    mini = '\n'.join(s[:1100] for s in sintesis)
    mini = mini[:11000]
    for titulo in titulos:
        print('[Redacción]',titulo,flush=True)
        try:
            secciones.append(redactar_seccion(ollama,args.modelo_redactor,titulo,{'producto':producto,'texto':mini},args.trabajo,args.rehacer_redaccion))
        except Exception as exc:
            errores.append({'fase':'redaccion','seccion':titulo,'error':str(exc)})
            secciones.append(f'## {titulo}\n\nPendiente de generar: {exc}')
    # Funcionalidades: todas las síntesis, para garantizar cobertura, sin depender de una ventana gigante.
    funcionalidades = '## Funcionalidades principales\n\n' + re.sub(r'\([^\n]*?(?:evidencia|archivo)[^\n]*?\)','',catalogo,flags=re.I)
    final = ('# '+producto+'\n\n'+secciones[0]+'\n\n'+secciones[1]+'\n\n'+secciones[2]+'\n\n'+funcionalidades+'\n\n'+secciones[3]+'\n\n'+secciones[4]).strip()+'\n'
    if errores:
        final += '\n---\n\n> **Revisión pendiente:** hubo '+str(len(errores))+' errores de procesamiento. Consulta `'+str(args.trabajo/'errores.json')+'` antes de publicar.\n'
    guardar_atomico(args.salida,final)
    guardar_atomico(args.trabajo/'errores.json',json.dumps(errores,ensure_ascii=False,indent=2))
    print('\nDOCUMENTO:',args.salida.resolve())
    print('CATÁLOGO:',(args.trabajo/'catalogo_funcional.json').resolve())
    print('SÍNTESIS:',(args.trabajo/'catalogo_consolidado.md').resolve())
    print('ERRORES:',len(errores))

if __name__ == '__main__':
    main()
