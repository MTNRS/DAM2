import csv

archivo = open("agenda.csv", mode='r', newline='')
lector = csv.reader(archivo)

def process_csv(file_path):
    try:
        with open(file_path, mode='r', newline='') as file:
            reader = csv.reader(file)
            for row in reader:
                print(row)
    except FileNotFoundError:
        print(f"El archivo {file_path} no se encontró.")
    except Exception as e:
        print(f"Ocurrió un error al procesar el archivo: {e}")


if __name__ == "__main__":
    file_path = 'your_file.csv'  # Replace with the path to your CSV file
    process_csv(file_path)