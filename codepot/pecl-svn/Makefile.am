EXTRA_DIST = config.m4 svn.c php_svn.h svn.ini

all-local:
	mkdir -p @abs_builddir@/pecl
	cp -pf @abs_srcdir@/svn.c @abs_builddir@/pecl/
	cp -pf @abs_srcdir@/php_svn.h @abs_builddir@/pecl/
	cp -pf @abs_srcdir@/config.m4 @abs_builddir@/pecl/
	cd @abs_builddir@/pecl/; phpize
	cd @abs_builddir@/pecl/; ./configure
	cd @abs_builddir@/pecl/; make

clean-local:
	-rm -rf @abs_builddir@/pecl

install-exec-hook:
	$(INSTALL) -D @abs_builddir@/pecl/.libs/svn.so $(DESTDIR)@PHPEXTDIR@/svn.so

install-data-hook:
	$(INSTALL) -D @abs_srcdir@/svn.ini $(DESTDIR)@PHPEXTINIDIR@/svn.ini

uninstall-hook:
	-rm -f @PHPEXTDIR@/svn.so $(DESTDIR)@PHPEXTINIDIR@/svn.ini
