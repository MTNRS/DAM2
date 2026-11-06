
import secrets
import base64


def codifica(entrada, clave):
    cadena = bytearray()

    for i in range(0, len(entrada)):
        codigoascii = entrada[i]
        codigoclave = clave[i]

        codigoresultado = (codigoascii + codigoclave) % 256

        cadena.append(codigoresultado)

    return bytes(cadena)


def descodifica(entrada, clave):
    cadena = bytearray()

    for i in range(0, len(entrada)):
        codigocifrado = entrada[i]
        codigoclave = clave[i]

        codigoresultado = (codigocifrado - codigoclave) % 256

        cadena.append(codigoresultado)

    return bytes(cadena)


# Mensaje original
nombre = "Jose Vicente"

# Convertimos el texto en bytes
mensaje = nombre.encode("utf-8")

# Generamos una clave aleatoria del mismo tamaño
clave = secrets.token_bytes(len(mensaje))

# Ciframos
codificado = codifica(mensaje, clave)

# Desciframos
descodificado = descodifica(codificado, clave)

# Mostramos resultados
print("Original:", nombre)
print("Clave:", base64.b64encode(clave).decode())
print("Codificado:", base64.b64encode(codificado).decode())
print("Descodificado:", descodificado.decode("utf-8"))
