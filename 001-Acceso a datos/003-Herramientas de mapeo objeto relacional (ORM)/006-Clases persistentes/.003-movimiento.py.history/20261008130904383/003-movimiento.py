import random
import cv2
import numpy as np
import math

class Ser:
    def __init__(self, x, y, angulo, radio, color):
        self.x = x
        self.y = y
        self.angulo = angulo
        self.radio = radio
        self.color = color
        self.velocidad = 3

    def mover(self):
        self.x += math.cos(self.angulo) * self.velocidad
        self.y += math.sin(self.angulo) * self.velocidad

    def colision(self):
        # Colisión con paredes izquierda y derecha
        if self.x - self.radio <= 0 or self.x + self.radio >= 500:
            self.angulo = math.pi - self.angulo
            self.x = max(self.radio, min(500 - self.radio, self.x))

        # Colisión con paredes superior e inferior
        if self.y - self.radio <= 0 or self.y + self.radio >= 500:
            self.angulo = -self.angulo
            self.y = max(self.radio, min(500 - self.radio, self.y))


seres = []
numero_de_seres = 20

for i in range(numero_de_seres):
    seres.append(
        Ser(
            random.randint(10, 490),
            random.randint(10, 490),
            random.uniform(0, 2 * math.pi),
            10,
            (0, 0, 255)
        )
    )

# Bucle de animación
while True:

    # Limpiar pantalla
    imagen = np.ones((500, 500, 3), dtype=np.uint8) * 255

    # Actualizar y pintar seres
    for ser in seres:
        ser.mover()
        ser.colision()

        cv2.circle(
            imagen,
            (int(ser.x), int(ser.y)),
            ser.radio,
            ser.color,
            -1
        )

    cv2.imshow("Seres", imagen)

    # ESC para salir
    if cv2.waitKey(16) & 0xFF == 27:
        break

cv2.destroyAllWindows()