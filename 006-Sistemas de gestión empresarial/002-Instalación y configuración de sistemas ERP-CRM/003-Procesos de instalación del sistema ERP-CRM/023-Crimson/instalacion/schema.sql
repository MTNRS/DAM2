PRAGMA foreign_keys = ON;

CREATE TABLE "actividades_crm" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "tercero_id" INTEGER,
  "lead_id" INTEGER,
  "oportunidad_id" INTEGER,
  "usuario_id" INTEGER,
  "tipo" TEXT,
  "asunto" TEXT,
  "descripcion" TEXT,
  "fecha_inicio" TEXT,
  "fecha_fin" TEXT,
  "completada" INTEGER,
  FOREIGN KEY ("usuario_id") REFERENCES "usuarios"("Identificador"),
  FOREIGN KEY ("oportunidad_id") REFERENCES "oportunidades"("Identificador"),
  FOREIGN KEY ("lead_id") REFERENCES "leads"("Identificador"),
  FOREIGN KEY ("tercero_id") REFERENCES "terceros"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "activos_fijos" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "codigo" TEXT,
  "nombre" TEXT,
  "fecha_compra" TEXT,
  "valor_compra" REAL,
  "valor_residual" REAL,
  "vida_util_meses" INTEGER,
  "cuenta_activo_id" INTEGER,
  "estado" TEXT,
  FOREIGN KEY ("cuenta_activo_id") REFERENCES "cuentas_contables"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "albaranes_venta" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "numero" TEXT,
  "pedido_id" INTEGER,
  "cliente_id" INTEGER,
  "almacen_id" INTEGER,
  "fecha" TEXT,
  "estado" TEXT,
  FOREIGN KEY ("almacen_id") REFERENCES "almacenes"("Identificador"),
  FOREIGN KEY ("cliente_id") REFERENCES "terceros"("Identificador"),
  FOREIGN KEY ("pedido_id") REFERENCES "pedidos_venta"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "albaranes_venta_lineas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "albaran_id" INTEGER,
  "producto_id" INTEGER,
  "descripcion" TEXT,
  "cantidad" REAL,
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador"),
  FOREIGN KEY ("albaran_id") REFERENCES "albaranes_venta"("Identificador")
);

CREATE TABLE "almacenes" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "centro_id" INTEGER,
  "codigo" TEXT,
  "nombre" TEXT,
  "activo" INTEGER,
  FOREIGN KEY ("centro_id") REFERENCES "centros"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "amortizaciones" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "activo_id" INTEGER,
  "fecha" TEXT,
  "importe" REAL,
  "acumulada" REAL,
  FOREIGN KEY ("activo_id") REFERENCES "activos_fijos"("Identificador")
);

CREATE TABLE "asientos" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "diario_id" INTEGER,
  "numero" INTEGER,
  "fecha" TEXT,
  "concepto" TEXT,
  "referencia" TEXT,
  "cerrado" INTEGER,
  FOREIGN KEY ("diario_id") REFERENCES "diarios_contables"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "asientos_lineas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "asiento_id" INTEGER,
  "cuenta_id" INTEGER,
  "tercero_id" INTEGER,
  "concepto" TEXT,
  "debe" REAL,
  "haber" REAL,
  FOREIGN KEY ("tercero_id") REFERENCES "terceros"("Identificador"),
  FOREIGN KEY ("cuenta_id") REFERENCES "cuentas_contables"("Identificador"),
  FOREIGN KEY ("asiento_id") REFERENCES "asientos"("Identificador")
);

CREATE TABLE "auditoria" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "usuario_id" INTEGER,
  "fecha" TEXT,
  "accion" TEXT,
  "tabla" TEXT,
  "registro_id" INTEGER,
  "datos_anteriores" TEXT,
  "datos_nuevos" TEXT,
  "ip" TEXT,
  FOREIGN KEY ("usuario_id") REFERENCES "usuarios"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "ausencias" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empleado_id" INTEGER,
  "tipo" TEXT,
  "fecha_inicio" TEXT,
  "fecha_fin" TEXT,
  "estado" TEXT,
  "observaciones" TEXT,
  FOREIGN KEY ("empleado_id") REFERENCES "empleados"("Identificador")
);

CREATE TABLE "bancos" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "nombre" TEXT,
  "bic" TEXT
);

CREATE TABLE "categorias_producto" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "padre_id" INTEGER,
  "codigo" TEXT,
  "nombre" TEXT,
  FOREIGN KEY ("padre_id") REFERENCES "categorias_producto"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "centros" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "codigo" TEXT,
  "nombre" TEXT,
  "tipo" TEXT,
  "direccion" TEXT,
  "ciudad" TEXT,
  "provincia_id" INTEGER,
  "codigo_postal" TEXT,
  "pais_codigo" INTEGER,
  "telefono" TEXT,
  "activo" INTEGER,
  FOREIGN KEY ("pais_codigo") REFERENCES "paises"("Identificador"),
  FOREIGN KEY ("provincia_id") REFERENCES "provincias"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "centros_coste" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "codigo" TEXT,
  "nombre" TEXT,
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "cobros" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "factura_id" INTEGER,
  "cliente_id" INTEGER,
  "cuenta_bancaria_id" INTEGER,
  "fecha" TEXT,
  "importe" REAL,
  "metodo" TEXT,
  "referencia" TEXT,
  FOREIGN KEY ("cuenta_bancaria_id") REFERENCES "cuentas_bancarias"("Identificador"),
  FOREIGN KEY ("cliente_id") REFERENCES "terceros"("Identificador"),
  FOREIGN KEY ("factura_id") REFERENCES "facturas_venta"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "configuracion" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "clave" TEXT,
  "valor" TEXT,
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "consumos_fabricacion" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "orden_id" INTEGER,
  "producto_id" INTEGER,
  "cantidad_prevista" REAL,
  "cantidad_real" REAL,
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador"),
  FOREIGN KEY ("orden_id") REFERENCES "ordenes_fabricacion"("Identificador")
);

CREATE TABLE "contactos" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "tercero_id" INTEGER,
  "nombre" TEXT,
  "cargo" TEXT,
  "email" TEXT,
  "telefono" TEXT,
  "movil" TEXT,
  "principal" INTEGER,
  FOREIGN KEY ("tercero_id") REFERENCES "terceros"("Identificador")
);

CREATE TABLE "contratos_laborales" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empleado_id" INTEGER,
  "tipo" TEXT,
  "fecha_inicio" TEXT,
  "fecha_fin" TEXT,
  "jornada_horas" REAL,
  "salario_anual" REAL,
  FOREIGN KEY ("empleado_id") REFERENCES "empleados"("Identificador")
);

CREATE TABLE "cuentas_bancarias" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "banco_id" INTEGER,
  "iban" TEXT,
  "moneda" INTEGER,
  "saldo_inicial" REAL,
  "activa" INTEGER,
  FOREIGN KEY ("moneda") REFERENCES "monedas"("Identificador"),
  FOREIGN KEY ("banco_id") REFERENCES "bancos"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "cuentas_contables" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "plan_id" INTEGER,
  "codigo" TEXT,
  "nombre" TEXT,
  "tipo" TEXT,
  "padre_id" INTEGER,
  FOREIGN KEY ("padre_id") REFERENCES "cuentas_contables"("Identificador"),
  FOREIGN KEY ("plan_id") REFERENCES "planes_contables"("Identificador")
);

CREATE TABLE "departamentos" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "centro_id" INTEGER,
  "padre_id" INTEGER,
  "codigo" TEXT,
  "nombre" TEXT,
  "activo" INTEGER,
  FOREIGN KEY ("padre_id") REFERENCES "departamentos"("Identificador"),
  FOREIGN KEY ("centro_id") REFERENCES "centros"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "diarios_contables" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "codigo" TEXT,
  "nombre" TEXT,
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "direcciones_terceros" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "tercero_id" INTEGER,
  "tipo" TEXT,
  "direccion1" TEXT,
  "direccion2" TEXT,
  "ciudad" TEXT,
  "provincia_id" INTEGER,
  "codigo_postal" TEXT,
  "pais_codigo" INTEGER,
  "principal" INTEGER,
  FOREIGN KEY ("pais_codigo") REFERENCES "paises"("Identificador"),
  FOREIGN KEY ("provincia_id") REFERENCES "provincias"("Identificador"),
  FOREIGN KEY ("tercero_id") REFERENCES "terceros"("Identificador")
);

CREATE TABLE "documentos" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "entidad" TEXT,
  "entidad_id" INTEGER,
  "nombre" TEXT,
  "ruta" TEXT,
  "mime" TEXT,
  "tamano" INTEGER,
  "creado_en" TEXT,
  "usuario_id" INTEGER,
  FOREIGN KEY ("usuario_id") REFERENCES "usuarios"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "empleados" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "usuario_id" INTEGER,
  "departamento_id" INTEGER,
  "codigo" TEXT,
  "nombre" TEXT,
  "apellidos" TEXT,
  "nif" TEXT,
  "email" TEXT,
  "telefono" TEXT,
  "fecha_alta" TEXT,
  "fecha_baja" TEXT,
  "puesto" TEXT,
  "salario_bruto_anual" REAL,
  "activo" INTEGER,
  FOREIGN KEY ("departamento_id") REFERENCES "departamentos"("Identificador"),
  FOREIGN KEY ("usuario_id") REFERENCES "usuarios"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "empleados_formaciones" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empleado_id" INTEGER,
  "formacion_id" INTEGER,
  "fecha" TEXT,
  "resultado" TEXT,
  FOREIGN KEY ("formacion_id") REFERENCES "formaciones"("Identificador"),
  FOREIGN KEY ("empleado_id") REFERENCES "empleados"("Identificador")
);

CREATE TABLE "empresas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "codigo" TEXT,
  "nombre" TEXT,
  "razon_social" TEXT,
  "nif" TEXT,
  "email" TEXT,
  "telefono" TEXT,
  "web" TEXT,
  "moneda_base" TEXT,
  "activa" INTEGER,
  "creado_en" TEXT
);

CREATE TABLE "etiquetas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "nombre" TEXT,
  "color" TEXT,
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "facturas_compra" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "numero" TEXT,
  "proveedor_id" INTEGER,
  "pedido_id" INTEGER,
  "fecha" TEXT,
  "vencimiento" TEXT,
  "moneda" INTEGER,
  "base_imponible" REAL,
  "impuestos" REAL,
  "total" REAL,
  "pagado" REAL,
  "estado" TEXT,
  FOREIGN KEY ("moneda") REFERENCES "monedas"("Identificador"),
  FOREIGN KEY ("pedido_id") REFERENCES "pedidos_compra"("Identificador"),
  FOREIGN KEY ("proveedor_id") REFERENCES "terceros"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "facturas_compra_lineas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "factura_id" INTEGER,
  "producto_id" INTEGER,
  "descripcion" TEXT,
  "cantidad" REAL,
  "precio" REAL,
  "descuento" REAL,
  "iva" REAL,
  "total_linea" REAL,
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador"),
  FOREIGN KEY ("factura_id") REFERENCES "facturas_compra"("Identificador")
);

CREATE TABLE "facturas_venta" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "numero" TEXT,
  "cliente_id" INTEGER,
  "pedido_id" INTEGER,
  "fecha" TEXT,
  "vencimiento" TEXT,
  "moneda" INTEGER,
  "base_imponible" REAL,
  "impuestos" REAL,
  "total" REAL,
  "pagado" REAL,
  "estado" TEXT,
  FOREIGN KEY ("moneda") REFERENCES "monedas"("Identificador"),
  FOREIGN KEY ("pedido_id") REFERENCES "pedidos_venta"("Identificador"),
  FOREIGN KEY ("cliente_id") REFERENCES "terceros"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "facturas_venta_lineas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "factura_id" INTEGER,
  "producto_id" INTEGER,
  "descripcion" TEXT,
  "cantidad" REAL,
  "precio" REAL,
  "descuento" REAL,
  "iva" REAL,
  "total_linea" REAL,
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador"),
  FOREIGN KEY ("factura_id") REFERENCES "facturas_venta"("Identificador")
);

CREATE TABLE "fichajes" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empleado_id" INTEGER,
  "fecha" TEXT,
  "entrada" TEXT,
  "salida" TEXT,
  "pausa_minutos" INTEGER,
  FOREIGN KEY ("empleado_id") REFERENCES "empleados"("Identificador")
);

CREATE TABLE "formaciones" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "nombre" TEXT,
  "proveedor" TEXT,
  "horas" REAL,
  "coste" REAL,
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "formas_pago" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "codigo" TEXT,
  "nombre" TEXT,
  "dias_vencimiento" INTEGER,
  "porcentaje_anticipo" REAL,
  "activa" INTEGER,
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "inventarios" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "almacen_id" INTEGER,
  "fecha" TEXT,
  "estado" TEXT,
  "usuario_id" INTEGER,
  FOREIGN KEY ("usuario_id") REFERENCES "usuarios"("Identificador"),
  FOREIGN KEY ("almacen_id") REFERENCES "almacenes"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "inventarios_lineas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "inventario_id" INTEGER,
  "producto_id" INTEGER,
  "ubicacion_id" INTEGER,
  "cantidad_sistema" REAL,
  "cantidad_contada" REAL,
  FOREIGN KEY ("ubicacion_id") REFERENCES "ubicaciones_almacen"("Identificador"),
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador"),
  FOREIGN KEY ("inventario_id") REFERENCES "inventarios"("Identificador")
);

CREATE TABLE "leads" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "responsable_id" INTEGER,
  "nombre" TEXT,
  "empresa" TEXT,
  "email" TEXT,
  "telefono" TEXT,
  "origen" TEXT,
  "estado" TEXT,
  "valor_estimado" REAL,
  "probabilidad" REAL,
  "creado_en" TEXT,
  FOREIGN KEY ("responsable_id") REFERENCES "usuarios"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "listas_materiales" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "producto_id" INTEGER,
  "version" TEXT,
  "cantidad_base" REAL,
  "activa" INTEGER,
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador")
);

CREATE TABLE "listas_materiales_lineas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "lista_id" INTEGER,
  "componente_id" INTEGER,
  "cantidad" REAL,
  "merma_pct" REAL,
  FOREIGN KEY ("componente_id") REFERENCES "productos"("Identificador"),
  FOREIGN KEY ("lista_id") REFERENCES "listas_materiales"("Identificador")
);

CREATE TABLE "lotes" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "producto_id" INTEGER,
  "lote" TEXT,
  "fecha_fabricacion" TEXT,
  "fecha_caducidad" TEXT,
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador")
);

CREATE TABLE "monedas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "codigo" TEXT,
  "nombre" TEXT,
  "simbolo" TEXT,
  "decimales" INTEGER
);

CREATE TABLE "movimientos_stock" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "producto_id" INTEGER,
  "ubicacion_origen_id" INTEGER,
  "ubicacion_destino_id" INTEGER,
  "lote_id" INTEGER,
  "serie_id" INTEGER,
  "tipo" TEXT,
  "cantidad" REAL,
  "coste_unitario" REAL,
  "referencia" TEXT,
  "fecha" TEXT,
  "usuario_id" INTEGER,
  FOREIGN KEY ("usuario_id") REFERENCES "usuarios"("Identificador"),
  FOREIGN KEY ("serie_id") REFERENCES "numeros_serie"("Identificador"),
  FOREIGN KEY ("lote_id") REFERENCES "lotes"("Identificador"),
  FOREIGN KEY ("ubicacion_destino_id") REFERENCES "ubicaciones_almacen"("Identificador"),
  FOREIGN KEY ("ubicacion_origen_id") REFERENCES "ubicaciones_almacen"("Identificador"),
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "nominas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empleado_id" INTEGER,
  "anio" INTEGER,
  "mes" INTEGER,
  "bruto" REAL,
  "seguridad_social" REAL,
  "irpf" REAL,
  "otros" REAL,
  "neto" REAL,
  FOREIGN KEY ("empleado_id") REFERENCES "empleados"("Identificador")
);

CREATE TABLE "notificaciones" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "usuario_id" INTEGER,
  "titulo" TEXT,
  "mensaje" TEXT,
  "leida" INTEGER,
  "creado_en" TEXT,
  FOREIGN KEY ("usuario_id") REFERENCES "usuarios"("Identificador")
);

CREATE TABLE "numeros_serie" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "producto_id" INTEGER,
  "numero_serie" TEXT,
  "estado" TEXT,
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador")
);

CREATE TABLE "operaciones_ruta" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "ruta_id" INTEGER,
  "secuencia" INTEGER,
  "nombre" TEXT,
  "maquina_id" INTEGER,
  "tiempo_preparacion" REAL,
  "tiempo_unitario" REAL,
  FOREIGN KEY ("maquina_id") REFERENCES "recursos_maquina"("Identificador"),
  FOREIGN KEY ("ruta_id") REFERENCES "rutas_fabricacion"("Identificador")
);

CREATE TABLE "oportunidades" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "tercero_id" INTEGER,
  "responsable_id" INTEGER,
  "titulo" TEXT,
  "fase" TEXT,
  "importe" REAL,
  "probabilidad" REAL,
  "fecha_cierre_prevista" TEXT,
  "estado" TEXT,
  "creado_en" TEXT,
  FOREIGN KEY ("responsable_id") REFERENCES "usuarios"("Identificador"),
  FOREIGN KEY ("tercero_id") REFERENCES "terceros"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "ordenes_fabricacion" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "numero" TEXT,
  "producto_id" INTEGER,
  "lista_id" INTEGER,
  "ruta_id" INTEGER,
  "cantidad" REAL,
  "fabricada" REAL,
  "fecha_inicio" TEXT,
  "fecha_fin_prevista" TEXT,
  "fecha_fin_real" TEXT,
  "estado" TEXT,
  FOREIGN KEY ("ruta_id") REFERENCES "rutas_fabricacion"("Identificador"),
  FOREIGN KEY ("lista_id") REFERENCES "listas_materiales"("Identificador"),
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "pagos" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "factura_id" INTEGER,
  "proveedor_id" INTEGER,
  "cuenta_bancaria_id" INTEGER,
  "fecha" TEXT,
  "importe" REAL,
  "metodo" TEXT,
  "referencia" TEXT,
  FOREIGN KEY ("cuenta_bancaria_id") REFERENCES "cuentas_bancarias"("Identificador"),
  FOREIGN KEY ("proveedor_id") REFERENCES "terceros"("Identificador"),
  FOREIGN KEY ("factura_id") REFERENCES "facturas_compra"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "paises" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "codigo" TEXT,
  "nombre" TEXT
);

CREATE TABLE "partes_horas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empleado_id" INTEGER,
  "proyecto_id" INTEGER,
  "tarea_id" INTEGER,
  "fecha" TEXT,
  "horas" REAL,
  "descripcion" TEXT,
  "facturable" INTEGER,
  FOREIGN KEY ("tarea_id") REFERENCES "tareas_proyecto"("Identificador"),
  FOREIGN KEY ("proyecto_id") REFERENCES "proyectos"("Identificador"),
  FOREIGN KEY ("empleado_id") REFERENCES "empleados"("Identificador")
);

CREATE TABLE "pedidos_compra" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "numero" TEXT,
  "proveedor_id" INTEGER,
  "almacen_id" INTEGER,
  "fecha" TEXT,
  "fecha_prevista" TEXT,
  "moneda" INTEGER,
  "estado" TEXT,
  "descuento" REAL,
  "observaciones" TEXT,
  "usuario_id" INTEGER,
  FOREIGN KEY ("usuario_id") REFERENCES "usuarios"("Identificador"),
  FOREIGN KEY ("moneda") REFERENCES "monedas"("Identificador"),
  FOREIGN KEY ("almacen_id") REFERENCES "almacenes"("Identificador"),
  FOREIGN KEY ("proveedor_id") REFERENCES "terceros"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "pedidos_compra_lineas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "pedido_id" INTEGER,
  "producto_id" INTEGER,
  "descripcion" TEXT,
  "cantidad" REAL,
  "precio" REAL,
  "descuento" REAL,
  "iva" REAL,
  "recibida" REAL,
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador"),
  FOREIGN KEY ("pedido_id") REFERENCES "pedidos_compra"("Identificador")
);

CREATE TABLE "pedidos_venta" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "numero" TEXT,
  "cliente_id" INTEGER,
  "presupuesto_id" INTEGER,
  "fecha" TEXT,
  "fecha_entrega" TEXT,
  "moneda" INTEGER,
  "estado" TEXT,
  "usuario_id" INTEGER,
  "observaciones" TEXT,
  FOREIGN KEY ("usuario_id") REFERENCES "usuarios"("Identificador"),
  FOREIGN KEY ("moneda") REFERENCES "monedas"("Identificador"),
  FOREIGN KEY ("presupuesto_id") REFERENCES "presupuestos_venta"("Identificador"),
  FOREIGN KEY ("cliente_id") REFERENCES "terceros"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "pedidos_venta_lineas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "pedido_id" INTEGER,
  "producto_id" INTEGER,
  "descripcion" TEXT,
  "cantidad" REAL,
  "precio" REAL,
  "descuento" REAL,
  "iva" REAL,
  "servida" REAL,
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador"),
  FOREIGN KEY ("pedido_id") REFERENCES "pedidos_venta"("Identificador")
);

CREATE TABLE "permisos" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "codigo" TEXT,
  "descripcion" TEXT
);

CREATE TABLE "planes_contables" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "nombre" TEXT,
  "ejercicio" INTEGER,
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "presupuestos_financieros" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "ejercicio" INTEGER,
  "nombre" TEXT,
  "estado" TEXT,
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "presupuestos_financieros_lineas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "presupuesto_id" INTEGER,
  "cuenta_id" INTEGER,
  "centro_coste_id" INTEGER,
  "mes" INTEGER,
  "importe" REAL,
  FOREIGN KEY ("centro_coste_id") REFERENCES "centros_coste"("Identificador"),
  FOREIGN KEY ("cuenta_id") REFERENCES "cuentas_contables"("Identificador"),
  FOREIGN KEY ("presupuesto_id") REFERENCES "presupuestos_financieros"("Identificador")
);

CREATE TABLE "presupuestos_venta" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "numero" TEXT,
  "cliente_id" INTEGER,
  "fecha" TEXT,
  "valido_hasta" TEXT,
  "moneda" INTEGER,
  "estado" TEXT,
  "usuario_id" INTEGER,
  "observaciones" TEXT,
  FOREIGN KEY ("usuario_id") REFERENCES "usuarios"("Identificador"),
  FOREIGN KEY ("moneda") REFERENCES "monedas"("Identificador"),
  FOREIGN KEY ("cliente_id") REFERENCES "terceros"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "presupuestos_venta_lineas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "presupuesto_id" INTEGER,
  "producto_id" INTEGER,
  "descripcion" TEXT,
  "cantidad" REAL,
  "precio" REAL,
  "descuento" REAL,
  "iva" REAL,
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador"),
  FOREIGN KEY ("presupuesto_id") REFERENCES "presupuestos_venta"("Identificador")
);

CREATE TABLE "producciones_fabricacion" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "orden_id" INTEGER,
  "fecha" TEXT,
  "cantidad_buena" REAL,
  "cantidad_rechazada" REAL,
  "lote_id" INTEGER,
  FOREIGN KEY ("lote_id") REFERENCES "lotes"("Identificador"),
  FOREIGN KEY ("orden_id") REFERENCES "ordenes_fabricacion"("Identificador")
);

CREATE TABLE "productos" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "categoria_id" INTEGER,
  "sku" TEXT,
  "nombre" TEXT,
  "descripcion" TEXT,
  "tipo" TEXT,
  "unidad_id" INTEGER,
  "iva_id" INTEGER,
  "precio_coste" REAL,
  "precio_venta" REAL,
  "peso" REAL,
  "stock_minimo" REAL,
  "stock_maximo" REAL,
  "gestion_lotes" INTEGER,
  "gestion_series" INTEGER,
  "activo" INTEGER,
  FOREIGN KEY ("iva_id") REFERENCES "tipos_iva"("Identificador"),
  FOREIGN KEY ("unidad_id") REFERENCES "unidades_medida"("Identificador"),
  FOREIGN KEY ("categoria_id") REFERENCES "categorias_producto"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "provincias" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "pais_codigo" INTEGER,
  "nombre" TEXT,
  FOREIGN KEY ("pais_codigo") REFERENCES "paises"("Identificador")
);

CREATE TABLE "proyectos" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "cliente_id" INTEGER,
  "responsable_id" INTEGER,
  "codigo" TEXT,
  "nombre" TEXT,
  "fecha_inicio" TEXT,
  "fecha_fin_prevista" TEXT,
  "fecha_fin_real" TEXT,
  "presupuesto" REAL,
  "estado" TEXT,
  FOREIGN KEY ("responsable_id") REFERENCES "empleados"("Identificador"),
  FOREIGN KEY ("cliente_id") REFERENCES "terceros"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "recepciones_compra" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "numero" TEXT,
  "pedido_id" INTEGER,
  "proveedor_id" INTEGER,
  "almacen_id" INTEGER,
  "fecha" TEXT,
  "estado" TEXT,
  FOREIGN KEY ("almacen_id") REFERENCES "almacenes"("Identificador"),
  FOREIGN KEY ("proveedor_id") REFERENCES "terceros"("Identificador"),
  FOREIGN KEY ("pedido_id") REFERENCES "pedidos_compra"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "recepciones_compra_lineas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "recepcion_id" INTEGER,
  "producto_id" INTEGER,
  "cantidad" REAL,
  "lote_id" INTEGER,
  "coste" REAL,
  FOREIGN KEY ("lote_id") REFERENCES "lotes"("Identificador"),
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador"),
  FOREIGN KEY ("recepcion_id") REFERENCES "recepciones_compra"("Identificador")
);

CREATE TABLE "recursos_maquina" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "centro_id" INTEGER,
  "codigo" TEXT,
  "nombre" TEXT,
  "coste_hora" REAL,
  "capacidad_hora" REAL,
  "activo" INTEGER,
  FOREIGN KEY ("centro_id") REFERENCES "centros"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "roles" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "nombre" TEXT,
  "descripcion" TEXT
);

CREATE TABLE "roles_permisos" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "rol_id" INTEGER,
  "permiso_id" INTEGER,
  FOREIGN KEY ("permiso_id") REFERENCES "permisos"("Identificador"),
  FOREIGN KEY ("rol_id") REFERENCES "roles"("Identificador")
);

CREATE TABLE "rutas_fabricacion" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "producto_id" INTEGER,
  "version" TEXT,
  "activa" INTEGER,
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador")
);

CREATE TABLE "series_documentales" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "tipo_documento" TEXT,
  "prefijo" TEXT,
  "siguiente_numero" INTEGER,
  "longitud" INTEGER,
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "solicitudes_compra" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "departamento_id" INTEGER,
  "solicitante_id" INTEGER,
  "fecha" TEXT,
  "estado" TEXT,
  "observaciones" TEXT,
  FOREIGN KEY ("solicitante_id") REFERENCES "usuarios"("Identificador"),
  FOREIGN KEY ("departamento_id") REFERENCES "departamentos"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "solicitudes_compra_lineas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "solicitud_id" INTEGER,
  "producto_id" INTEGER,
  "descripcion" TEXT,
  "cantidad" REAL,
  "fecha_necesidad" TEXT,
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador"),
  FOREIGN KEY ("solicitud_id") REFERENCES "solicitudes_compra"("Identificador")
);

CREATE TABLE "stock" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "producto_id" INTEGER,
  "ubicacion_id" INTEGER,
  "lote_id" INTEGER,
  "cantidad" REAL,
  "reservado" REAL,
  FOREIGN KEY ("lote_id") REFERENCES "lotes"("Identificador"),
  FOREIGN KEY ("ubicacion_id") REFERENCES "ubicaciones_almacen"("Identificador"),
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador")
);

CREATE TABLE "tareas_proyecto" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "proyecto_id" INTEGER,
  "padre_id" INTEGER,
  "responsable_id" INTEGER,
  "titulo" TEXT,
  "descripcion" TEXT,
  "prioridad" TEXT,
  "estado" TEXT,
  "fecha_inicio" TEXT,
  "fecha_fin" TEXT,
  "horas_estimadas" REAL,
  FOREIGN KEY ("responsable_id") REFERENCES "empleados"("Identificador"),
  FOREIGN KEY ("padre_id") REFERENCES "tareas_proyecto"("Identificador"),
  FOREIGN KEY ("proyecto_id") REFERENCES "proyectos"("Identificador")
);

CREATE TABLE "tarifas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "codigo" TEXT,
  "nombre" TEXT,
  "moneda" INTEGER,
  "activa" INTEGER,
  FOREIGN KEY ("moneda") REFERENCES "monedas"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "tarifas_lineas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "tarifa_id" INTEGER,
  "producto_id" INTEGER,
  "cantidad_desde" REAL,
  "precio" REAL,
  "descuento" REAL,
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador"),
  FOREIGN KEY ("tarifa_id") REFERENCES "tarifas"("Identificador")
);

CREATE TABLE "terceros" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "tipo" TEXT,
  "codigo" TEXT,
  "razon_social" TEXT,
  "nombre_comercial" TEXT,
  "nif" TEXT,
  "email" TEXT,
  "telefono" TEXT,
  "web" TEXT,
  "forma_pago_id" INTEGER,
  "limite_credito" REAL,
  "descuento" REAL,
  "activo" INTEGER,
  "creado_en" TEXT,
  FOREIGN KEY ("forma_pago_id") REFERENCES "formas_pago"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "terceros_etiquetas" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "tercero_id" INTEGER,
  "etiqueta_id" INTEGER,
  FOREIGN KEY ("etiqueta_id") REFERENCES "etiquetas"("Identificador"),
  FOREIGN KEY ("tercero_id") REFERENCES "terceros"("Identificador")
);

CREATE TABLE "tickets_mensajes" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "ticket_id" INTEGER,
  "usuario_id" INTEGER,
  "contacto_id" INTEGER,
  "mensaje" TEXT,
  "creado_en" TEXT,
  "interno" INTEGER,
  FOREIGN KEY ("contacto_id") REFERENCES "contactos"("Identificador"),
  FOREIGN KEY ("usuario_id") REFERENCES "usuarios"("Identificador"),
  FOREIGN KEY ("ticket_id") REFERENCES "tickets_soporte"("Identificador")
);

CREATE TABLE "tickets_soporte" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "tercero_id" INTEGER,
  "contacto_id" INTEGER,
  "responsable_id" INTEGER,
  "numero" TEXT,
  "asunto" TEXT,
  "descripcion" TEXT,
  "prioridad" TEXT,
  "estado" TEXT,
  "creado_en" TEXT,
  "cerrado_en" TEXT,
  FOREIGN KEY ("responsable_id") REFERENCES "usuarios"("Identificador"),
  FOREIGN KEY ("contacto_id") REFERENCES "contactos"("Identificador"),
  FOREIGN KEY ("tercero_id") REFERENCES "terceros"("Identificador"),
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "tipos_cambio" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "moneda_origen" INTEGER,
  "moneda_destino" INTEGER,
  "fecha" TEXT,
  "cambio" REAL,
  FOREIGN KEY ("moneda_destino") REFERENCES "monedas"("Identificador"),
  FOREIGN KEY ("moneda_origen") REFERENCES "monedas"("Identificador")
);

CREATE TABLE "tipos_iva" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "nombre" TEXT,
  "porcentaje" REAL,
  "recargo" REAL,
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "ubicaciones_almacen" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "almacen_id" INTEGER,
  "codigo" TEXT,
  "nombre" TEXT,
  "tipo" TEXT,
  FOREIGN KEY ("almacen_id") REFERENCES "almacenes"("Identificador")
);

CREATE TABLE "unidades_medida" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "codigo" TEXT,
  "nombre" TEXT,
  "factor_base" REAL
);

CREATE TABLE "usuarios" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "empresa_id" INTEGER,
  "username" TEXT,
  "password_hash" TEXT,
  "nombre" TEXT,
  "email" TEXT,
  "activo" INTEGER,
  "ultimo_acceso" TEXT,
  "creado_en" TEXT,
  FOREIGN KEY ("empresa_id") REFERENCES "empresas"("Identificador")
);

CREATE TABLE "usuarios_roles" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "usuario_id" INTEGER,
  "rol_id" INTEGER,
  FOREIGN KEY ("rol_id") REFERENCES "roles"("Identificador"),
  FOREIGN KEY ("usuario_id") REFERENCES "usuarios"("Identificador")
);

CREATE TABLE "variantes_producto" (
  "Identificador" INTEGER PRIMARY KEY AUTOINCREMENT,
  "producto_id" INTEGER,
  "codigo" TEXT,
  "nombre" TEXT,
  "precio_adicional" REAL,
  FOREIGN KEY ("producto_id") REFERENCES "productos"("Identificador")
);
