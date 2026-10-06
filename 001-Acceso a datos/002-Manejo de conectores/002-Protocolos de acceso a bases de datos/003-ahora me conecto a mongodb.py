from pymongo import MongoClient

# Conectar a MongoDB
cliente = MongoClient("mongodb://localhost:27017/")

# Crear/seleccionar base de datos
db = cliente["empresa"]

# Crear/seleccionar colección
clientes = db["clientes"]

# Insertar datos de demostración
clientes.insert_one({
    "nombre": "Ana García",
    "email": "ana@correo.com",
    "telefono": "600111222"
})

clientes.insert_one({
    "nombre": "Juan López",
    "email": "juan@correo.com",
    "telefono": "600333444"
})

clientes.insert_one({
    "nombre": "María Pérez",
    "email": "maria@correo.com",
    "telefono": "600555666"
})

# Mostrar clientes
for cliente in clientes.find():
    print(cliente)

# Cerrar conexión
cliente.close()