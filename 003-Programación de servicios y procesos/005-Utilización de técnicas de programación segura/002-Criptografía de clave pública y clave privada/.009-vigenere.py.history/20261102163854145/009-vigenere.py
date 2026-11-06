palabrasecreta = "Esto es un texto que estoy escribiendo para probar"

def codifica(entrada):
    cadena = ""
    for i in len(entrada):
      	
        codigoascii = ord(entrada[i])
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
print(nombre)
codificado = codifica(nombre)
print(codificado)
descodificado = descodifica(codificado)
print(descodificado)
