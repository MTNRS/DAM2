Sí, ahí está el problema: **la librería se ha instalado correctamente**, pero el constructor de `JocarsaBBDD` intenta abrir:

```text
config.json
```

como ruta relativa. Por tanto, al ejecutar desde `~`, busca:

```text
~/config.json
```

y al no encontrarlo lanza la excepción. Además, esa excepción está ocurriendo al inicializar el módulo, por eso aborta Python entero.

Para una librería instalada a nivel de sistema, **no deberíamos depender del directorio desde el que ejecutas Python**.

### Solución que recomiendo

Instalemos la configuración del motor en:

```text
/etc/jocarsabbdd/config.json
```

Primero:

```bash
sudo mkdir -p /etc/jocarsabbdd
```

y copia tu configuración:

```bash
sudo cp config.json /etc/jocarsabbdd/config.json
```

Después, en `JocarsaBBDD.cpp`, donde tengas algo equivalente a:

```cpp
std::string rutaConfig = "config.json";
```

cámbialo por:

```cpp
std::string rutaConfig = "/etc/jocarsabbdd/config.json";
```

Incluso mejor, podemos permitir **configuración local + configuración global**:

```cpp
std::string rutaConfig;

if(std::filesystem::exists("config.json")){
    rutaConfig = "config.json";
}
else if(std::filesystem::exists("/etc/jocarsabbdd/config.json")){
    rutaConfig = "/etc/jocarsabbdd/config.json";
}
else{
    throw std::runtime_error(
        "No se ha encontrado config.json"
    );
}
```

Así durante desarrollo:

```text
./mi-proyecto/config.json
```

tiene prioridad, pero desde cualquier script:

```text
/etc/jocarsabbdd/config.json
```

funciona como configuración global.

Luego recompila:

```bash
make clean
make
sudo make install
```

Y prueba **desde `/tmp`**, que es una buena prueba de que no depende de tu proyecto:

```bash
cd /tmp
python3 -c "import jocarsabbdd; print(jocarsabbdd.__file__)"
```

Debería aparecer:

```text
/usr/local/lib/python3.12/dist-packages/jocarsabbdd.cpython-312-x86_64-linux-gnu.so
```

Y después:

```bash
python3
```

```python
import jocarsabbdd

jocarsabbdd.crear_base("empresa")
jocarsabbdd.usar_base("empresa")
```

### Hay otra mejora importante

Yo corregiría también el constructor para que **un error de configuración nunca provoque un `Abortado (core generado)` al hacer simplemente `import jocarsabbdd`**.

Ahora seguramente tienes algo conceptualmente parecido a:

```cpp
static JocarsaBBDD db;
```

Ese objeto se construye durante:

```python
import jocarsabbdd
```

y si el constructor lanza:

```cpp
throw std::runtime_error(...)
```

la excepción C++ atraviesa la frontera Python/C y termina el proceso.

Eso hay que corregir. El comportamiento correcto debería ser:

```python
>>> import jocarsabbdd
```

funciona siempre, y si hay un problema:

```python
>>> jocarsabbdd.crear_base("empresa")
RuntimeError: No se ha encontrado la configuración...
```

**no matar Python**.

Yo haría ambas correcciones ahora: **config global en `/etc/jocarsabbdd/` + impedir que una excepción C++ pueda abortar el intérprete Python**. Es importante antes de seguir desarrollando el conector.
