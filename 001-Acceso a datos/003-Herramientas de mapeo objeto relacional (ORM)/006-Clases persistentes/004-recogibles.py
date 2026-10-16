import random
import cv2
import numpy as np
import math

ANCHO = 500
ALTO = 500


class Recogible:
    def __init__(self, x, y, radio, color):
        self.x = x
        self.y = y
        self.radio = radio
        self.color = color

    def pintar(self, imagen):
        cv2.circle(
            imagen,
            (int(self.x), int(self.y)),
            self.radio,
            self.color,
            -1
        )


class Ser:
    def __init__(self, x, y, angulo, radio, color):
        self.x = x
        self.y = y
        self.angulo = angulo
        self.radio = radio
        self.color = color
        self.velocidad = 3

        # Lista de recogibles capturados
        self.recogidos = []

    def mover(self):
        self.x += math.cos(self.angulo) * self.velocidad
        self.y += math.sin(self.angulo) * self.velocidad

    def colision(self):
        if self.x - self.radio <= 0 or self.x + self.radio >= ANCHO:
            self.angulo = math.pi - self.angulo
            self.x = max(self.radio, min(ANCHO - self.radio, self.x))

        if self.y - self.radio <= 0 or self.y + self.radio >= ALTO:
            self.angulo = -self.angulo
            self.y = max(self.radio, min(ALTO - self.radio, self.y))

    def recoger(self, recogibles):
        for recogible in recogibles[:]:

            distancia = math.hypot(
                self.x - recogible.x,
                self.y - recogible.y
            )

            if distancia < self.radio + recogible.radio:
                self.recogidos.append(recogible)
                recogibles.remove(recogible)

    def pintar(self, imagen):

        # Pintar el personaje
        cv2.circle(
            imagen,
            (int(self.x), int(self.y)),
            self.radio,
            self.color,
            -1
        )

        # Pintar recogibles detrás del personaje
        for i, recogible in enumerate(self.recogidos):

            distancia = self.radio + recogible.radio + i * (recogible.radio * 2)

            recogible.x = self.x - math.cos(self.angulo) * distancia
            recogible.y = self.y - math.sin(self.angulo) * distancia

            recogible.pintar(imagen)


# Crear seres
seres = []
numero_de_seres = 5

for i in range(numero_de_seres):
    seres.append(
        Ser(
            random.randint(20, 480),
            random.randint(20, 480),
            random.uniform(0, 2 * math.pi),
            10,
            (0, 0, 255)
        )
    )


# Crear recogibles
recogibles = []
numero_de_recogibles = 100

for i in range(numero_de_recogibles):
    recogibles.append(
        Recogible(
            random.randint(10, 490),
            random.randint(10, 490),
            4,
            (0, 180, 0)
        )
    )


# Bucle principal
while True:

    imagen = np.ones(
        (ALTO, ANCHO, 3),
        dtype=np.uint8
    ) * 255

    # Pintar recogibles disponibles
    for recogible in recogibles:
        recogible.pintar(imagen)

    # Actualizar seres
    for ser in seres:
        ser.mover()
        ser.colision()
        ser.recoger(recogibles)
        ser.pintar(imagen)

    # Información
    cv2.putText(
        imagen,
        f"Recogibles restantes: {len(recogibles)}",
        (10, 20),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.5,
        (0, 0, 0),
        1
    )

    cv2.imshow("Simulacion", imagen)

    if cv2.waitKey(16) & 0xFF == 27:
        break

cv2.destroyAllWindows()