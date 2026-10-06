import jocarsabbdd

jocarsabbdd.usar_base("demo_conectores_python")

jocarsabbdd.iniciar_transaccion()
jocarsabbdd.insertar("productos", ["Temporal", "1", "999", "FALSO"])
print(jocarsabbdd.consultar("SELECCIONAR CONTAR(*) COMO total DE productos"))
jocarsabbdd.deshacer()

jocarsabbdd.crear_vista(
    "productos_caros",
    "SELECCIONAR nombre,precio DE productos DONDE precio > 20",
)
print(jocarsabbdd.consultar("SELECCIONAR * DE productos_caros"))
