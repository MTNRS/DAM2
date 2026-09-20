#include <stdio.h>

int main() {
    float numero = 1.00000000054;
    for(int i = 0;i<10000000000000000;i++){
    	numero = numero*1.00000000534;
    }
    return 0;
}