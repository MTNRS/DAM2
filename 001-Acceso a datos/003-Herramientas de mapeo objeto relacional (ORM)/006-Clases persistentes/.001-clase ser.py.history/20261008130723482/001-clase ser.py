import random
class Ser:
  def __init__(self,x,y,angulo,radio,color):
    self.x = x
    self.y = y
    self.angulo = angulo
    self.radio = radio
    self.color = color
    
seres = []
numero_de_seres = 20

for i in range(0,numero_de_seres):
  seres.append(Ser(random.randint(0,500),random.randint(0,500),0,10,'red'))
  
print(seres)
