CXX=g++
CXXFLAGS=-std=c++17 -O2 -Wall -Wextra -fPIC -Iinclude
all: jocarsabbdd libjocarsaorm.so
jocarsabbdd: src/main.cpp src/JocarsaBBDD.cpp
	$(CXX) $(CXXFLAGS) $^ -o $@
libjocarsaorm.so: src/JocarsaBBDD.cpp src/JocarsaORM.cpp src/jocarsaorm_c.cpp
	$(CXX) $(CXXFLAGS) -shared $^ -ljson-c -o $@
python:
	$(CXX) $(CXXFLAGS) -shared $$(python3-config --includes) src/conector_python.cpp src/JocarsaBBDD.cpp -o jocarsabbdd$$(python3-config --extension-suffix)
php:
	$(CXX) $(CXXFLAGS) -shared $$(php-config --includes) -DCOMPILE_DL_JOCARSABBDD src/conector_php.cpp src/JocarsaBBDD.cpp -o jocarsabbdd.so
clean:
	rm -f jocarsabbdd libjocarsaorm.so jocarsabbdd*.so
