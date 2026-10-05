PRAGMA foreign_keys = ON;

-- Schema inicial de Crimson ERP.
-- Este archivo sustituye al antiguo modelodedatos.md.
-- El editor visual de Modelo de datos genera las migraciones posteriores.

CREATE TABLE "actividades_crm" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "tercero_id" INTEGER REFERENCES "terceros"("Identificador"), "lead_id" INTEGER REFERENCES "leads"("Identificador"), "oportunidad_id" INTEGER REFERENCES "oportunidades"("Identificador"), "usuario_id" INTEGER REFERENCES "usuarios"("Identificador"), "tipo" TEXT, "asunto" TEXT, "descripcion" TEXT, "fecha_inicio" TEXT, "fecha_fin" TEXT, "completada" INTEGER);

CREATE TABLE "activos_fijos" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "codigo" TEXT, "nombre" TEXT, "fecha_compra" TEXT, "valor_compra" REAL, "valor_residual" REAL, "vida_util_meses" INTEGER, "cuenta_activo_id" INTEGER REFERENCES "cuentas_contables"("Identificador"), "estado" TEXT);

CREATE TABLE "albaranes_venta" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "numero" TEXT, "pedido_id" INTEGER REFERENCES "pedidos_venta"("Identificador"), "cliente_id" INTEGER REFERENCES "terceros"("Identificador"), "almacen_id" INTEGER REFERENCES "almacenes"("Identificador"), "fecha" TEXT, "estado" TEXT);

CREATE TABLE "albaranes_venta_lineas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "albaran_id" INTEGER REFERENCES "albaranes_venta"("Identificador"), "producto_id" INTEGER REFERENCES "productos"("Identificador"), "descripcion" TEXT, "cantidad" REAL);

CREATE TABLE "almacenes" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "centro_id" INTEGER REFERENCES "centros"("Identificador"), "codigo" TEXT, "nombre" TEXT, "activo" INTEGER);

CREATE TABLE "amortizaciones" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "activo_id" INTEGER REFERENCES "activos_fijos"("Identificador"), "fecha" TEXT, "importe" REAL, "acumulada" REAL);

CREATE TABLE "asientos" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "diario_id" INTEGER REFERENCES "diarios_contables"("Identificador"), "numero" INTEGER, "fecha" TEXT, "concepto" TEXT, "referencia" TEXT, "cerrado" INTEGER);

CREATE TABLE "asientos_lineas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "asiento_id" INTEGER REFERENCES "asientos"("Identificador"), "cuenta_id" INTEGER REFERENCES "cuentas_contables"("Identificador"), "tercero_id" INTEGER REFERENCES "terceros"("Identificador"), "concepto" TEXT, "debe" REAL, "haber" REAL);

CREATE TABLE "auditoria" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "usuario_id" INTEGER REFERENCES "usuarios"("Identificador"), "fecha" TEXT, "accion" TEXT, "tabla" TEXT, "registro_id" INTEGER, "datos_anteriores" TEXT, "datos_nuevos" TEXT, "ip" TEXT);

CREATE TABLE "ausencias" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empleado_id" INTEGER REFERENCES "empleados"("Identificador"), "tipo" TEXT, "fecha_inicio" TEXT, "fecha_fin" TEXT, "estado" TEXT, "observaciones" TEXT);

CREATE TABLE "bancos" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "nombre" TEXT, "bic" TEXT);

CREATE TABLE "categorias_producto" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "padre_id" INTEGER REFERENCES "categorias_producto"("Identificador"), "codigo" TEXT, "nombre" TEXT);

CREATE TABLE "centros" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "codigo" TEXT, "nombre" TEXT, "tipo" TEXT, "direccion" TEXT, "ciudad" TEXT, "provincia_id" INTEGER REFERENCES "provincias"("Identificador"), "codigo_postal" TEXT, "pais_codigo" INTEGER REFERENCES "paises"("Identificador"), "telefono" TEXT, "activo" INTEGER);

CREATE TABLE "centros_coste" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "codigo" TEXT, "nombre" TEXT);

CREATE TABLE "cobros" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "factura_id" INTEGER REFERENCES "facturas_venta"("Identificador"), "cliente_id" INTEGER REFERENCES "terceros"("Identificador"), "cuenta_bancaria_id" INTEGER REFERENCES "cuentas_bancarias"("Identificador"), "fecha" TEXT, "importe" REAL, "metodo" TEXT, "referencia" TEXT);

CREATE TABLE "configuracion" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "clave" TEXT, "valor" TEXT);

CREATE TABLE "consumos_fabricacion" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "orden_id" INTEGER REFERENCES "ordenes_fabricacion"("Identificador"), "producto_id" INTEGER REFERENCES "productos"("Identificador"), "cantidad_prevista" REAL, "cantidad_real" REAL);

CREATE TABLE "contactos" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "tercero_id" INTEGER REFERENCES "terceros"("Identificador"), "nombre" TEXT, "cargo" TEXT, "email" TEXT, "telefono" TEXT, "movil" TEXT, "principal" INTEGER);

CREATE TABLE "contratos_laborales" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empleado_id" INTEGER REFERENCES "empleados"("Identificador"), "tipo" TEXT, "fecha_inicio" TEXT, "fecha_fin" TEXT, "jornada_horas" REAL, "salario_anual" REAL);

CREATE TABLE "cuentas_bancarias" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "banco_id" INTEGER REFERENCES "bancos"("Identificador"), "iban" TEXT, "moneda" INTEGER REFERENCES "monedas"("Identificador"), "saldo_inicial" REAL, "activa" INTEGER);

CREATE TABLE "cuentas_contables" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "plan_id" INTEGER REFERENCES "planes_contables"("Identificador"), "codigo" TEXT, "nombre" TEXT, "tipo" TEXT, "padre_id" INTEGER REFERENCES "cuentas_contables"("Identificador"));

CREATE TABLE "departamentos" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "centro_id" INTEGER REFERENCES "centros"("Identificador"), "padre_id" INTEGER REFERENCES "departamentos"("Identificador"), "codigo" TEXT, "nombre" TEXT, "activo" INTEGER);

CREATE TABLE "diarios_contables" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "codigo" TEXT, "nombre" TEXT);

CREATE TABLE "direcciones_terceros" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "tercero_id" INTEGER REFERENCES "terceros"("Identificador"), "tipo" TEXT, "direccion1" TEXT, "direccion2" TEXT, "ciudad" TEXT, "provincia_id" INTEGER REFERENCES "provincias"("Identificador"), "codigo_postal" TEXT, "pais_codigo" INTEGER REFERENCES "paises"("Identificador"), "principal" INTEGER);

CREATE TABLE "documentos" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "entidad" TEXT, "entidad_id" INTEGER, "nombre" TEXT, "ruta" TEXT, "mime" TEXT, "tamano" INTEGER, "creado_en" TEXT, "usuario_id" INTEGER REFERENCES "usuarios"("Identificador"));

CREATE TABLE "empleados" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "usuario_id" INTEGER REFERENCES "usuarios"("Identificador"), "departamento_id" INTEGER REFERENCES "departamentos"("Identificador"), "codigo" TEXT, "nombre" TEXT, "apellidos" TEXT, "nif" TEXT, "email" TEXT, "telefono" TEXT, "fecha_alta" TEXT, "fecha_baja" TEXT, "puesto" TEXT, "salario_bruto_anual" REAL, "activo" INTEGER);

CREATE TABLE "empleados_formaciones" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empleado_id" INTEGER REFERENCES "empleados"("Identificador"), "formacion_id" INTEGER REFERENCES "formaciones"("Identificador"), "fecha" TEXT, "resultado" TEXT);

CREATE TABLE "empresas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "codigo" TEXT, "nombre" TEXT, "razon_social" TEXT, "nif" TEXT, "email" TEXT, "telefono" TEXT, "web" TEXT, "moneda_base" TEXT, "activa" INTEGER, "creado_en" TEXT);

CREATE TABLE "etiquetas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "nombre" TEXT, "color" TEXT);

CREATE TABLE "facturas_compra" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "numero" TEXT, "proveedor_id" INTEGER REFERENCES "terceros"("Identificador"), "pedido_id" INTEGER REFERENCES "pedidos_compra"("Identificador"), "fecha" TEXT, "vencimiento" TEXT, "moneda" INTEGER REFERENCES "monedas"("Identificador"), "base_imponible" REAL, "impuestos" REAL, "total" REAL, "pagado" REAL, "estado" TEXT);

CREATE TABLE "facturas_compra_lineas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "factura_id" INTEGER REFERENCES "facturas_compra"("Identificador"), "producto_id" INTEGER REFERENCES "productos"("Identificador"), "descripcion" TEXT, "cantidad" REAL, "precio" REAL, "descuento" REAL, "iva" REAL, "total_linea" REAL);

CREATE TABLE "facturas_venta" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "numero" TEXT, "cliente_id" INTEGER REFERENCES "terceros"("Identificador"), "pedido_id" INTEGER REFERENCES "pedidos_venta"("Identificador"), "fecha" TEXT, "vencimiento" TEXT, "moneda" INTEGER REFERENCES "monedas"("Identificador"), "base_imponible" REAL, "impuestos" REAL, "total" REAL, "pagado" REAL, "estado" TEXT);

CREATE TABLE "facturas_venta_lineas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "factura_id" INTEGER REFERENCES "facturas_venta"("Identificador"), "producto_id" INTEGER REFERENCES "productos"("Identificador"), "descripcion" TEXT, "cantidad" REAL, "precio" REAL, "descuento" REAL, "iva" REAL, "total_linea" REAL);

CREATE TABLE "fichajes" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empleado_id" INTEGER REFERENCES "empleados"("Identificador"), "fecha" TEXT, "entrada" TEXT, "salida" TEXT, "pausa_minutos" INTEGER);

CREATE TABLE "formaciones" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "nombre" TEXT, "proveedor" TEXT, "horas" REAL, "coste" REAL);

CREATE TABLE "formas_pago" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "codigo" TEXT, "nombre" TEXT, "dias_vencimiento" INTEGER, "porcentaje_anticipo" REAL, "activa" INTEGER);

CREATE TABLE "inventarios" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "almacen_id" INTEGER REFERENCES "almacenes"("Identificador"), "fecha" TEXT, "estado" TEXT, "usuario_id" INTEGER REFERENCES "usuarios"("Identificador"));

CREATE TABLE "inventarios_lineas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "inventario_id" INTEGER REFERENCES "inventarios"("Identificador"), "producto_id" INTEGER REFERENCES "productos"("Identificador"), "ubicacion_id" INTEGER REFERENCES "ubicaciones_almacen"("Identificador"), "cantidad_sistema" REAL, "cantidad_contada" REAL);

CREATE TABLE "leads" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "responsable_id" INTEGER REFERENCES "usuarios"("Identificador"), "nombre" TEXT, "empresa" TEXT, "email" TEXT, "telefono" TEXT, "origen" TEXT, "estado" TEXT, "valor_estimado" REAL, "probabilidad" REAL, "creado_en" TEXT);

CREATE TABLE "listas_materiales" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "producto_id" INTEGER REFERENCES "productos"("Identificador"), "version" TEXT, "cantidad_base" REAL, "activa" INTEGER);

CREATE TABLE "listas_materiales_lineas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "lista_id" INTEGER REFERENCES "listas_materiales"("Identificador"), "componente_id" INTEGER REFERENCES "productos"("Identificador"), "cantidad" REAL, "merma_pct" REAL);

CREATE TABLE "lotes" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "producto_id" INTEGER REFERENCES "productos"("Identificador"), "lote" TEXT, "fecha_fabricacion" TEXT, "fecha_caducidad" TEXT);

CREATE TABLE "monedas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "codigo" TEXT, "nombre" TEXT, "simbolo" TEXT, "decimales" INTEGER);

CREATE TABLE "movimientos_stock" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "producto_id" INTEGER REFERENCES "productos"("Identificador"), "ubicacion_origen_id" INTEGER REFERENCES "ubicaciones_almacen"("Identificador"), "ubicacion_destino_id" INTEGER REFERENCES "ubicaciones_almacen"("Identificador"), "lote_id" INTEGER REFERENCES "lotes"("Identificador"), "serie_id" INTEGER REFERENCES "numeros_serie"("Identificador"), "tipo" TEXT, "cantidad" REAL, "coste_unitario" REAL, "referencia" TEXT, "fecha" TEXT, "usuario_id" INTEGER REFERENCES "usuarios"("Identificador"));

CREATE TABLE "nominas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empleado_id" INTEGER REFERENCES "empleados"("Identificador"), "anio" INTEGER, "mes" INTEGER, "bruto" REAL, "seguridad_social" REAL, "irpf" REAL, "otros" REAL, "neto" REAL);

CREATE TABLE "notificaciones" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "usuario_id" INTEGER REFERENCES "usuarios"("Identificador"), "titulo" TEXT, "mensaje" TEXT, "leida" INTEGER, "creado_en" TEXT);

CREATE TABLE "numeros_serie" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "producto_id" INTEGER REFERENCES "productos"("Identificador"), "numero_serie" TEXT, "estado" TEXT);

CREATE TABLE "operaciones_ruta" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "ruta_id" INTEGER REFERENCES "rutas_fabricacion"("Identificador"), "secuencia" INTEGER, "nombre" TEXT, "maquina_id" INTEGER REFERENCES "recursos_maquina"("Identificador"), "tiempo_preparacion" REAL, "tiempo_unitario" REAL);

CREATE TABLE "oportunidades" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "tercero_id" INTEGER REFERENCES "terceros"("Identificador"), "responsable_id" INTEGER REFERENCES "usuarios"("Identificador"), "titulo" TEXT, "fase" TEXT, "importe" REAL, "probabilidad" REAL, "fecha_cierre_prevista" TEXT, "estado" TEXT, "creado_en" TEXT);

CREATE TABLE "ordenes_fabricacion" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "numero" TEXT, "producto_id" INTEGER REFERENCES "productos"("Identificador"), "lista_id" INTEGER REFERENCES "listas_materiales"("Identificador"), "ruta_id" INTEGER REFERENCES "rutas_fabricacion"("Identificador"), "cantidad" REAL, "fabricada" REAL, "fecha_inicio" TEXT, "fecha_fin_prevista" TEXT, "fecha_fin_real" TEXT, "estado" TEXT);

CREATE TABLE "pagos" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "factura_id" INTEGER REFERENCES "facturas_compra"("Identificador"), "proveedor_id" INTEGER REFERENCES "terceros"("Identificador"), "cuenta_bancaria_id" INTEGER REFERENCES "cuentas_bancarias"("Identificador"), "fecha" TEXT, "importe" REAL, "metodo" TEXT, "referencia" TEXT);

CREATE TABLE "paises" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "codigo" TEXT, "nombre" TEXT);

CREATE TABLE "partes_horas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empleado_id" INTEGER REFERENCES "empleados"("Identificador"), "proyecto_id" INTEGER REFERENCES "proyectos"("Identificador"), "tarea_id" INTEGER REFERENCES "tareas_proyecto"("Identificador"), "fecha" TEXT, "horas" REAL, "descripcion" TEXT, "facturable" INTEGER);

CREATE TABLE "pedidos_compra" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "numero" TEXT, "proveedor_id" INTEGER REFERENCES "terceros"("Identificador"), "almacen_id" INTEGER REFERENCES "almacenes"("Identificador"), "fecha" TEXT, "fecha_prevista" TEXT, "moneda" INTEGER REFERENCES "monedas"("Identificador"), "estado" TEXT, "descuento" REAL, "observaciones" TEXT, "usuario_id" INTEGER REFERENCES "usuarios"("Identificador"));

CREATE TABLE "pedidos_compra_lineas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "pedido_id" INTEGER REFERENCES "pedidos_compra"("Identificador"), "producto_id" INTEGER REFERENCES "productos"("Identificador"), "descripcion" TEXT, "cantidad" REAL, "precio" REAL, "descuento" REAL, "iva" REAL, "recibida" REAL);

CREATE TABLE "pedidos_venta" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "numero" TEXT, "cliente_id" INTEGER REFERENCES "terceros"("Identificador"), "presupuesto_id" INTEGER REFERENCES "presupuestos_venta"("Identificador"), "fecha" TEXT, "fecha_entrega" TEXT, "moneda" INTEGER REFERENCES "monedas"("Identificador"), "estado" TEXT, "usuario_id" INTEGER REFERENCES "usuarios"("Identificador"), "observaciones" TEXT);

CREATE TABLE "pedidos_venta_lineas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "pedido_id" INTEGER REFERENCES "pedidos_venta"("Identificador"), "producto_id" INTEGER REFERENCES "productos"("Identificador"), "descripcion" TEXT, "cantidad" REAL, "precio" REAL, "descuento" REAL, "iva" REAL, "servida" REAL);

CREATE TABLE "permisos" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "codigo" TEXT, "descripcion" TEXT);

CREATE TABLE "planes_contables" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "nombre" TEXT, "ejercicio" INTEGER);

CREATE TABLE "presupuestos_financieros" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "ejercicio" INTEGER, "nombre" TEXT, "estado" TEXT);

CREATE TABLE "presupuestos_financieros_lineas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "presupuesto_id" INTEGER REFERENCES "presupuestos_financieros"("Identificador"), "cuenta_id" INTEGER REFERENCES "cuentas_contables"("Identificador"), "centro_coste_id" INTEGER REFERENCES "centros_coste"("Identificador"), "mes" INTEGER, "importe" REAL);

CREATE TABLE "presupuestos_venta" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "numero" TEXT, "cliente_id" INTEGER REFERENCES "terceros"("Identificador"), "fecha" TEXT, "valido_hasta" TEXT, "moneda" INTEGER REFERENCES "monedas"("Identificador"), "estado" TEXT, "usuario_id" INTEGER REFERENCES "usuarios"("Identificador"), "observaciones" TEXT);

CREATE TABLE "presupuestos_venta_lineas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "presupuesto_id" INTEGER REFERENCES "presupuestos_venta"("Identificador"), "producto_id" INTEGER REFERENCES "productos"("Identificador"), "descripcion" TEXT, "cantidad" REAL, "precio" REAL, "descuento" REAL, "iva" REAL);

CREATE TABLE "producciones_fabricacion" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "orden_id" INTEGER REFERENCES "ordenes_fabricacion"("Identificador"), "fecha" TEXT, "cantidad_buena" REAL, "cantidad_rechazada" REAL, "lote_id" INTEGER REFERENCES "lotes"("Identificador"));

CREATE TABLE "productos" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "categoria_id" INTEGER REFERENCES "categorias_producto"("Identificador"), "sku" TEXT, "nombre" TEXT, "descripcion" TEXT, "tipo" TEXT, "unidad_id" INTEGER REFERENCES "unidades_medida"("Identificador"), "iva_id" INTEGER REFERENCES "tipos_iva"("Identificador"), "precio_coste" REAL, "precio_venta" REAL, "peso" REAL, "stock_minimo" REAL, "stock_maximo" REAL, "gestion_lotes" INTEGER, "gestion_series" INTEGER, "activo" INTEGER);

CREATE TABLE "provincias" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "pais_codigo" INTEGER REFERENCES "paises"("Identificador"), "nombre" TEXT);

CREATE TABLE "proyectos" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "cliente_id" INTEGER REFERENCES "terceros"("Identificador"), "responsable_id" INTEGER REFERENCES "empleados"("Identificador"), "codigo" TEXT, "nombre" TEXT, "fecha_inicio" TEXT, "fecha_fin_prevista" TEXT, "fecha_fin_real" TEXT, "presupuesto" REAL, "estado" TEXT);

CREATE TABLE "recepciones_compra" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "numero" TEXT, "pedido_id" INTEGER REFERENCES "pedidos_compra"("Identificador"), "proveedor_id" INTEGER REFERENCES "terceros"("Identificador"), "almacen_id" INTEGER REFERENCES "almacenes"("Identificador"), "fecha" TEXT, "estado" TEXT);

CREATE TABLE "recepciones_compra_lineas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "recepcion_id" INTEGER REFERENCES "recepciones_compra"("Identificador"), "producto_id" INTEGER REFERENCES "productos"("Identificador"), "cantidad" REAL, "lote_id" INTEGER REFERENCES "lotes"("Identificador"), "coste" REAL);

CREATE TABLE "recursos_maquina" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "centro_id" INTEGER REFERENCES "centros"("Identificador"), "codigo" TEXT, "nombre" TEXT, "coste_hora" REAL, "capacidad_hora" REAL, "activo" INTEGER);

CREATE TABLE "roles" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "nombre" TEXT, "descripcion" TEXT);

CREATE TABLE "roles_permisos" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "rol_id" INTEGER REFERENCES "roles"("Identificador"), "permiso_id" INTEGER REFERENCES "permisos"("Identificador"));

CREATE TABLE "rutas_fabricacion" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "producto_id" INTEGER REFERENCES "productos"("Identificador"), "version" TEXT, "activa" INTEGER);

CREATE TABLE "series_documentales" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "tipo_documento" TEXT, "prefijo" TEXT, "siguiente_numero" INTEGER, "longitud" INTEGER);

CREATE TABLE "solicitudes_compra" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "departamento_id" INTEGER REFERENCES "departamentos"("Identificador"), "solicitante_id" INTEGER REFERENCES "usuarios"("Identificador"), "fecha" TEXT, "estado" TEXT, "observaciones" TEXT);

CREATE TABLE "solicitudes_compra_lineas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "solicitud_id" INTEGER REFERENCES "solicitudes_compra"("Identificador"), "producto_id" INTEGER REFERENCES "productos"("Identificador"), "descripcion" TEXT, "cantidad" REAL, "fecha_necesidad" TEXT);

CREATE TABLE "stock" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "producto_id" INTEGER REFERENCES "productos"("Identificador"), "ubicacion_id" INTEGER REFERENCES "ubicaciones_almacen"("Identificador"), "lote_id" INTEGER REFERENCES "lotes"("Identificador"), "cantidad" REAL, "reservado" REAL);

CREATE TABLE "tareas_proyecto" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "proyecto_id" INTEGER REFERENCES "proyectos"("Identificador"), "padre_id" INTEGER REFERENCES "tareas_proyecto"("Identificador"), "responsable_id" INTEGER REFERENCES "empleados"("Identificador"), "titulo" TEXT, "descripcion" TEXT, "prioridad" TEXT, "estado" TEXT, "fecha_inicio" TEXT, "fecha_fin" TEXT, "horas_estimadas" REAL);

CREATE TABLE "tarifas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "codigo" TEXT, "nombre" TEXT, "moneda" INTEGER REFERENCES "monedas"("Identificador"), "activa" INTEGER);

CREATE TABLE "tarifas_lineas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "tarifa_id" INTEGER REFERENCES "tarifas"("Identificador"), "producto_id" INTEGER REFERENCES "productos"("Identificador"), "cantidad_desde" REAL, "precio" REAL, "descuento" REAL);

CREATE TABLE "terceros" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "tipo" TEXT, "codigo" TEXT, "razon_social" TEXT, "nombre_comercial" TEXT, "nif" TEXT, "email" TEXT, "telefono" TEXT, "web" TEXT, "forma_pago_id" INTEGER REFERENCES "formas_pago"("Identificador"), "limite_credito" REAL, "descuento" REAL, "activo" INTEGER, "creado_en" TEXT);

CREATE TABLE "terceros_etiquetas" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "tercero_id" INTEGER REFERENCES "terceros"("Identificador"), "etiqueta_id" INTEGER REFERENCES "etiquetas"("Identificador"));

CREATE TABLE "tickets_mensajes" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "ticket_id" INTEGER REFERENCES "tickets_soporte"("Identificador"), "usuario_id" INTEGER REFERENCES "usuarios"("Identificador"), "contacto_id" INTEGER REFERENCES "contactos"("Identificador"), "mensaje" TEXT, "creado_en" TEXT, "interno" INTEGER);

CREATE TABLE "tickets_soporte" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "tercero_id" INTEGER REFERENCES "terceros"("Identificador"), "contacto_id" INTEGER REFERENCES "contactos"("Identificador"), "responsable_id" INTEGER REFERENCES "usuarios"("Identificador"), "numero" TEXT, "asunto" TEXT, "descripcion" TEXT, "prioridad" TEXT, "estado" TEXT, "creado_en" TEXT, "cerrado_en" TEXT);

CREATE TABLE "tipos_cambio" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "moneda_origen" INTEGER REFERENCES "monedas"("Identificador"), "moneda_destino" INTEGER REFERENCES "monedas"("Identificador"), "fecha" TEXT, "cambio" REAL);

CREATE TABLE "tipos_iva" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "nombre" TEXT, "porcentaje" REAL, "recargo" REAL);

CREATE TABLE "ubicaciones_almacen" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "almacen_id" INTEGER REFERENCES "almacenes"("Identificador"), "codigo" TEXT, "nombre" TEXT, "tipo" TEXT);

CREATE TABLE "unidades_medida" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "codigo" TEXT, "nombre" TEXT, "factor_base" REAL);

CREATE TABLE "usuarios" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "empresa_id" INTEGER REFERENCES "empresas"("Identificador"), "username" TEXT, "password_hash" TEXT, "nombre" TEXT, "email" TEXT, "activo" INTEGER, "ultimo_acceso" TEXT, "creado_en" TEXT);

CREATE TABLE "usuarios_roles" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "usuario_id" INTEGER REFERENCES "usuarios"("Identificador"), "rol_id" INTEGER REFERENCES "roles"("Identificador"));

CREATE TABLE "variantes_producto" ("Identificador" INTEGER PRIMARY KEY AUTOINCREMENT, "producto_id" INTEGER REFERENCES "productos"("Identificador"), "codigo" TEXT, "nombre" TEXT, "precio_adicional" REAL);
