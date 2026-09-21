#include <stdio.h>

int main(int argc, char *argv[]) {

    int dia;
    int mes;
    for(mes = 1;mes<=12;mes++){
      for(dia = 1;dia<31;dia++){
        printf("Hoy es el dia %i del mes %i \n",dia,mes);
      }
    }
    return 0;
}