CXX ?= g++
CXXFLAGS = -std=c++17 -O2 -Wall -Wextra -fPIC -Iinclude
LDFLAGS = -lsqlite3
PREFIX ?= /usr/local
all: libjocarsaorm.so jocarsaorm
libjocarsaorm.so: src/JocarsaORM.cpp src/jocarsaorm_c.cpp include/JocarsaORM.h include/jocarsaorm_c.h
	$(CXX) $(CXXFLAGS) -shared src/JocarsaORM.cpp src/jocarsaorm_c.cpp -o $@ $(LDFLAGS)
jocarsaorm: src/main.cpp libjocarsaorm.so
	$(CXX) $(CXXFLAGS) src/main.cpp -L. -ljocarsaorm -Wl,-rpath,'$$ORIGIN/../lib' -o $@
install: all
	install -d $(DESTDIR)$(PREFIX)/lib $(DESTDIR)$(PREFIX)/bin $(DESTDIR)$(PREFIX)/include
	install -m 755 libjocarsaorm.so $(DESTDIR)$(PREFIX)/lib/
	install -m 755 jocarsaorm $(DESTDIR)$(PREFIX)/bin/
	install -m 644 include/JocarsaORM.h include/jocarsaorm_c.h $(DESTDIR)$(PREFIX)/include/
uninstall:
	rm -f $(DESTDIR)$(PREFIX)/lib/libjocarsaorm.so $(DESTDIR)$(PREFIX)/bin/jocarsaorm $(DESTDIR)$(PREFIX)/include/JocarsaORM.h $(DESTDIR)$(PREFIX)/include/jocarsaorm_c.h
clean:
	rm -f libjocarsaorm.so jocarsaorm
.PHONY: all install uninstall clean
