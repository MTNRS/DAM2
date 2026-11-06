from jocarsaorm import JocarsaORM
with JocarsaORM('demo_orm', 'config.json') as orm:
    id = orm.guardar('clientes', {'nombre':'Ana','edad':28,'tags':['vip','web']})
    print('ID:', id)
    print('Obtener:', orm.obtener('clientes', id))
    print('Buscar:', orm.buscar('clientes', 'nombre', 'Ana'))
    print('Actualizar:', orm.actualizar('clientes', id, {'edad':29}))
    print('Todos:', orm.todos('clientes'))
    print('Contar:', orm.contar('clientes'))
    print('Eliminar:', orm.eliminar('clientes', id))
