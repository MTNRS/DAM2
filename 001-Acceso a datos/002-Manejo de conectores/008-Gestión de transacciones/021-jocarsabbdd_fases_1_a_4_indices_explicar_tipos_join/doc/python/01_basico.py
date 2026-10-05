import os
import jocarsabbdd

CONFIG = os.path.join(os.path.dirname(__file__), "..", "..", "config.json")
# El conector Python usa config.json al inicializarse. Ejecute el ejemplo desde
# la raíz del proyecto o instale el config global previsto por el proyecto.

jocarsabbdd.crear_base("demo_conectores_python")
jocarsabbdd.usar_base("demo_conectores_python")
jocarsabbdd.crear_tabla_tipada("productos", {
    "nombre": "TEXTO",
    "stock": "ENTERO",
    "precio": "REAL",
    "disponible": "BOOLEANO",
})

identificador = jocarsabbdd.insertar("productos", ["Teclado", "10", "25.50", "VERDADERO"])
print("ID insertado:", identificador)
print(jocarsabbdd.listar("productos"))
print(jocarsabbdd.consultar("SELECCIONAR nombre,precio DE productos DONDE precio > 20"))
