#include <stdio.h>

int multiplicar(int numero1,int numero2){
	int multiplicacion = numero1 * numero2;
	return multiplicacion;
};

int main() {
    int resultado = multiplicacion(48,5);
    printf("El resultado de la operación %i \n",resultado);
    return 0;
}