import random
import cv2
import numpy as np

class Ser:
    def __init__(self, x, y, angulo, radio, color):
        self.x = x
        self.y = y
        self.angulo = angulo
        self.radio = radio
        self.color = color

seres = []
numero_de_seres = 20

for i in range(numero_de_seres):
    seres.append(
        Ser(
            random.randint(0, 500),
            random.randint(0, 500),
            0,
            10,
            (0, 0, 255)
        )
    )

# Crear lienzo blanco
imagen = np.ones((500, 500, 3), dtype=np.uint8) * 255

# Pintar todos los seres
for ser in seres:
    cv2.circle(
        imagen,
        (ser.x, ser.y),
        ser.radio,
        ser.color,
        -1
    )

# Mostrar ventana
cv2.imshow("Seres", imagen)
cv2.waitKey(0)
cv2.destroyAllWindows()