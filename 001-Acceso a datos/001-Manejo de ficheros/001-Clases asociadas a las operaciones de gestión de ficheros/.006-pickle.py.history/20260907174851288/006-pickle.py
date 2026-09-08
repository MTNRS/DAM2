import pickle


class Persona:
    def __init__(self, nombre, edad):
        self.nombre = nombre
        self.edad = edad

    def __str__(self):
        return f"Persona(nombre='{self.nombre}', edad={self.edad})"


# Crear una instancia de la clase Persona
persona = Persona("Juan", 30)

# Guardar el objeto en un archivo binario
with open('persona.pkl', 'wb') as file:
    pickle.dump(persona, file)

print("Objeto guardado en 'persona.pkl'")