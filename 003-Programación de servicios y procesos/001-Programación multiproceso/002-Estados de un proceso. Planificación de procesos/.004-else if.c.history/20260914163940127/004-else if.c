#include <stdio.h>

int main() {
    int edad = 48;
    if(edad < 10){
    	printf("Eres un niño \n");
    }else if(edad >= 10 && edad < 20){
    	printf("Eres un adolescente \n");
    }
    return 0;
}