import jocarsabbdd

jocarsabbdd.crear_base("empresa")

jocarsabbdd.usar_base("empresa")

jocarsabbdd.crear_tabla(
    "clientes",
    "nombre,apellidos,email"
)

id = jocarsabbdd.insertar(
    "clientes",
    [
        "Jose Vicente",
        "Carratala",
        "jose@jocarsa.com"
    ]
)

print("ID insertado:", id)