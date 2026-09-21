#include <stdio.h>
#include <string.h>

struct Persona {
    char nombre[50];
    int edad;
};

int main() {
    struct Persona persona1;
    strcpy(persona1.nombre, "Jose Vicente");
    persona1.edad = 48
    return 0;
}