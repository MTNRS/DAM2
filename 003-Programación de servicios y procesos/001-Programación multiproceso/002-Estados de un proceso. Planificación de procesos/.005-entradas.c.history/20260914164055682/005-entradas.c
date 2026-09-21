#include <stdio.h>

int main() {
    int edad = 48;
    if(edad < 10){
    	printf("Eres un niño \n");
    }else if(edad >= 10 && edad < 20){
    	printf("Eres un adolescente \n");
    }else if(edad >= 20 && edad < 30){
    	printf("Eres un joven \n");
    }else if(edad >= 30 && edad < 40){
    	printf("Eres un adulto \n");
    }else{
    	printf("Eres mayor \n");
    }
    return 0;
}