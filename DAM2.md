# DAM2 · curso 2026–2027

Este repositorio personal sigue el material publicado por el profesor en
[`jocarsa/tame2627dam2`](https://github.com/jocarsa/tame2627dam2).

## Remotos

- `origin`: repositorio personal `MTNRS/DAM2`.
- `upstream`: repositorio del profesor `jocarsa/tame2627dam2`.

El material del profesor se integra en `main`. El trabajo personal puede
guardarse en `asignaturas/`, `proyectos/` y `recursos/` o dentro de las
carpetas del temario cuando corresponda.

## Actualizar desde el profesor

En PowerShell, desde la raíz del repositorio:

```powershell
.\actualizar-desde-profesor.ps1
```

El script descarga `upstream/main`, comprueba si hay novedades, las integra y
las sube a `origin/main`. Se detiene si existen cambios locales sin confirmar o
si Git detecta un conflicto.

GitHub también ejecuta esta sincronización automáticamente cada seis horas y
permite lanzarla manualmente desde la pestaña **Actions**.

## Trabajo personal

Antes de empezar una tarea:

```powershell
.\actualizar-desde-profesor.ps1
git switch -c trabajo/nombre-de-la-tarea
```

No guardes credenciales ni archivos `.env` en Git. Usa `.env.example` para
documentar las variables necesarias.
