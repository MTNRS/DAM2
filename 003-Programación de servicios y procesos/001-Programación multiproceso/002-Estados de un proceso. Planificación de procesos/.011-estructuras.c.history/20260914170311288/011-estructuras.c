#include <stdio.h>

struct Persona {
    char nombre[50];
    int edad;
};

int main() {
    int resultado = multiplicar(48,5);
    printf("El resultado de la operación %i \n",resultado);
    return 0;
}