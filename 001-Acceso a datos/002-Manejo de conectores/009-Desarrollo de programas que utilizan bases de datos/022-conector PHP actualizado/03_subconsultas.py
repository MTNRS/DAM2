import jocarsabbdd

jocarsabbdd.usar_base("demo_conectores_python")
print(jocarsabbdd.consultar(
    "SELECCIONAR nombre,precio DE productos "
    "DONDE precio > (SELECCIONAR MEDIA(precio) DE productos)"
))