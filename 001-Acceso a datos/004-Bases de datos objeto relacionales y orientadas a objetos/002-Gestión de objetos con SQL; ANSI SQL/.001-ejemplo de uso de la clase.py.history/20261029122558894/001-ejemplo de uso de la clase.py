from gestor import GestorYAML

gestor = GestorYAML()

# Insertar objetos
id1 = gestor.guardar("empresa", "clientes", {
    "nombre": "Ana",
    "edad": 28,
    "direccion": {
        "ciudad": "Valencia",
        "codigo_postal": "46001"
    },
    "telefonos": ["600123456", "600654321"]
})

id2 = gestor.guardar("empresa", "clientes", {
    "nombre": "Luis",
    "edad": 35
})

gestor.guardar("empresa", "productos", {
    "nombre": "Portátil",
    "precio": 899.99
})

# Listar clientes
print(gestor.listar("empresa", "clientes"))

# Buscar por identificador
print(gestor.buscar("empresa", "clientes", id1))

# Eliminar
gestor.eliminar("empresa", "clientes", id2)