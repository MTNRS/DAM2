import jocarsabbdd

jocarsabbdd.crear_base("empresaprueba2")

jocarsabbdd.usar_base("empresaprueba2")

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