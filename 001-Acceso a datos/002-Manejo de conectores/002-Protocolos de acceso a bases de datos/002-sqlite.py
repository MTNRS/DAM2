import sqlite3

# Crear/conectar a la base de datos
conexion = sqlite3.connect("empresa.db")
cursor = conexion.cursor()

# Crear tabla
cursor.execute("""
CREATE TABLE IF NOT EXISTS clientes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT,
    email TEXT,
    telefono TEXT
)
""")

# Insertar datos de demostración
cursor.execute("""
INSERT INTO clientes (nombre, email, telefono)
VALUES ('Ana García', 'ana@correo.com', '600111222')
""")

cursor.execute("""
INSERT INTO clientes (nombre, email, telefono)
VALUES ('Juan López', 'juan@correo.com', '600333444')
""")

cursor.execute("""
INSERT INTO clientes (nombre, email, telefono)
VALUES ('María Pérez', 'maria@correo.com', '600555666')
""")

# Guardar cambios
conexion.commit()

# Mostrar clientes
cursor.execute("SELECT * FROM clientes")

for cliente in cursor.fetchall():
    print(cliente)

# Cerrar conexión
conexion.close()