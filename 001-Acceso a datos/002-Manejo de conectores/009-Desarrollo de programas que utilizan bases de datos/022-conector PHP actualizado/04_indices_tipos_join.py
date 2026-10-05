import jocarsabbdd

jocarsabbdd.crear_base("ejemplo_fases14")
jocarsabbdd.usar_base("ejemplo_fases14")
jocarsabbdd.crear_tabla_tipada("clientes", {
    "nombre":"TEXTO", "email":"TEXTO", "nacimiento":"FECHA", "saldo":"DECIMAL"
})
jocarsabbdd.crear_indice("idx_email", "clientes", "email")
jocarsabbdd.insertar("clientes", ["Ana","ana@x.es","2026-01-10","100.25"])
print(jocarsabbdd.indices("clientes"))
print(jocarsabbdd.explicar("SELECCIONAR * DE clientes DONDE email = 'ana@x.es'"))
print(jocarsabbdd.consultar("SELECCIONAR c.nombre DE clientes c"))