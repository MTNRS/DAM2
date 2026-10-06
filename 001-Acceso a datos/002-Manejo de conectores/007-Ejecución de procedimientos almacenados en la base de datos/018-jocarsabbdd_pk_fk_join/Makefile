CXX := g++
CXXFLAGS := -std=c++20 -O2 -Wall -Wextra -pedantic -fPIC
PYTHON := python3
PY_INCLUDES := $(shell $(PYTHON)-config --includes)
PY_EXT := $(shell $(PYTHON)-config --extension-suffix)
PREFIX ?= /usr/local
PY_SITE := $(shell $(PYTHON) -c "import sysconfig; print(sysconfig.get_paths()['platlib'])")

all: jocarsabbdd jocarsabbdd$(PY_EXT)

jocarsabbdd: main.cpp JocarsaBBDD.cpp JocarsaBBDD.hpp
	$(CXX) $(CXXFLAGS) main.cpp JocarsaBBDD.cpp -o $@

jocarsabbdd$(PY_EXT): conector_python.cpp JocarsaBBDD.cpp JocarsaBBDD.hpp
	$(CXX) $(CXXFLAGS) -shared $(PY_INCLUDES) conector_python.cpp JocarsaBBDD.cpp -o $@

install: all
	install -Dm755 jocarsabbdd $(PREFIX)/bin/jocarsabbdd
	install -Dm755 jocarsabbdd$(PY_EXT) $(PY_SITE)/jocarsabbdd$(PY_EXT)
	install -d /etc/jocarsabbdd
	@test -f /etc/jocarsabbdd/config.json || install -m644 config.json /etc/jocarsabbdd/config.json
	install -d /var/jocarsa-basededatos

uninstall:
	rm -f $(PREFIX)/bin/jocarsabbdd
	rm -f $(PY_SITE)/jocarsabbdd*.so

clean:
	rm -f jocarsabbdd jocarsabbdd*.so *.o

.PHONY: all install uninstall clean
