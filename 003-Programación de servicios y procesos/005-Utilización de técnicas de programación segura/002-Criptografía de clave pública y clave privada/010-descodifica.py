
palabrasecreta = "Esto es un texto que estoy escribiendo para probar"

def codifica(entrada):
    cadena = ""

    for i in range(0, len(entrada)):
        codigoascii = ord(entrada[i])
        mascinco = codigoascii + 5

        letraclave = palabrasecreta[i]
        ordletraclave = ord(letraclave)

        superletracorrespondiente = chr(ordletraclave + mascinco)

        cadena += superletracorrespondiente

    return cadena


def descodifica(entrada):
    cadena = ""

    for i in range(0, len(entrada)):
        codigocifrado = ord(entrada[i])

        letraclave = palabrasecreta[i]
        ordletraclave = ord(letraclave)

        menosclave = codigocifrado - ordletraclave
        menoscinco = menosclave - 5

        letracorrespondiente = chr(menoscinco)

        cadena += letracorrespondiente

    return cadena


nombre = "Jose Vicente"

codificado = codifica(nombre)
descodificado = descodifica(codificado)

print("Original:", nombre)
print("Codificado:", codificado)
print("Descodificado:", descodificado)
