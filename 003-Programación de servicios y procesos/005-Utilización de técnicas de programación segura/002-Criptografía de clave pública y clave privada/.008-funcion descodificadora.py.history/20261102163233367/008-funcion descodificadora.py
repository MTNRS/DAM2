
def codifica(entrada):
    cadena = ""
    for letra in entrada:
        codigoascii = ord(letra)
        mascinco = codigoascii + 5
        letracorrespondiente = chr(mascinco)
        # print(letra, ":", codigoascii, "-", mascinco, ":", letracorrespondiente)
        cadena += letracorrespondiente
    return cadena
  
def descodifica(entrada):
    cadena = ""
    for letra in entrada:
        codigoascii = ord(letra)
        mascinco = codigoascii - 5
        letracorrespondiente = chr(mascinco)
        # print(letra, ":", codigoascii, "-", mascinco, ":", letracorrespondiente)
        cadena += letracorrespondiente
    return cadena

nombre = "Jose Vicente"
codificado = codifica(nombre)
