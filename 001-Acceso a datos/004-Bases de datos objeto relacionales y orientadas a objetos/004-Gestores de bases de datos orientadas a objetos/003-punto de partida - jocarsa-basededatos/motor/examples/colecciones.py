from jocarsaorm import JocarsaORM
with JocarsaORM('erp','config.json') as db:
    for nombre in ['marketing','produccion','administracion','soporte']:
        db.crear_coleccion(nombre)
    print('Colecciones:',db.listar_colecciones())
    print('Para asociar productos, crear primero la tabla productos mediante CLI')
    print('db.agregar_tabla_coleccion("productos", "marketing")')
