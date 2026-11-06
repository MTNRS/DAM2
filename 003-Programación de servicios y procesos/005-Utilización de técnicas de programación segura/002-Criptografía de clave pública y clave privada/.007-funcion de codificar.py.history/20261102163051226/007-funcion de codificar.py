

def codifica(entrada):
  cadena = ""
  for letra in nombre:
    codigoascii = ord(letra)
    mascinco = codigoascii + 5
    letracorrespondiente = chr(mascinco)
    #print(letra,":",codigoascii,"-",mascinco,":",letracorrespondiente)
    cadena += letracorrespondiente
	return cadena