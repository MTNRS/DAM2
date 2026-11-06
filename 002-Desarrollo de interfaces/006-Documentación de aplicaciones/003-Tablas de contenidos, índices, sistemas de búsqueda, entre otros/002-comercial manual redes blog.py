#!/usr/bin/env python3
"""Extrae funcionalidades y genera comercial, manual, redes y blog con Ollama.

Ejemplos:
  python3 generar_documentacion.py
  python3 generar_documentacion.py --tipo redes
  python3 generar_documentacion.py --tipo blog
  python3 generar_documentacion.py --rehacer-redaccion
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

EXCLUIR_DIR = {'.git','.get','venv','.venv','env','node_modules','__pycache__','.idea','.vscode','dist','build','.pytest_cache','.mypy_cache','vendor','coverage','.next','.cache','doc','.comercial_ollama'}
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
    """Cliente de API PHP remota, con contrato compatible con Ollama."""
    def __init__(self, url, contexto, timeout, intentos, token):
        self.url = url
        self.contexto = contexto
        self.timeout = timeout
        self.intentos = intentos
        self.token = token
        self.session = requests.Session()
        self.session.headers.update({'Accept': 'application/json'})

    def generar(self, modelo, prompt, salida_tokens=900, json_mode=False):
        payload = {
            'token': self.token, 'modelo': modelo, 'mensaje': prompt,
            'stream': False, 'format': 'json' if json_mode else None,
            'options': {'num_ctx': self.contexto, 'num_predict': salida_tokens,
                        'temperature': 0.15},
        }
        for intento in range(1, self.intentos + 1):
            try:
                respuesta = self.session.post(self.url, json=payload, timeout=(15, self.timeout))
                if respuesta.status_code >= 400:
                    raise RuntimeError(f'HTTP {respuesta.status_code}: {respuesta.text[:700]}')
                try:
                    dato = respuesta.json()
                except ValueError as exc:
                    raise RuntimeError(f'El servidor no devolvió JSON: {respuesta.text[:500]}') from exc
                if not isinstance(dato, dict):
                    raise RuntimeError('Respuesta no es objeto JSON')
                if dato.get('error'):
                    raise RuntimeError(str(dato['error']))
                salida = dato.get('response')
                if salida is None:
                    salida = dato.get('respuesta')
                if isinstance(salida, (dict, list)):
                    salida = json.dumps(salida, ensure_ascii=False)
                if not isinstance(salida, str) or not salida.strip():
                    raise RuntimeError('Respuesta vacía o contrato de API inesperado')
                if dato.get('done_reason') == 'length':
                    raise RuntimeError('Salida truncada por límite de tokens')
                return salida.strip()
            except (requests.RequestException, ValueError, RuntimeError) as exc:
                print(f'    Error intento {intento}/{self.intentos}: {exc}', flush=True)
                if intento == self.intentos:
                    raise
                time.sleep(min(3 * intento, 15))


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
    clave = digest(ollama.url+'\n'+modelo+'\n'+str(ollama.contexto)+'\n'+instrucciones)
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
    clave = digest(ollama.url+modelo+str(ollama.contexto)+prompt)
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
    clave = digest(ollama.url+modelo+str(ollama.contexto)+prompt)
    destino = cache/'redaccion'/f'{clave}.md'
    if destino.exists() and not rehacer:
        return destino.read_text(encoding='utf-8')
    respuesta = ollama.generar(modelo,prompt,salida_tokens=2300)
    guardar_atomico(destino,respuesta+'\n')
    return respuesta


def redactar_manual_modulo(ollama, modelo, producto, modulo, items, cache, rehacer):
    """Manual funcional por módulo, con todas las evidencias disponibles."""
    datos = json.dumps(items, ensure_ascii=False)
    prompt = f'''Eres redactor de manuales para usuarios finales, sin conocimientos técnicos.
Producto: {producto}. Módulo: {modulo}.
Redacta SOLO Markdown, comenzando por ### {modulo}.
Describe todas las funcionalidades distintas respaldadas por los datos, sin omitir ninguna.
Para cada función incluye: **Para qué sirve**, **Cómo utilizarla** (pasos numerados SOLO
cuando la evidencia permita conocer la secuencia real) y **Resultado esperado**.
Si no se conocen los pasos concretos, explica lo que puede hacer el usuario de forma
conceptual, e indica "Los pasos exactos no están documentados en la evidencia disponible".
No inventes nombres de botones, pantallas, menús, permisos, flujos ni resultados.
No incluyas código, rutas, archivos, tablas, endpoints, frameworks, APIs o jerga técnica.
No conviertas funcionalidades inferidas en afirmaciones seguras: identifícalas como
"Pendiente de confirmar" y no des instrucciones operativas para ellas.
Escribe en español claro, dirigiéndote al usuario de tú.
DATOS JSON:\n{datos}'''
    clave = digest(ollama.url + modelo + str(ollama.contexto) + prompt)
    destino = cache / 'manual' / f'{clave}.md'
    if destino.exists() and not rehacer:
        return destino.read_text(encoding='utf-8')
    respuesta = ollama.generar(modelo, prompt, salida_tokens=2400)
    guardar_atomico(destino, respuesta + '\n')
    return respuesta



def resumen_para_publicacion(funciones, max_chars=12500):
    """Resumen compacto del catálogo real, repartido entre módulos."""
    modulos = defaultdict(list)
    for f in funciones:
        if f.get('confianza', '').lower() != 'verificada':
            continue
        modulo = f.get('modulo', '').strip() or 'Otras funcionalidades'
        modulos[modulo].append(f)
    if not modulos:
        return ''
    lineas = []
    for modulo, items in sorted(modulos.items()):
        lineas.append('## ' + modulo)
        for f in items:
            lineas.append('- ' + f.get('funcion', '') + ': ' + f.get('descripcion', ''))
    texto = '\n'.join(lineas)
    if len(texto) <= max_chars:
        return texto
    # Reparto equitativo para no concentrarse únicamente en los primeros módulos.
    lineas = []
    cuota = max(350, max_chars // len(modulos))
    for modulo, items in sorted(modulos.items()):
        bloque = '## ' + modulo + '\n'
        for f in items:
            linea = '- ' + f.get('funcion', '') + ': ' + f.get('descripcion', '') + '\n'
            if len(bloque) + len(linea) > cuota:
                break
            bloque += linea
        lineas.append(bloque)
    return '\n'.join(lineas)[:max_chars]


def redactar_publicacion(ollama, modelo, producto, tipo, catalogo, cache, rehacer):
    if tipo == 'redes':
        instrucciones = """Redacta UN post corporativo para LinkedIn, en español, de 120 a 220 palabras.
Abre con un gancho atractivo y natural. Presenta el producto, el problema al que responde,
3 o 4 funcionalidades reales y el valor práctico para el usuario. Termina con una invitación
natural a conocer el proyecto y entre 3 y 5 hashtags relevantes. Usa párrafos breves.
No inventes enlaces, datos de contacto, cifras, testimonios, lanzamientos ni promesas.
No escribas instrucciones para el redactor ni etiquetas como 'Post para LinkedIn'.
Devuelve solo el texto final en Markdown."""
        limite = 700
    else:
        instrucciones = """Escribe un artículo para el blog corporativo de jocarsa, en español,
de aproximadamente 700 a 1000 palabras si la información disponible lo permite.
Incluye título H1, introducción, contexto o necesidad que aborda el producto,
funcionalidades verificadas organizadas por temas, escenarios prácticos plausibles
expresados como ejemplos (no como hechos comprobados) y conclusión.
Escribe en tono corporativo, cercano y divulgativo, sin tecnicismos de programación.
No inventes cifras, integraciones, testimonios, enlaces, clientes, fechas de lanzamiento,
certificaciones ni funciones no respaldadas por el catálogo. No rellenes con generalidades
para alcanzar la extensión: si hay pocos datos, redacta un artículo más corto.
Devuelve únicamente Markdown listo para publicar."""
        limite = 2500
    prompt = (f'Eres redactor de contenidos de jocarsa. Producto: {producto}.\n'
              + instrucciones + '\nUsa EXCLUSIVAMENTE las funcionalidades verificadas siguientes. '
              + 'No afirmes como reales capacidades no incluidas.\nCATÁLOGO:\n' + catalogo)
    clave = digest(ollama.url + modelo + str(ollama.contexto) + prompt)
    destino = cache / tipo / (clave + '.md')
    if destino.exists() and not rehacer:
        return destino.read_text(encoding='utf-8')
    respuesta = ollama.generar(modelo, prompt, salida_tokens=limite)
    guardar_atomico(destino, respuesta.strip() + '\n')
    return respuesta.strip() + '\n'


def procesar_proyecto(args, raiz, ollama):
    producto = raiz.name
    nombre = re.sub(r'(?i)^jocarsa[-_ ]*', '', producto).replace('-', ' ').replace('_', ' ').strip()
    titulo = 'jocarsa | ' + (nombre or producto)
    trabajo = args.trabajo_base / (producto + '-' + digest(str(raiz))[:8])
    archivos = cargar_directorio(raiz, int(args.max_archivo_mb * 1024 * 1024))
    if not archivos:
        raise ValueError('No hay archivos de texto para analizar.')
    trabajo.mkdir(parents=True, exist_ok=True)
    trabajos = [(archivo, i, len(bloques), bloque)
                for archivo, contenido in archivos
                for bloques in [partir(contenido, args.caracteres_chunk)]
                for i, bloque in enumerate(bloques, 1)]
    print(f'Proyecto: {producto} | archivos: {len(archivos)} | fragmentos: {len(trabajos)}', flush=True)
    funciones, errores = [], []
    for n, (archivo, i, total, bloque) in enumerate(trabajos, 1):
        print(f'[{n}/{len(trabajos)}] {archivo} ({i}/{total})', flush=True)
        try:
            datos = extraer(ollama, args.modelo_analista, archivo, bloque, i, total,
                            trabajo, args.rehacer_extraccion)
            funciones.extend(datos)
        except Exception as exc:
            errores.append({'fase':'extraccion','archivo':archivo,'fragmento':i,'error':str(exc)})
            print(f'    FALLO: {exc}', flush=True)
    funciones = consolidar_local(funciones)
    guardar_atomico(trabajo/'catalogo_funcional.json', json.dumps(funciones,ensure_ascii=False,indent=2))
    if not funciones:
        raise ValueError('No se extrajeron funcionalidades.')
    lotes = grupos_de_funciones(funciones, 8500)
    sintesis, manual = [], []
    for n, (modulo, items) in enumerate(lotes, 1):
        print(f'[Módulo {n}/{len(lotes)}] {modulo}', flush=True)
        if args.tipo in ('todos','ambos','comercial'):
            try:
                sintesis.append('### '+modulo+'\n'+sintetizar_lote(
                    ollama,args.modelo_redactor,modulo,items,trabajo,args.rehacer_redaccion))
            except Exception as exc:
                errores.append({'fase':'sintesis','modulo':modulo,'error':str(exc)})
                sintesis.append('### '+modulo+'\n'+'\n'.join(
                    f"- **{x['funcion']}**: {x['descripcion']} ({x['confianza']})" for x in items))
        if args.tipo in ('todos','ambos','manual'):
            try:
                manual.append(redactar_manual_modulo(
                    ollama,args.modelo_redactor,producto,modulo,items,trabajo,args.rehacer_redaccion))
            except Exception as exc:
                errores.append({'fase':'manual','modulo':modulo,'error':str(exc)})
                manual.append('### '+modulo+'\n\n'+'\n'.join(
                    f"#### {x['funcion']}\n\n{x['descripcion']}\n\n"
                    'Los pasos exactos no están documentados en la evidencia disponible.\n'
                    for x in items))
    salidas = []
    if args.tipo in ('todos','ambos','comercial'):
        catalogo = '\n\n'.join(sintesis)
        guardar_atomico(trabajo/'catalogo_consolidado.md', '# Catálogo funcional: '+producto+'\n\n'+catalogo+'\n')
        mini = '\n'.join(s[:1100] for s in sintesis)[:11000]
        titulos = ['Presentación del producto','Problemas que resuelve',
                   'Beneficios para las empresas','Perfiles de usuario y escenarios de uso',
                   'Valor diferencial y conclusión']
        secciones = []
        for seccion in titulos:
            print('[Comercial]',seccion,flush=True)
            try:
                secciones.append(redactar_seccion(
                    ollama,args.modelo_redactor,seccion,{'producto':producto,'texto':mini},
                    trabajo,args.rehacer_redaccion))
            except Exception as exc:
                errores.append({'fase':'comercial','seccion':seccion,'error':str(exc)})
                secciones.append(f'## {seccion}\n\nPendiente de generar.')
        funcionalidades = '## Funcionalidades principales\n\n'+re.sub(
            r'\([^\n]*?(?:evidencia|archivo)[^\n]*?\)','',catalogo,flags=re.I)
        final = ('# '+titulo+'\n\n'+ '\n\n'.join(secciones[:3])+'\n\n'+
                 funcionalidades+'\n\n'+'\n\n'.join(secciones[3:])).strip()+'\n'
        destino = args.salida_dir/'comercial'/(titulo+'.md')
        guardar_atomico(destino,final)
        salidas.append(destino)
    if args.tipo in ('todos','ambos','manual'):
        intro = ('# Manual de usuario — '+titulo+'\n\n'
                 'Este manual describe las funcionalidades identificadas en el producto, '
                 'organizadas por módulos y orientadas a su utilización cotidiana.\n\n'
                 '## Funcionalidades y procedimientos\n\n')
        destino = args.salida_dir/'manual'/(titulo+'.md')
        guardar_atomico(destino,intro+'\n\n'.join(manual).strip()+'\n')
        salidas.append(destino)
    if args.tipo in ('todos', 'redes', 'blog'):
        catalogo_publicaciones = resumen_para_publicacion(funciones)
        if not catalogo_publicaciones:
            errores.append({'fase':'publicaciones','error':'No hay funcionalidades verificadas para redactar publicaciones'})
            print('  OMITIDAS publicaciones: ninguna funcionalidad verificada',flush=True)
        else:
            for tipo in ('redes', 'blog'):
                if args.tipo not in ('todos', tipo):
                    continue
                print('[Publicación]',tipo,flush=True)
                try:
                    contenido = redactar_publicacion(ollama,args.modelo_redactor,titulo,tipo,
                                   catalogo_publicaciones,trabajo,args.rehacer_redaccion)
                    destino = args.salida_dir/tipo/(titulo+'.md')
                    guardar_atomico(destino,contenido)
                    salidas.append(destino)
                except Exception as exc:
                    errores.append({'fase':tipo,'error':str(exc)})
                    print('  FALLO',tipo,exc,flush=True)
    if errores:
        aviso = ('\n---\n\n> **Revisión pendiente:** se produjeron '+str(len(errores))+
                 ' errores durante la generación. Comprueba el registro de trabajo antes de publicar.\n')
        for destino in salidas:
            with destino.open('a',encoding='utf-8') as f:
                f.write(aviso)
    guardar_atomico(trabajo/'errores.json',json.dumps(errores,ensure_ascii=False,indent=2))
    for destino in salidas:
        print('DOCUMENTO:',destino.resolve())
    print('CATÁLOGO:',(trabajo/'catalogo_funcional.json').resolve())
    print('ERRORES:',len(errores))
    return salidas


def main():
    ap = argparse.ArgumentParser(description='Generación de comercial, manual, redes y blog por lotes')
    ap.add_argument('--tipo', choices=('todos','ambos','comercial','manual','redes','blog'), default='todos')
    ap.add_argument('--lista', type=Path, default=Path(__file__).resolve().parent / 'directorios.txt')
    ap.add_argument('--salida-dir', type=Path, default=Path(__file__).resolve().parent / 'doc')
    ap.add_argument('--trabajo-base', type=Path, default=Path(__file__).resolve().parent / '.comercial_ollama')
    ap.add_argument('--modelo-analista', default='qwen2.5-coder:14b')
    ap.add_argument('--modelo-redactor', default='qwen2.5:14b-instruct')
    ap.add_argument('--url', default=os.environ.get('JOCARSA_IA_URL', 'https://works-protection-specifies-bottles.trycloudflare.com/dam/index.php'))
    ap.add_argument('--token', default=os.environ.get('JOCARSA_IA_TOKEN', 'jocarsa_phi4_2026_ClaveSegura_8f3a91b7'), help='Token de la API (preferentemente por variable de entorno)')
    ap.add_argument('--probar-api', action='store_true')
    ap.add_argument('--contexto', type=int, default=16384)
    ap.add_argument('--caracteres-chunk', type=int, default=9000)
    ap.add_argument('--max-archivo-mb', type=float, default=2)
    ap.add_argument('--timeout', type=int, default=600)
    ap.add_argument('--intentos', type=int, default=3)
    ap.add_argument('--rehacer-extraccion', action='store_true')
    ap.add_argument('--rehacer-redaccion', action='store_true')
    args = ap.parse_args()
    if not args.token:
        ap.error('Configura JOCARSA_IA_TOKEN o usa --token')
    if not (args.url.startswith('https://') or args.url.startswith('http://127.0.0.1') or args.url.startswith('http://localhost')):
        ap.error('La API remota debe utilizar HTTPS')
    if args.caracteres_chunk < 1000 or args.contexto < 4096:
        ap.error('Usa al menos 1000 caracteres por chunk y 4096 tokens de contexto')
    ollama = Ollama(args.url, args.contexto, args.timeout, args.intentos, args.token)
    if args.probar_api:
        print('Respuesta de prueba:', ollama.generar(args.modelo_analista, 'Responde exclusivamente OK', 32))
        return
    lista = args.lista.expanduser().resolve()
    if not lista.is_file():
        ap.error(f'No existe el archivo de rutas: {lista}')
    rutas = []
    vistos = set()
    for linea in lista.read_text(encoding='utf-8-sig').splitlines():
        linea = linea.strip()
        if not linea or linea.startswith('#'):
            continue
        raiz = Path(linea).expanduser().resolve()
        if raiz not in vistos:
            rutas.append(raiz)
            vistos.add(raiz)
    if not rutas:
        ap.error('El archivo de rutas está vacío')
    args.salida_dir.mkdir(parents=True, exist_ok=True)
    args.trabajo_base.mkdir(parents=True, exist_ok=True)
    correctos, fallidos = [], []
    for indice, raiz in enumerate(rutas, 1):
        print(f'\n{"="*70}\nPROYECTO {indice}/{len(rutas)}: {raiz}\n{"="*70}', flush=True)
        if not raiz.is_dir():
            print('ERROR: el directorio no existe', flush=True)
            fallidos.append((str(raiz), 'Directorio inexistente'))
            continue
        try:
            salidas = procesar_proyecto(args, raiz, ollama)
            correctos.extend(str(x) for x in salidas)
        except Exception as exc:
            print(f'ERROR DEL PROYECTO: {exc}', flush=True)
            fallidos.append((str(raiz), str(exc)))
    print(f'\nFIN: {len(correctos)} documentos generados; {len(fallidos)} proyectos fallidos.')
    for ruta, error in fallidos:
        print(f'  FALLO {ruta}: {error}')
    if fallidos:
        sys.exit(1)

if __name__ == '__main__':
    main()
