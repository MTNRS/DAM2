empresas
  -codigo:TEXT
  -nombre:TEXT
  -razon_social:TEXT
  -nif:TEXT
  -email:TEXT
  -telefono:TEXT
  -web:TEXT
  -moneda_base:TEXT
  -activa:INTEGER
  -creado_en:TEXT

monedas
  -codigo:TEXT
  -nombre:TEXT
  -simbolo:TEXT
  -decimales:INTEGER

tipos_cambio
  -moneda_origen:INTEGER FK monedas
  -moneda_destino:INTEGER FK monedas
  -fecha:TEXT
  -cambio:REAL

paises
  -codigo:TEXT
  -nombre:TEXT

provincias
  -pais_codigo:INTEGER FK paises
  -nombre:TEXT

centros
  -empresa_id:INTEGER FK empresas
  -codigo:TEXT
  -nombre:TEXT
  -tipo:TEXT
  -direccion:TEXT
  -ciudad:TEXT
  -provincia_id:INTEGER FK provincias
  -codigo_postal:TEXT
  -pais_codigo:INTEGER FK paises
  -telefono:TEXT
  -activo:INTEGER

departamentos
  -empresa_id:INTEGER FK empresas
  -centro_id:INTEGER FK centros
  -padre_id:INTEGER FK departamentos
  -codigo:TEXT
  -nombre:TEXT
  -activo:INTEGER

roles
  -nombre:TEXT
  -descripcion:TEXT

permisos
  -codigo:TEXT
  -descripcion:TEXT

roles_permisos
  -rol_id:INTEGER FK roles
  -permiso_id:INTEGER FK permisos

usuarios
  -empresa_id:INTEGER FK empresas
  -username:TEXT
  -password_hash:TEXT
  -nombre:TEXT
  -email:TEXT
  -activo:INTEGER
  -ultimo_acceso:TEXT
  -creado_en:TEXT

usuarios_roles
  -usuario_id:INTEGER FK usuarios
  -rol_id:INTEGER FK roles

series_documentales
  -empresa_id:INTEGER FK empresas
  -tipo_documento:TEXT
  -prefijo:TEXT
  -siguiente_numero:INTEGER
  -longitud:INTEGER

formas_pago
  -empresa_id:INTEGER FK empresas
  -codigo:TEXT
  -nombre:TEXT
  -dias_vencimiento:INTEGER
  -porcentaje_anticipo:REAL
  -activa:INTEGER

bancos
  -nombre:TEXT
  -bic:TEXT

cuentas_bancarias
  -empresa_id:INTEGER FK empresas
  -banco_id:INTEGER FK bancos
  -iban:TEXT
  -moneda:INTEGER FK monedas
  -saldo_inicial:REAL
  -activa:INTEGER

terceros
  -empresa_id:INTEGER FK empresas
  -tipo:TEXT
  -codigo:TEXT
  -razon_social:TEXT
  -nombre_comercial:TEXT
  -nif:TEXT
  -email:TEXT
  -telefono:TEXT
  -web:TEXT
  -forma_pago_id:INTEGER FK formas_pago
  -limite_credito:REAL
  -descuento:REAL
  -activo:INTEGER
  -creado_en:TEXT

direcciones_terceros
  -tercero_id:INTEGER FK terceros
  -tipo:TEXT
  -direccion1:TEXT
  -direccion2:TEXT
  -ciudad:TEXT
  -provincia_id:INTEGER FK provincias
  -codigo_postal:TEXT
  -pais_codigo:INTEGER FK paises
  -principal:INTEGER

contactos
  -tercero_id:INTEGER FK terceros
  -nombre:TEXT
  -cargo:TEXT
  -email:TEXT
  -telefono:TEXT
  -movil:TEXT
  -principal:INTEGER

etiquetas
  -empresa_id:INTEGER FK empresas
  -nombre:TEXT
  -color:TEXT

terceros_etiquetas
  -tercero_id:INTEGER FK terceros
  -etiqueta_id:INTEGER FK etiquetas

leads
  -empresa_id:INTEGER FK empresas
  -responsable_id:INTEGER FK usuarios
  -nombre:TEXT
  -empresa:TEXT
  -email:TEXT
  -telefono:TEXT
  -origen:TEXT
  -estado:TEXT
  -valor_estimado:REAL
  -probabilidad:REAL
  -creado_en:TEXT

oportunidades
  -empresa_id:INTEGER FK empresas
  -tercero_id:INTEGER FK terceros
  -responsable_id:INTEGER FK usuarios
  -titulo:TEXT
  -fase:TEXT
  -importe:REAL
  -probabilidad:REAL
  -fecha_cierre_prevista:TEXT
  -estado:TEXT
  -creado_en:TEXT

actividades_crm
  -empresa_id:INTEGER FK empresas
  -tercero_id:INTEGER FK terceros
  -lead_id:INTEGER FK leads
  -oportunidad_id:INTEGER FK oportunidades
  -usuario_id:INTEGER FK usuarios
  -tipo:TEXT
  -asunto:TEXT
  -descripcion:TEXT
  -fecha_inicio:TEXT
  -fecha_fin:TEXT
  -completada:INTEGER

categorias_producto
  -empresa_id:INTEGER FK empresas
  -padre_id:INTEGER FK categorias_producto
  -codigo:TEXT
  -nombre:TEXT

unidades_medida
  -codigo:TEXT
  -nombre:TEXT
  -factor_base:REAL

tipos_iva
  -empresa_id:INTEGER FK empresas
  -nombre:TEXT
  -porcentaje:REAL
  -recargo:REAL

productos
  -empresa_id:INTEGER FK empresas
  -categoria_id:INTEGER FK categorias_producto
  -sku:TEXT
  -nombre:TEXT
  -descripcion:TEXT
  -tipo:TEXT
  -unidad_id:INTEGER FK unidades_medida
  -iva_id:INTEGER FK tipos_iva
  -precio_coste:REAL
  -precio_venta:REAL
  -peso:REAL
  -stock_minimo:REAL
  -stock_maximo:REAL
  -gestion_lotes:INTEGER
  -gestion_series:INTEGER
  -activo:INTEGER

variantes_producto
  -producto_id:INTEGER FK productos
  -codigo:TEXT
  -nombre:TEXT
  -precio_adicional:REAL

tarifas
  -empresa_id:INTEGER FK empresas
  -codigo:TEXT
  -nombre:TEXT
  -moneda:INTEGER FK monedas
  -activa:INTEGER

tarifas_lineas
  -tarifa_id:INTEGER FK tarifas
  -producto_id:INTEGER FK productos
  -cantidad_desde:REAL
  -precio:REAL
  -descuento:REAL

almacenes
  -empresa_id:INTEGER FK empresas
  -centro_id:INTEGER FK centros
  -codigo:TEXT
  -nombre:TEXT
  -activo:INTEGER

ubicaciones_almacen
  -almacen_id:INTEGER FK almacenes
  -codigo:TEXT
  -nombre:TEXT
  -tipo:TEXT

lotes
  -producto_id:INTEGER FK productos
  -lote:TEXT
  -fecha_fabricacion:TEXT
  -fecha_caducidad:TEXT

numeros_serie
  -producto_id:INTEGER FK productos
  -numero_serie:TEXT
  -estado:TEXT

stock
  -producto_id:INTEGER FK productos
  -ubicacion_id:INTEGER FK ubicaciones_almacen
  -lote_id:INTEGER FK lotes
  -cantidad:REAL
  -reservado:REAL

movimientos_stock
  -empresa_id:INTEGER FK empresas
  -producto_id:INTEGER FK productos
  -ubicacion_origen_id:INTEGER FK ubicaciones_almacen
  -ubicacion_destino_id:INTEGER FK ubicaciones_almacen
  -lote_id:INTEGER FK lotes
  -serie_id:INTEGER FK numeros_serie
  -tipo:TEXT
  -cantidad:REAL
  -coste_unitario:REAL
  -referencia:TEXT
  -fecha:TEXT
  -usuario_id:INTEGER FK usuarios

inventarios
  -empresa_id:INTEGER FK empresas
  -almacen_id:INTEGER FK almacenes
  -fecha:TEXT
  -estado:TEXT
  -usuario_id:INTEGER FK usuarios

inventarios_lineas
  -inventario_id:INTEGER FK inventarios
  -producto_id:INTEGER FK productos
  -ubicacion_id:INTEGER FK ubicaciones_almacen
  -cantidad_sistema:REAL
  -cantidad_contada:REAL

solicitudes_compra
  -empresa_id:INTEGER FK empresas
  -departamento_id:INTEGER FK departamentos
  -solicitante_id:INTEGER FK usuarios
  -fecha:TEXT
  -estado:TEXT
  -observaciones:TEXT

solicitudes_compra_lineas
  -solicitud_id:INTEGER FK solicitudes_compra
  -producto_id:INTEGER FK productos
  -descripcion:TEXT
  -cantidad:REAL
  -fecha_necesidad:TEXT

pedidos_compra
  -empresa_id:INTEGER FK empresas
  -numero:TEXT
  -proveedor_id:INTEGER FK terceros
  -almacen_id:INTEGER FK almacenes
  -fecha:TEXT
  -fecha_prevista:TEXT
  -moneda:INTEGER FK monedas
  -estado:TEXT
  -descuento:REAL
  -observaciones:TEXT
  -usuario_id:INTEGER FK usuarios

pedidos_compra_lineas
  -pedido_id:INTEGER FK pedidos_compra
  -producto_id:INTEGER FK productos
  -descripcion:TEXT
  -cantidad:REAL
  -precio:REAL
  -descuento:REAL
  -iva:REAL
  -recibida:REAL

recepciones_compra
  -empresa_id:INTEGER FK empresas
  -numero:TEXT
  -pedido_id:INTEGER FK pedidos_compra
  -proveedor_id:INTEGER FK terceros
  -almacen_id:INTEGER FK almacenes
  -fecha:TEXT
  -estado:TEXT

recepciones_compra_lineas
  -recepcion_id:INTEGER FK recepciones_compra
  -producto_id:INTEGER FK productos
  -cantidad:REAL
  -lote_id:INTEGER FK lotes
  -coste:REAL

presupuestos_venta
  -empresa_id:INTEGER FK empresas
  -numero:TEXT
  -cliente_id:INTEGER FK terceros
  -fecha:TEXT
  -valido_hasta:TEXT
  -moneda:INTEGER FK monedas
  -estado:TEXT
  -usuario_id:INTEGER FK usuarios
  -observaciones:TEXT

presupuestos_venta_lineas
  -presupuesto_id:INTEGER FK presupuestos_venta
  -producto_id:INTEGER FK productos
  -descripcion:TEXT
  -cantidad:REAL
  -precio:REAL
  -descuento:REAL
  -iva:REAL

pedidos_venta
  -empresa_id:INTEGER FK empresas
  -numero:TEXT
  -cliente_id:INTEGER FK terceros
  -presupuesto_id:INTEGER FK presupuestos_venta
  -fecha:TEXT
  -fecha_entrega:TEXT
  -moneda:INTEGER FK monedas
  -estado:TEXT
  -usuario_id:INTEGER FK usuarios
  -observaciones:TEXT

pedidos_venta_lineas
  -pedido_id:INTEGER FK pedidos_venta
  -producto_id:INTEGER FK productos
  -descripcion:TEXT
  -cantidad:REAL
  -precio:REAL
  -descuento:REAL
  -iva:REAL
  -servida:REAL

albaranes_venta
  -empresa_id:INTEGER FK empresas
  -numero:TEXT
  -pedido_id:INTEGER FK pedidos_venta
  -cliente_id:INTEGER FK terceros
  -almacen_id:INTEGER FK almacenes
  -fecha:TEXT
  -estado:TEXT

albaranes_venta_lineas
  -albaran_id:INTEGER FK albaranes_venta
  -producto_id:INTEGER FK productos
  -descripcion:TEXT
  -cantidad:REAL

facturas_venta
  -empresa_id:INTEGER FK empresas
  -numero:TEXT
  -cliente_id:INTEGER FK terceros
  -pedido_id:INTEGER FK pedidos_venta
  -fecha:TEXT
  -vencimiento:TEXT
  -moneda:INTEGER FK monedas
  -base_imponible:REAL
  -impuestos:REAL
  -total:REAL
  -pagado:REAL
  -estado:TEXT

facturas_venta_lineas
  -factura_id:INTEGER FK facturas_venta
  -producto_id:INTEGER FK productos
  -descripcion:TEXT
  -cantidad:REAL
  -precio:REAL
  -descuento:REAL
  -iva:REAL
  -total_linea:REAL

facturas_compra
  -empresa_id:INTEGER FK empresas
  -numero:TEXT
  -proveedor_id:INTEGER FK terceros
  -pedido_id:INTEGER FK pedidos_compra
  -fecha:TEXT
  -vencimiento:TEXT
  -moneda:INTEGER FK monedas
  -base_imponible:REAL
  -impuestos:REAL
  -total:REAL
  -pagado:REAL
  -estado:TEXT

facturas_compra_lineas
  -factura_id:INTEGER FK facturas_compra
  -producto_id:INTEGER FK productos
  -descripcion:TEXT
  -cantidad:REAL
  -precio:REAL
  -descuento:REAL
  -iva:REAL
  -total_linea:REAL

cobros
  -empresa_id:INTEGER FK empresas
  -factura_id:INTEGER FK facturas_venta
  -cliente_id:INTEGER FK terceros
  -cuenta_bancaria_id:INTEGER FK cuentas_bancarias
  -fecha:TEXT
  -importe:REAL
  -metodo:TEXT
  -referencia:TEXT

pagos
  -empresa_id:INTEGER FK empresas
  -factura_id:INTEGER FK facturas_compra
  -proveedor_id:INTEGER FK terceros
  -cuenta_bancaria_id:INTEGER FK cuentas_bancarias
  -fecha:TEXT
  -importe:REAL
  -metodo:TEXT
  -referencia:TEXT

planes_contables
  -empresa_id:INTEGER FK empresas
  -nombre:TEXT
  -ejercicio:INTEGER

cuentas_contables
  -plan_id:INTEGER FK planes_contables
  -codigo:TEXT
  -nombre:TEXT
  -tipo:TEXT
  -padre_id:INTEGER FK cuentas_contables

diarios_contables
  -empresa_id:INTEGER FK empresas
  -codigo:TEXT
  -nombre:TEXT

asientos
  -empresa_id:INTEGER FK empresas
  -diario_id:INTEGER FK diarios_contables
  -numero:INTEGER
  -fecha:TEXT
  -concepto:TEXT
  -referencia:TEXT
  -cerrado:INTEGER

asientos_lineas
  -asiento_id:INTEGER FK asientos
  -cuenta_id:INTEGER FK cuentas_contables
  -tercero_id:INTEGER FK terceros
  -concepto:TEXT
  -debe:REAL
  -haber:REAL

centros_coste
  -empresa_id:INTEGER FK empresas
  -codigo:TEXT
  -nombre:TEXT

presupuestos_financieros
  -empresa_id:INTEGER FK empresas
  -ejercicio:INTEGER
  -nombre:TEXT
  -estado:TEXT

presupuestos_financieros_lineas
  -presupuesto_id:INTEGER FK presupuestos_financieros
  -cuenta_id:INTEGER FK cuentas_contables
  -centro_coste_id:INTEGER FK centros_coste
  -mes:INTEGER
  -importe:REAL

empleados
  -empresa_id:INTEGER FK empresas
  -usuario_id:INTEGER FK usuarios
  -departamento_id:INTEGER FK departamentos
  -codigo:TEXT
  -nombre:TEXT
  -apellidos:TEXT
  -nif:TEXT
  -email:TEXT
  -telefono:TEXT
  -fecha_alta:TEXT
  -fecha_baja:TEXT
  -puesto:TEXT
  -salario_bruto_anual:REAL
  -activo:INTEGER

contratos_laborales
  -empleado_id:INTEGER FK empleados
  -tipo:TEXT
  -fecha_inicio:TEXT
  -fecha_fin:TEXT
  -jornada_horas:REAL
  -salario_anual:REAL

fichajes
  -empleado_id:INTEGER FK empleados
  -fecha:TEXT
  -entrada:TEXT
  -salida:TEXT
  -pausa_minutos:INTEGER

ausencias
  -empleado_id:INTEGER FK empleados
  -tipo:TEXT
  -fecha_inicio:TEXT
  -fecha_fin:TEXT
  -estado:TEXT
  -observaciones:TEXT

nominas
  -empleado_id:INTEGER FK empleados
  -anio:INTEGER
  -mes:INTEGER
  -bruto:REAL
  -seguridad_social:REAL
  -irpf:REAL
  -otros:REAL
  -neto:REAL

formaciones
  -empresa_id:INTEGER FK empresas
  -nombre:TEXT
  -proveedor:TEXT
  -horas:REAL
  -coste:REAL

empleados_formaciones
  -empleado_id:INTEGER FK empleados
  -formacion_id:INTEGER FK formaciones
  -fecha:TEXT
  -resultado:TEXT

proyectos
  -empresa_id:INTEGER FK empresas
  -cliente_id:INTEGER FK terceros
  -responsable_id:INTEGER FK empleados
  -codigo:TEXT
  -nombre:TEXT
  -fecha_inicio:TEXT
  -fecha_fin_prevista:TEXT
  -fecha_fin_real:TEXT
  -presupuesto:REAL
  -estado:TEXT

tareas_proyecto
  -proyecto_id:INTEGER FK proyectos
  -padre_id:INTEGER FK tareas_proyecto
  -responsable_id:INTEGER FK empleados
  -titulo:TEXT
  -descripcion:TEXT
  -prioridad:TEXT
  -estado:TEXT
  -fecha_inicio:TEXT
  -fecha_fin:TEXT
  -horas_estimadas:REAL

partes_horas
  -empleado_id:INTEGER FK empleados
  -proyecto_id:INTEGER FK proyectos
  -tarea_id:INTEGER FK tareas_proyecto
  -fecha:TEXT
  -horas:REAL
  -descripcion:TEXT
  -facturable:INTEGER

recursos_maquina
  -empresa_id:INTEGER FK empresas
  -centro_id:INTEGER FK centros
  -codigo:TEXT
  -nombre:TEXT
  -coste_hora:REAL
  -capacidad_hora:REAL
  -activo:INTEGER

listas_materiales
  -producto_id:INTEGER FK productos
  -version:TEXT
  -cantidad_base:REAL
  -activa:INTEGER

listas_materiales_lineas
  -lista_id:INTEGER FK listas_materiales
  -componente_id:INTEGER FK productos
  -cantidad:REAL
  -merma_pct:REAL

rutas_fabricacion
  -producto_id:INTEGER FK productos
  -version:TEXT
  -activa:INTEGER

operaciones_ruta
  -ruta_id:INTEGER FK rutas_fabricacion
  -secuencia:INTEGER
  -nombre:TEXT
  -maquina_id:INTEGER FK recursos_maquina
  -tiempo_preparacion:REAL
  -tiempo_unitario:REAL

ordenes_fabricacion
  -empresa_id:INTEGER FK empresas
  -numero:TEXT
  -producto_id:INTEGER FK productos
  -lista_id:INTEGER FK listas_materiales
  -ruta_id:INTEGER FK rutas_fabricacion
  -cantidad:REAL
  -fabricada:REAL
  -fecha_inicio:TEXT
  -fecha_fin_prevista:TEXT
  -fecha_fin_real:TEXT
  -estado:TEXT

consumos_fabricacion
  -orden_id:INTEGER FK ordenes_fabricacion
  -producto_id:INTEGER FK productos
  -cantidad_prevista:REAL
  -cantidad_real:REAL

producciones_fabricacion
  -orden_id:INTEGER FK ordenes_fabricacion
  -fecha:TEXT
  -cantidad_buena:REAL
  -cantidad_rechazada:REAL
  -lote_id:INTEGER FK lotes

activos_fijos
  -empresa_id:INTEGER FK empresas
  -codigo:TEXT
  -nombre:TEXT
  -fecha_compra:TEXT
  -valor_compra:REAL
  -valor_residual:REAL
  -vida_util_meses:INTEGER
  -cuenta_activo_id:INTEGER FK cuentas_contables
  -estado:TEXT

amortizaciones
  -activo_id:INTEGER FK activos_fijos
  -fecha:TEXT
  -importe:REAL
  -acumulada:REAL

tickets_soporte
  -empresa_id:INTEGER FK empresas
  -tercero_id:INTEGER FK terceros
  -contacto_id:INTEGER FK contactos
  -responsable_id:INTEGER FK usuarios
  -numero:TEXT
  -asunto:TEXT
  -descripcion:TEXT
  -prioridad:TEXT
  -estado:TEXT
  -creado_en:TEXT
  -cerrado_en:TEXT

tickets_mensajes
  -ticket_id:INTEGER FK tickets_soporte
  -usuario_id:INTEGER FK usuarios
  -contacto_id:INTEGER FK contactos
  -mensaje:TEXT
  -creado_en:TEXT
  -interno:INTEGER

documentos
  -empresa_id:INTEGER FK empresas
  -entidad:TEXT
  -entidad_id:INTEGER
  -nombre:TEXT
  -ruta:TEXT
  -mime:TEXT
  -tamano:INTEGER
  -creado_en:TEXT
  -usuario_id:INTEGER FK usuarios

notificaciones
  -usuario_id:INTEGER FK usuarios
  -titulo:TEXT
  -mensaje:TEXT
  -leida:INTEGER
  -creado_en:TEXT

configuracion
  -empresa_id:INTEGER FK empresas
  -clave:TEXT
  -valor:TEXT

auditoria
  -empresa_id:INTEGER FK empresas
  -usuario_id:INTEGER FK usuarios
  -fecha:TEXT
  -accion:TEXT
  -tabla:TEXT
  -registro_id:INTEGER
  -datos_anteriores:TEXT
  -datos_nuevos:TEXT
  -ip:TEXT
