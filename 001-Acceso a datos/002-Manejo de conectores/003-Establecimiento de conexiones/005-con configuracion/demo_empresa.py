#!/usr/bin/env python3

from JocarsaBBDD import JocarsaBBDD
import os


class Colores:
  RESET = "\033[0m"
  NEGRITA = "\033[1m"
  SUAVE = "\033[2m"
  ROJO = "\033[91m"
  VERDE = "\033[92m"
  AMARILLO = "\033[93m"
  AZUL = "\033[94m"
  MAGENTA = "\033[95m"
  CYAN = "\033[96m"
  BLANCO = "\033[97m"


class AplicacionEmpresa:

  def __init__(self):
    self.bbdd = JocarsaBBDD()
    self.nombreBaseDatos = "empresa_demo"
    self.ancho = 86


  def limpiar(self):
    os.system("cls" if os.name == "nt" else "clear")


  def linea(self, caracter="─"):
    print(Colores.CYAN + caracter * self.ancho + Colores.RESET)


  def centrar(self, texto, color=Colores.BLANCO):
    print(color + texto.center(self.ancho) + Colores.RESET)


  def cabecera(self, titulo, subtitulo="Aplicación empresarial CRUD"):
    self.limpiar()
    print(Colores.CYAN + "╔" + "═" * (self.ancho - 2) + "╗" + Colores.RESET)
    print(
      Colores.CYAN + "║" + Colores.RESET
      + Colores.NEGRITA + Colores.BLANCO
      + titulo.center(self.ancho - 2)
      + Colores.RESET + Colores.CYAN + "║" + Colores.RESET
    )
    print(
      Colores.CYAN + "║" + Colores.RESET
      + Colores.SUAVE
      + subtitulo.center(self.ancho - 2)
      + Colores.RESET + Colores.CYAN + "║" + Colores.RESET
    )
    print(Colores.CYAN + "╚" + "═" * (self.ancho - 2) + "╝" + Colores.RESET)
    print()


  def mensaje(self, simbolo, texto, color):
    print("  " + color + simbolo + Colores.RESET + "  " + texto)


  def exito(self, texto):
    self.mensaje("✔", texto, Colores.VERDE)


  def error(self, texto):
    self.mensaje("✘", texto, Colores.ROJO)


  def aviso(self, texto):
    self.mensaje("!", texto, Colores.AMARILLO)


  def info(self, texto):
    self.mensaje("●", texto, Colores.CYAN)


  def pausa(self):
    input("\n  " + Colores.SUAVE + "Pulsa ENTER para continuar..." + Colores.RESET)


  def pedir(self, texto, defecto=""):
    if defecto != "":
      respuesta = input(
        "  " + Colores.AMARILLO + "› " + Colores.RESET
        + texto + " "
        + Colores.SUAVE + "[" + str(defecto) + "]" + Colores.RESET
        + ": "
      ).strip()

      if respuesta == "":
        return str(defecto)

      return respuesta

    return input(
      "  " + Colores.AMARILLO + "› " + Colores.RESET + texto + ": "
    ).strip()


  def confirmar(self, texto):
    respuesta = input(
      "\n  " + Colores.AMARILLO + "? " + Colores.RESET
      + texto + " "
      + Colores.SUAVE + "[s/N]" + Colores.RESET + " "
    ).strip().lower()

    return respuesta in ["s", "si", "sí", "y", "yes"]


  def prepararBaseDatos(self):
    try:
      ruta = self.bbdd.instalacion + self.nombreBaseDatos

      if not os.path.isdir(ruta):
        self.bbdd.creaBaseDatos(self.nombreBaseDatos)

      self.bbdd.usaBaseDatos(self.nombreBaseDatos)

      rutaClientes = ruta + "/clientes.csv"

      if not os.path.isfile(rutaClientes):
        self.bbdd.creaTabla(
          "clientes",
          "nombre,apellidos,email,telefono,empresa"
        )

      rutaProductos = ruta + "/productos.csv"

      if not os.path.isfile(rutaProductos):
        self.bbdd.creaTabla(
          "productos",
          "nombre,categoria,precio,stock"
        )

    except Exception as error:
      print("Se ha producido un error preparando la base de datos:")
      print(error)
      raise


  def obtenerTodos(self, tabla):
    try:
      esquema = self.bbdd.obtenerEsquema(tabla)

      assert esquema != None, \
        "No se ha podido obtener el esquema"

      ruta = (
        self.bbdd.instalacion
        + self.bbdd.basededatos
        + "/"
        + tabla
        + ".csv"
      )

      resultados = []

      archivo = open(ruta, "rb")

      while True:
        bloque = archivo.read(self.bbdd.tamanoRegistro)

        if bloque == b"":
          break

        cadena = bloque.decode("utf-8").rstrip("\n").rstrip()

        if cadena != "":
          elementos = cadena.split(",")

          if len(elementos) == len(esquema):
            if elementos[1] == "1":
              registro = {}

              for i in range(len(esquema)):
                registro[esquema[i]] = elementos[i]

              resultados.append(registro)

      archivo.close()

      return resultados

    except Exception as error:
      self.error("No se han podido recuperar los registros.")
      print(error)
      return []


  def tabla(self, registros, columnas):
    if len(registros) == 0:
      self.aviso("No hay registros para mostrar.")
      return

    anchos = []

    for clave, titulo, ancho in columnas:
      anchos.append(ancho)

    borde = "  +"

    for ancho in anchos:
      borde += "-" * (ancho + 2) + "+"

    print(Colores.SUAVE + borde + Colores.RESET)

    cabecera = "  |"

    for i in range(len(columnas)):
      titulo = columnas[i][1]
      ancho = columnas[i][2]
      cabecera += " " + titulo[:ancho].ljust(ancho) + " |"

    print(Colores.NEGRITA + cabecera + Colores.RESET)
    print(Colores.SUAVE + borde + Colores.RESET)

    for registro in registros:
      fila = "  |"

      for clave, titulo, ancho in columnas:
        valor = str(registro.get(clave, ""))
        fila += " " + valor[:ancho].ljust(ancho) + " |"

      print(fila)

    print(Colores.SUAVE + borde + Colores.RESET)
    print(
      "\n  "
      + Colores.SUAVE
      + str(len(registros))
      + " registro(s)"
      + Colores.RESET
    )


  # ============================================================
  # CLIENTES
  # ============================================================

  def listarClientes(self):
    self.cabecera("CLIENTES", "Listado de clientes")

    clientes = self.obtenerTodos("clientes")

    self.tabla(
      clientes,
      [
        ("id", "ID", 4),
        ("nombre", "Nombre", 15),
        ("apellidos", "Apellidos", 20),
        ("email", "Email", 24),
        ("telefono", "Teléfono", 13)
      ]
    )


  def crearCliente(self):
    self.cabecera("NUEVO CLIENTE")

    nombre = self.pedir("Nombre")
    apellidos = self.pedir("Apellidos")
    email = self.pedir("Email")
    telefono = self.pedir("Teléfono")
    empresa = self.pedir("Empresa")

    if nombre == "":
      self.error("El nombre es obligatorio.")
      self.pausa()
      return

    id = self.bbdd.insertarDatos(
      "clientes",
      [nombre, apellidos, email, telefono, empresa]
    )

    if id != None:
      self.exito("Cliente creado con ID " + str(id))

    self.pausa()


  def editarCliente(self):
    self.listarClientes()

    id = self.pedir("ID del cliente que quieres editar")

    try:
      id = int(id)
    except:
      self.error("El ID debe ser numérico.")
      self.pausa()
      return

    cliente = self.bbdd.seleccionar("clientes", id)

    if cliente == None:
      self.error("No se ha encontrado el cliente.")
      self.pausa()
      return

    print()
    self.info("Deja el campo vacío para conservar el valor actual.")
    print()

    nombre = self.pedir("Nombre", cliente["nombre"])
    apellidos = self.pedir("Apellidos", cliente["apellidos"])
    email = self.pedir("Email", cliente["email"])
    telefono = self.pedir("Teléfono", cliente["telefono"])
    empresa = self.pedir("Empresa", cliente["empresa"])

    self.bbdd.actualizar(
      "clientes",
      id,
      [nombre, apellidos, email, telefono, empresa]
    )

    self.exito("Cliente actualizado.")
    self.pausa()


  def eliminarCliente(self):
    self.listarClientes()

    id = self.pedir("ID del cliente que quieres eliminar")

    try:
      id = int(id)
    except:
      self.error("El ID debe ser numérico.")
      self.pausa()
      return

    cliente = self.bbdd.seleccionar("clientes", id)

    if cliente == None:
      self.error("No se ha encontrado el cliente.")
      self.pausa()
      return

    print()
    self.info(
      "Cliente: "
      + cliente["nombre"]
      + " "
      + cliente["apellidos"]
    )

    if self.confirmar("¿Eliminar este cliente?"):
      self.bbdd.eliminar("clientes", id)
      self.exito("Cliente eliminado.")
    else:
      self.aviso("Operación cancelada.")

    self.pausa()


  def menuClientes(self):
    while True:
      self.cabecera("GESTIÓN DE CLIENTES")

      print("  " + Colores.CYAN + "[1]" + Colores.RESET + " Listar clientes")
      print("  " + Colores.CYAN + "[2]" + Colores.RESET + " Nuevo cliente")
      print("  " + Colores.CYAN + "[3]" + Colores.RESET + " Editar cliente")
      print("  " + Colores.CYAN + "[4]" + Colores.RESET + " Eliminar cliente")
      print()
      print("  " + Colores.SUAVE + "[0] Volver" + Colores.RESET)

      opcion = self.pedir("Selecciona una opción")

      if opcion == "1":
        self.listarClientes()
        self.pausa()
      elif opcion == "2":
        self.crearCliente()
      elif opcion == "3":
        self.editarCliente()
      elif opcion == "4":
        self.eliminarCliente()
      elif opcion == "0":
        break


  # ============================================================
  # PRODUCTOS
  # ============================================================

  def listarProductos(self):
    self.cabecera("PRODUCTOS", "Catálogo de productos")

    productos = self.obtenerTodos("productos")

    self.tabla(
      productos,
      [
        ("id", "ID", 4),
        ("nombre", "Producto", 28),
        ("categoria", "Categoría", 20),
        ("precio", "Precio", 12),
        ("stock", "Stock", 8)
      ]
    )


  def crearProducto(self):
    self.cabecera("NUEVO PRODUCTO")

    nombre = self.pedir("Nombre")
    categoria = self.pedir("Categoría")
    precio = self.pedir("Precio")
    stock = self.pedir("Stock")

    if nombre == "":
      self.error("El nombre es obligatorio.")
      self.pausa()
      return

    id = self.bbdd.insertarDatos(
      "productos",
      [nombre, categoria, precio, stock]
    )

    if id != None:
      self.exito("Producto creado con ID " + str(id))

    self.pausa()


  def editarProducto(self):
    self.listarProductos()

    id = self.pedir("ID del producto que quieres editar")

    try:
      id = int(id)
    except:
      self.error("El ID debe ser numérico.")
      self.pausa()
      return

    producto = self.bbdd.seleccionar("productos", id)

    if producto == None:
      self.error("No se ha encontrado el producto.")
      self.pausa()
      return

    print()
    self.info("Pulsa ENTER para conservar el valor actual.")
    print()

    nombre = self.pedir("Nombre", producto["nombre"])
    categoria = self.pedir("Categoría", producto["categoria"])
    precio = self.pedir("Precio", producto["precio"])
    stock = self.pedir("Stock", producto["stock"])

    self.bbdd.actualizar(
      "productos",
      id,
      [nombre, categoria, precio, stock]
    )

    self.exito("Producto actualizado.")
    self.pausa()


  def eliminarProducto(self):
    self.listarProductos()

    id = self.pedir("ID del producto que quieres eliminar")

    try:
      id = int(id)
    except:
      self.error("El ID debe ser numérico.")
      self.pausa()
      return

    producto = self.bbdd.seleccionar("productos", id)

    if producto == None:
      self.error("No se ha encontrado el producto.")
      self.pausa()
      return

    print()
    self.info("Producto: " + producto["nombre"])

    if self.confirmar("¿Eliminar este producto?"):
      self.bbdd.eliminar("productos", id)
      self.exito("Producto eliminado.")
    else:
      self.aviso("Operación cancelada.")

    self.pausa()


  def menuProductos(self):
    while True:
      self.cabecera("GESTIÓN DE PRODUCTOS")

      print("  " + Colores.CYAN + "[1]" + Colores.RESET + " Listar productos")
      print("  " + Colores.CYAN + "[2]" + Colores.RESET + " Nuevo producto")
      print("  " + Colores.CYAN + "[3]" + Colores.RESET + " Editar producto")
      print("  " + Colores.CYAN + "[4]" + Colores.RESET + " Eliminar producto")
      print()
      print("  " + Colores.SUAVE + "[0] Volver" + Colores.RESET)

      opcion = self.pedir("Selecciona una opción")

      if opcion == "1":
        self.listarProductos()
        self.pausa()
      elif opcion == "2":
        self.crearProducto()
      elif opcion == "3":
        self.editarProducto()
      elif opcion == "4":
        self.eliminarProducto()
      elif opcion == "0":
        break


  # ============================================================
  # DASHBOARD
  # ============================================================

  def dashboard(self):
    clientes = self.obtenerTodos("clientes")
    productos = self.obtenerTodos("productos")

    stockTotal = 0
    valorInventario = 0.0

    for producto in productos:
      try:
        stock = int(producto["stock"])
        precio = float(producto["precio"].replace(",", "."))

        stockTotal += stock
        valorInventario += stock * precio
      except:
        pass

    self.cabecera(
      "JOCARSA EMPRESA",
      "Demostración empresarial utilizando JocarsaBBDD"
    )

    print(
      "  ┌──────────────────────┐  "
      "┌──────────────────────┐  "
      "┌──────────────────────────────┐"
    )

    print(
      "  │ "
      + Colores.CYAN
      + "CLIENTES"
      + Colores.RESET
      + "             │  │ "
      + Colores.MAGENTA
      + "PRODUCTOS"
      + Colores.RESET
      + "            │  │ "
      + Colores.VERDE
      + "VALOR INVENTARIO"
      + Colores.RESET
      + "             │"
    )

    print(
      "  │ "
      + Colores.NEGRITA
      + str(len(clientes)).ljust(20)
      + Colores.RESET
      + " │  │ "
      + Colores.NEGRITA
      + str(len(productos)).ljust(20)
      + Colores.RESET
      + " │  │ "
      + Colores.NEGRITA
      + (("%.2f €" % valorInventario).ljust(28))
      + Colores.RESET
      + " │"
    )

    print(
      "  └──────────────────────┘  "
      "└──────────────────────┘  "
      "└──────────────────────────────┘"
    )

    print()
    print(
      "  "
      + Colores.SUAVE
      + "Unidades totales en inventario: "
      + str(stockTotal)
      + Colores.RESET
    )


  def ejecutar(self):
    try:
      self.prepararBaseDatos()

      while True:
        self.dashboard()

        print()
        self.linea()
        print()

        print(
          "  "
          + Colores.NEGRITA
          + "MENÚ PRINCIPAL"
          + Colores.RESET
        )
        print()
        print("  " + Colores.CYAN + "[1]" + Colores.RESET + " Gestión de clientes")
        print("  " + Colores.CYAN + "[2]" + Colores.RESET + " Gestión de productos")
        print("  " + Colores.CYAN + "[3]" + Colores.RESET + " Actualizar dashboard")
        print()
        print("  " + Colores.SUAVE + "[0] Salir" + Colores.RESET)

        opcion = self.pedir("Selecciona una opción")

        if opcion == "1":
          self.menuClientes()

        elif opcion == "2":
          self.menuProductos()

        elif opcion == "3":
          pass

        elif opcion == "0":
          self.cabecera("JOCARSA EMPRESA")
          self.centrar(
            "Gracias por utilizar la demostración.",
            Colores.VERDE
          )
          print()
          break

        else:
          self.aviso("Opción no válida.")
          self.pausa()

    except KeyboardInterrupt:
      print()
      self.aviso("Aplicación finalizada por el usuario.")

    except Exception as error:
      print()
      self.error("Se ha producido un error general:")
      self.error(str(error))


if __name__ == "__main__":
  aplicacion = AplicacionEmpresa()
  aplicacion.ejecutar()
