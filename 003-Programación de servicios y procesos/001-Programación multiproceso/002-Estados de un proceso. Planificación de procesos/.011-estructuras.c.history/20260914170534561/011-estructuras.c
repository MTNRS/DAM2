#include <stdio.h>
#include <string.h>

struct Persona {
    char nombre[50];
    int edad;
};

int main() {
    struct Persona persona1;
    strcpy(persona1.nombre, "Jose Vicente");
    persona1.edad = 48;
    
    struct Persona persona2;
    strcpy(persona1.nombre, "Juan");
    persona1.edad = 52;
    
    printf("Persona 1 es: %s con edad %i",persona1.nombre,persona1.edad);
    return 0;
}