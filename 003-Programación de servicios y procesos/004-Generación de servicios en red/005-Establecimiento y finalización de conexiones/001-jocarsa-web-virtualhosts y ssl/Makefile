CC = gcc
CFLAGS = -std=c11 -O2 -Wall -Wextra -D_DEFAULT_SOURCE
LANGSRC = $(wildcard lenguaje/src/*.c)
all: bin/jocarsa-lenguaje bin/jocarsa-servidor
bin:
	mkdir -p bin
bin/jocarsa-lenguaje: $(LANGSRC) | bin
	$(CC) $(CFLAGS) -o $@ $(LANGSRC) -lm
bin/jocarsa-servidor: servidor/servidor.c | bin
	$(CC) $(CFLAGS) -o $@ $< -lssl -lcrypto
clean:
	rm -rf bin
.PHONY: all clean
