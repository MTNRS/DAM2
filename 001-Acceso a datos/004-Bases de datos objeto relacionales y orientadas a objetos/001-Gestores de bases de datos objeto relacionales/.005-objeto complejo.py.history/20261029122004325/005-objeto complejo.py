import yaml

objeto = {
    "nombre": "Empresa tecnológica",
    "activa": True,
    "empleados": [
        {
            "nombre": "Ana",
            "edad": 28,
            "habilidades": ["Python", "JavaScript", "SQL"]
        },
        {
            "nombre": "Luis",
            "edad": 35,
            "habilidades": ["Java", "Docker"]
        }
    ],
    "configuracion": {
        "servidor": "localhost",
        "puerto": 8080,
        "base_datos": {
            "motor": "SQLite",
            "archivo": "empresa.db"
        }
    }
}

# Guardar en YAML
with open("datos.yaml", "w", encoding="utf-8") as archivo:
    yaml.safe_dump(
        objeto,
        archivo,
        allow_unicode=True,
        sort_keys=False,
        default_flow_style=False
    )