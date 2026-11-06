palabrasecreta = "Esto es un texto que estoy escribiendo para probar"

def codifica(entrada):
    for i in range(0,len(entrada)):
      	
        codigoascii = ord(entrada[i])
        mascinco = codigoascii + 5
        letracorrespondiente = chr(mascinco)
        letraclave = palabrasecreta[i]
        print(entrada[i],",",codigoascii,",",mascinco,",",letracorrespondiente,",",letraclave)
       
    return 0

codifica("Jose Vicente")

