
#include <iostream>
#include <iomanip>

int main() {

    long double numero = 1.0000000005345L;
    long long iteraciones = 10000000000LL;

    for (long long contador = 1; contador < iteraciones; contador++) {
        numero *= 1.00000000534L;
    }

    std::cout << std::setprecision(20)
              << numero << std::endl;

    return 0;
}
