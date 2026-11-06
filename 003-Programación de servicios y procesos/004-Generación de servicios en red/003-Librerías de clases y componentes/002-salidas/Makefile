CC = cc
CFLAGS = -std=c11 -O2 -Wall -Wextra
SRC = $(wildcard src/*.c)

all: jocarsa-lenguaje

jocarsa-lenguaje: $(SRC)
	$(CC) $(CFLAGS) -o $@ $(SRC)

prueba: jocarsa-lenguaje
	./jocarsa-lenguaje ejemplos/01_salidas.jocarsa

clean:
	rm -f jocarsa-lenguaje

.PHONY: all prueba clean
