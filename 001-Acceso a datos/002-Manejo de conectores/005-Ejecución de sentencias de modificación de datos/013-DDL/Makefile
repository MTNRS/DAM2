CXX=g++
CXXFLAGS=-std=c++20 -O2 -Wall -Wextra -pedantic -fPIC
PYTHON=python3
PY_INCLUDES=$(shell $(PYTHON)-config --includes)
PY_SUFFIX=$(shell $(PYTHON)-config --extension-suffix)
PY_PLATLIB=$(shell $(PYTHON) -c "import sysconfig; print(sysconfig.get_path('platlib'))")
MODULE=jocarsabbdd$(PY_SUFFIX)

all: jocarsabbdd $(MODULE)

jocarsabbdd: main.cpp JocarsaBBDD.cpp JocarsaBBDD.hpp
	$(CXX) $(CXXFLAGS) main.cpp JocarsaBBDD.cpp -o jocarsabbdd

$(MODULE): conector_python.cpp JocarsaBBDD.cpp JocarsaBBDD.hpp
	$(CXX) $(CXXFLAGS) -shared $(PY_INCLUDES) conector_python.cpp JocarsaBBDD.cpp -o $(MODULE)

install: all
	install -d /etc/jocarsabbdd
	install -m 644 config.json /etc/jocarsabbdd/config.json
	install -d /var/jocarsa-basededatos
	@if [ -n "$$SUDO_USER" ]; then chown $$SUDO_USER:$$SUDO_USER /var/jocarsa-basededatos; fi
	install -m 755 jocarsabbdd /usr/local/bin/jocarsabbdd
	install -m 755 $(MODULE) $(PY_PLATLIB)/$(MODULE)
	@echo "Instalado. Prueba: cd /tmp && python3 -c 'import jocarsabbdd; print(jocarsabbdd.configuracion())'"

uninstall:
	rm -f /usr/local/bin/jocarsabbdd
	rm -f $(PY_PLATLIB)/jocarsabbdd*.so
	rm -rf /etc/jocarsabbdd
	@echo "Datos conservados en /var/jocarsa-basededatos"

clean:
	rm -f jocarsabbdd jocarsabbdd*.so
