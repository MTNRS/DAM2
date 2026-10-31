#include <iostream>
#include <iomanip>
#include <omp.h>

int main() {

    const long double inicial = 1.0000000005345L;
    const long double factor = 1.00000000534L;
    const long long iteraciones = 100000000000LL;

    long double resultado = 1.0L;

    double inicio = omp_get_wtime();

    #pragma omp parallel
    {
        #pragma omp single
        {
            std::cout << "Hilos utilizados: "
                      << omp_get_num_threads()
                      << std::endl;
        }

        #pragma omp for reduction(*:resultado)
        for (long long contador = 1;
             contador < iteraciones;
             contador++) {

            resultado *= factor;
        }
    }

    resultado *= inicial;

    double fin = omp_get_wtime();

    std::cout << std::setprecision(20);
    std::cout << "Resultado: " << resultado << std::endl;
    std::cout << "Tiempo: " << fin - inicio
              << " segundos" << std::endl;

    return 0;
}
