from gestor import GestorYAML

bd = GestorYAML()

# Insertar un cliente
id_cliente = bd.guardar("empresa", "clientes", {
    "nombre": "Ana",
    "edad": 28,
    "direccion": {
        "ciudad": "Valencia",
        "codigo_postal": "46001"
    },
    "telefonos": [
        "600123456",
        "600654321"
    ]
})

# Insertar un producto
id_producto = bd.guardar("empresa", "productos", {
    "nombre": "Portátil",
    "precio": 899.99,
    "caracteristicas": {
        "ram": 16,
        "procesador": "Intel i7"
    }
})

# Listar clientes
print(bd.listar("empresa", "clientes"))

# Buscar cliente
print(bd.buscar("empresa", "clientes", id_cliente))

# Actualizar cliente
bd.actualizar("empresa", "clientes", id_cliente, {
    "nombre": "Ana García",
    "edad": 29
})

# Listar bases de datos
print(bd.bases_de_datos())

# Listar colecciones
print(bd.colecciones("empresa"))

# Eliminar producto
bd.eliminar("empresa", "productos", id_producto)