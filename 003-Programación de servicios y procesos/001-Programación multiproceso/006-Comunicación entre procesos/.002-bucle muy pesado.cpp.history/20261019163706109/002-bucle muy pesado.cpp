#include <iostream>

int main() {
    
    int numero = 1.0000000005345;
    int iteraciones = 10000000000000;
    for(int contador = 1;contador<iteraciones;contador++){
    	numero *= 1.00000000534;
    }
    return 0;

}