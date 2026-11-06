CXX=g++
CXXFLAGS=-std=c++17 -O2 -Wall -Wextra -fPIC -Iinclude
LDLIBS=-ljson-c
all: jocarsabbdd libjocarsaorm.so
jocarsabbdd: src/main.cpp src/JocarsaBBDD.cpp include/JocarsaBBDD.hpp
	$(CXX) $(CXXFLAGS) src/main.cpp src/JocarsaBBDD.cpp $(LDLIBS) -o $@
libjocarsaorm.so: src/JocarsaBBDD.cpp src/JocarsaORM.cpp src/jocarsaorm_c.cpp
	$(CXX) $(CXXFLAGS) -shared $^ $(LDLIBS) -o $@
python:
	$(CXX) $(CXXFLAGS) -shared $$(python3-config --includes) src/conector_python.cpp src/JocarsaBBDD.cpp $(LDLIBS) -o jocarsabbdd$$(python3-config --extension-suffix)
php:
	$(CXX) $(CXXFLAGS) -shared $$(php-config --includes) -DCOMPILE_DL_JOCARSABBDD src/conector_php.cpp src/JocarsaBBDD.cpp $(LDLIBS) -o jocarsabbdd.so
clean:
	rm -f jocarsabbdd libjocarsaorm.so jocarsabbdd.so jocarsabbdd.cpython-*.so
.PHONY: all python php clean
