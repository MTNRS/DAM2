nombre = "Jose Vicente"
print(nombre)
print(nombre[0])

for letra in nombre:
  codigoascii = ord(letra)
  mascinco = codigoascii + 5
  letracorrespondiente = chr(mascinco)
  print(letra,":",ord(letra),"-",(ord(letra)+5))