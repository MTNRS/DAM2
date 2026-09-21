#include <iostream>

class Persona {
public:
    Persona() {
        std::cout << "Persona constructor called." << std::endl;
    }

    void hacerAlgo() {
        std::cout << "Haciendo algo en Persona." << std::endl;
    }
};

int main() {
    Persona persona;
    persona.hacerAlgo();
    return 0;
}