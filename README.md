# DAM2

Repositorio de trabajo para segundo curso de Desarrollo de Aplicaciones Multiplataforma (DAM), curso 2026–2027.

## Organización

- `asignaturas/`: una carpeta por asignatura, con sus apuntes, ejercicios y prácticas.
- `proyectos/`: proyectos del curso y proyecto intermodular.
- `recursos/`: referencias y material de apoyo compartido entre asignaturas.

Al añadir una asignatura, se puede organizar así:

```text
asignaturas/nombre-asignatura/
├── apuntes/
├── ejercicios/
└── practicas/
```

Cada práctica o proyecto puede incluir un `README.md` con el enunciado, los requisitos y las instrucciones para ejecutarlo.

## Guardar avances

Desde esta carpeta:

```bash
git status
git add <archivos-o-carpetas>
git commit -m "Añade ejercicios de la unidad 1"
git push
```

Revisa los archivos antes de confirmar cambios. Las credenciales y los archivos `.env` deben permanecer locales; utiliza `.env.example` para documentar la configuración sin secretos.
