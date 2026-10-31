
#include <iostream>
#include <vector>
#include <cmath>
#include <random>
#include <algorithm>
#include <string>
#include <iomanip>

using namespace std;

using Vector = vector<double>;
using Matrix = vector<Vector>;

const int D = 16;
const int FF = 32;
const int MAX_CTX = 16;

mt19937 rng(42);

// Pesos inicializados aleatoriamente
double aleatorio() {
    static normal_distribution<double> dist(0.0, 0.02);
    return dist(rng);
}

Matrix matriz(int filas, int columnas) {
    Matrix m(filas, Vector(columnas));
    for (auto &fila : m)
        for (double &x : fila)
            x = aleatorio();
    return m;
}

// Multiplicacion vector por matriz
Vector lineal(const Vector &x, const Matrix &w) {
    Vector salida(w[0].size(), 0.0);

    for (int j = 0; j < (int)salida.size(); j++)
        for (int i = 0; i < (int)x.size(); i++)
            salida[j] += x[i] * w[i][j];

    return salida;
}

Vector sumar(const Vector &a, const Vector &b) {
    Vector resultado = a;
    for (int i = 0; i < (int)a.size(); i++)
        resultado[i] += b[i];
    return resultado;
}

Vector normalizar(const Vector &x) {
    double media = 0, varianza = 0;

    for (double v : x) media += v;
    media /= x.size();

    for (double v : x)
        varianza += (v - media) * (v - media);

    varianza /= x.size();

    Vector resultado = x;
    for (double &v : resultado)
        v = (v - media) / sqrt(varianza + 1e-5);

    return resultado;
}

Vector softmax(const Vector &x) {
    double maximo = *max_element(x.begin(), x.end());
    Vector resultado(x.size());
    double suma = 0;

    for (int i = 0; i < (int)x.size(); i++) {
        resultado[i] = exp(x[i] - maximo);
        suma += resultado[i];
    }

    for (double &v : resultado) v /= suma;
    return resultado;
}

class Transformer {
    Matrix embeddings;
    Matrix Wq, Wk, Wv, Wo;
    Matrix W1, W2;
    Matrix Wsalida;

public:
    Transformer(int vocabulario) {
        embeddings = matriz(vocabulario, D);
        Wq = matriz(D, D);
        Wk = matriz(D, D);
        Wv = matriz(D, D);
        Wo = matriz(D, D);
        W1 = matriz(D, FF);
        W2 = matriz(FF, D);
        Wsalida = matriz(D, vocabulario);
    }

    Vector ejecutar(const vector<int> &tokens) {
        int n = tokens.size();
        Matrix x(n, Vector(D));

        // 1. Embeddings y posiciones sinusoidales
        for (int t = 0; t < n; t++) {
            x[t] = embeddings[tokens[t]];

            for (int i = 0; i < D; i++) {
                double angulo = t / pow(
                    10000.0, 2.0 * (i / 2) / D
                );

                x[t][i] += (i % 2 == 0)
                    ? sin(angulo) : cos(angulo);
            }
        }

        // 2. Queries, Keys y Values
        Matrix Q(n), K(n), V(n);

        for (int t = 0; t < n; t++) {
            Vector h = normalizar(x[t]);
            Q[t] = lineal(h, Wq);
            K[t] = lineal(h, Wk);
            V[t] = lineal(h, Wv);
        }

        // 3. Self-attention causal
        Matrix atencion(n, Vector(D, 0.0));

        for (int t = 0; t < n; t++) {
            Vector scores(t + 1);

            for (int j = 0; j <= t; j++) {
                double producto = 0;

                for (int d = 0; d < D; d++)
                    producto += Q[t][d] * K[j][d];

                scores[j] = producto / sqrt((double)D);
            }

            Vector probabilidades = softmax(scores);

            for (int j = 0; j <= t; j++)
                for (int d = 0; d < D; d++)
                    atencion[t][d] +=
                        probabilidades[j] * V[j][d];
        }

        // 4. Residual y Feed Forward
        for (int t = 0; t < n; t++) {
            x[t] = sumar(x[t], lineal(atencion[t], Wo));

            Vector h = lineal(normalizar(x[t]), W1);

            for (double &v : h)
                v = max(0.0, v); // ReLU

            x[t] = sumar(x[t], lineal(h, W2));
        }

        // 5. Logits del siguiente token
        return lineal(normalizar(x[n - 1]), Wsalida);
    }
};

int main() {
    vector<string> vocabulario = {
        "hola", "mundo", "soy", "un",
        "transformer", "pequeno", "que",
        "aprende", "a", "hablar", "."
    };

    Transformer modelo(vocabulario.size());

    vector<int> entrada = {0, 1, 2};

    cout << "Entrada: ";
    for (int id : entrada)
        cout << vocabulario[id] << " ";

    cout << "\n\n";

    Vector logits = modelo.ejecutar(entrada);
    Vector probabilidades = softmax(logits);

    cout << "Probabilidades del siguiente token:\n";

    for (int i = 0; i < (int)vocabulario.size(); i++) {
        cout << setw(14) << vocabulario[i]
             << " : " << fixed << setprecision(4)
             << probabilidades[i] * 100 << "%\n";
    }

    int elegido = max_element(
        probabilidades.begin(), probabilidades.end()
    ) - probabilidades.begin();

    cout << "\nPrediccion: " << vocabulario[elegido] << "\n";

    return 0;
}
