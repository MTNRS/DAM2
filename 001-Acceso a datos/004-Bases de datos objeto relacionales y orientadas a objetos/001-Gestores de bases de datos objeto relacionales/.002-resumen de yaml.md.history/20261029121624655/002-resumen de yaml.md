# YAML — Resumen

YAML (YAML Ain't Markup Language) es un formato de serialización de datos diseñado para almacenar información estructurada de manera sencilla y legible. Se utiliza principalmente en archivos de configuración y para intercambiar datos entre aplicaciones.

## Características

- Utiliza indentación mediante espacios para representar jerarquías.
- No necesita llaves, corchetes ni etiquetas para estructurar los datos.
- Admite cadenas de texto, números, booleanos, listas, diccionarios y valores nulos.
- Permite comentarios utilizando el símbolo `#`.
- Es compatible con múltiples lenguajes de programación.
- Sus archivos utilizan las extensiones `.yaml` o `.yml`.

## Ejemplo

```yaml
persona:
  nombre: Ana
  edad: 25
  activo: true
  habilidades:
    - Python
    - JavaScript
  direccion:
    ciudad: Valencia
    codigo_postal: 46001
```

## Uso en Python

Instalación de la biblioteca:

```bash
pip install pyyaml
```

Lectura de un archivo YAML:

```python
import yaml

with open("datos.yaml", "r", encoding="utf-8") as archivo:
    datos = yaml.safe_load(archivo)

print(datos["persona"]["nombre"])
```

Escritura de un archivo YAML:

```python
import yaml

datos = {
    "nombre": "Ana",
    "edad": 25,
    "activo": True
}

with open("datos.yaml", "w", encoding="utf-8") as archivo:
    yaml.safe_dump(datos, archivo, allow_unicode=True)
```

## Ventajas

- Sintaxis sencilla y fácil de leer.
- Permite comentarios.
- Adecuado para configuraciones complejas.
- Facilita la organización jerárquica de la información.

## Desventajas

- Los errores de indentación pueden provocar fallos.
- Su procesamiento suele ser más complejo que JSON.
- No es el formato más adecuado para grandes volúmenes de datos.
- Es recomendable utilizar analizadores seguros al leer archivos externos.

## Aplicaciones

YAML se utiliza habitualmente en Docker Compose, Kubernetes, Ansible, GitHub Actions y archivos de configuración de aplicaciones.

## Conclusión

YAML es una buena alternativa a JSON y XML cuando se necesita almacenar información estructurada que deba ser fácilmente legible y editable por personas. Su principal aplicación es la configuración de sistemas y aplicaciones.