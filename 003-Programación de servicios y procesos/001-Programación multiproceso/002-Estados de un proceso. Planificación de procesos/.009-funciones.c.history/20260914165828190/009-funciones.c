#include <stdio.h>

int doble(int numero){
	int doble = numero * 2;
	return doble;
};

int main() {

    int numerodoble = doble(48);
    printf("El doble de 48 es %i \n",numerodoble);
    return 0;
}