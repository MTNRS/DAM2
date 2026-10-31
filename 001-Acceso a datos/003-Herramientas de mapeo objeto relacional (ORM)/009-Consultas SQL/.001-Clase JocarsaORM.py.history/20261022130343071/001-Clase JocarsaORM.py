from jocarsaorm import JocarsaORM

orm = JocarsaORM("curriculums.db")

# Guardar un objeto
id = orm.guardar("curriculum", {
    "nombre": "Ana",
    "edad": 28,
    "habilidades": ["Python", "JavaScript", "SQL"],
    "direccion": {
        "ciudad": "Valencia",
        "pais": "España"
    }
})

# Recuperar un objeto completo
persona = orm.obtener("curriculum", id)

# Recuperar todos
personas = orm.todos("curriculum")

# Modificar
orm.actualizar("curriculum", id, {
    "nombre": "Ana García",
    "edad": 29
})

# Eliminar
orm.eliminar("curriculum", id)

# Cerrar
orm.cerrar()