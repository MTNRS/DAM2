#include <stdio.h>
#include <stdlib.h>

int main(int argc, char *argv[]) {

    int edad = atoi(argv[1]); // Converte en entero - el argumento 1 de terminal

    if(edad < 10){
        printf("Eres un niño\n");
    }else if(edad >= 10 && edad < 20){
        printf("Eres un adolescente\n");
    }else if(edad >= 20 && edad < 30){
        printf("Eres un joven\n");
    }else if(edad >= 30 && edad < 40){
        printf("Eres un adulto\n");
    }else{
        printf("Eres mayor\n");
    }

    return 0;
}