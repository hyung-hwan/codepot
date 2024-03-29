ACLOCAL_AMFLAGS = -I ac/m4
AUTOMAKE_OPTION = foreign

SUBDIRS = etc src sbin pecl-svn 

EXTRA_DIST = README.md mkmf.sh DEBIAN docker

PHP_PACKAGE_NAME ?= php

install-data-hook:
	$(INSTALL) -d "$(DESTDIR)@DEPOTDIR@"
	$(INSTALL) -d "$(DESTDIR)@DEPOTDIR@/svnrepo"
	$(INSTALL) -d "$(DESTDIR)@DEPOTDIR@/files"
	$(INSTALL) -d "$(DESTDIR)@DEPOTDIR@/issuefiles"
	$(INSTALL) -d "$(DESTDIR)@DEPOTDIR@/attachments"
	$(INSTALL) -d "$(DESTDIR)@DEPOTDIR@/usericons"
	$(INSTALL) -d "$(DESTDIR)@LOGDIR@"
	$(INSTALL) -d "$(DESTDIR)@CACHEDIR@"
	[ -x /usr/bin/sqlite3 ] && /usr/bin/sqlite3 "$(DESTDIR)@DEPOTDIR@/codepot.db" < "@abs_srcdir@/etc/codepot.sqlite" || /bin/true

uninstall-hook:
	rm -f "$(DESTDIR)@DEPOTDIR@/codepot.db"
	$(RMDIR) "$(DESTDIR)@DEPOTDIR@/usericons"
	$(RMDIR) "$(DESTDIR)@DEPOTDIR@/attachments"
	$(RMDIR) "$(DESTDIR)@DEPOTDIR@/issuefiles"
	$(RMDIR) "$(DESTDIR)@DEPOTDIR@/files"
	$(RMDIR) "$(DESTDIR)@DEPOTDIR@/svnrepo"
	$(RMDIR) "$(DESTDIR)@DEPOTDIR@"
	$(RMDIR) "$(DESTDIR)@LOGDIR@"
	$(RMDIR) "$(DESTDIR)@CACHEDIR@"

distclean-local:
	$(RM) -r autom4te.cache config.h.in~ "$(PACKAGE)-$(VERSION)"


# TODO: specify --with-phpextdir and --with-phpextinidir for the deb target
deb: dist-gzip
	tar -zxvf "$(PACKAGE)-$(VERSION).tar.gz"
	cd "$(PACKAGE)-$(VERSION)"; PHPEXTDIR="`php5 --info 2>/dev/null | grep ^extension_dir | awk '{print $$5;}'`"; ./configure \
		--prefix=/usr \
		--sysconfdir=/etc \
		--with-wwwdir=/var/www/codepot \
		--with-cfgdir=/etc/codepot \
		--with-depotdir=/var/lib/codepot \
		--with-logdir=/var/log/codepot \
		--with-cachedir=/var/cache/codepot \
		--with-phpextdir="$${PHPEXTDIR}" \
		--with-phpextinidir=/etc/php5/conf.d
	rm -rf "/var/tmp/$(PACKAGE)-$(VERSION)"
	cd "$(PACKAGE)-$(VERSION)"; make install DESTDIR="/var/tmp/$(PACKAGE)-$(VERSION)"
	cd "$(PACKAGE)-$(VERSION)/DEBIAN"; make install DESTDIR="/var/tmp/$(PACKAGE)-$(VERSION)"
#	cd "/var/tmp/$(PACKAGE)-$(VERSION)"; find . -type f -a ! -path '*/DEBIAN/*' -exec md5sum {} \; > "/var/tmp/$(PACKAGE)-$(VERSION)/DEBIAN/md5sums"
	fakeroot dpkg -b "/var/tmp/$(PACKAGE)-$(VERSION)"
	$(RM) -r "/var/tmp/$(PACKAGE)-$(VERSION)"
	$(RM) -r "$(PACKAGE)-$(VERSION)"
	mv -f "/var/tmp/$(PACKAGE)-$(VERSION).deb" .

rpm: dist
	rm -rf "@abs_top_builddir@/RPM/BUILD"
	mkdir -p "@abs_top_builddir@/RPM/BUILD"
	mkdir -p "@abs_top_builddir@/RPM/SOURCES"
	mkdir -p "@abs_top_builddir@/RPM/SRPMS"
	mkdir -p "@abs_top_builddir@/RPM/RPMS"
	cp codepot-@VERSION@.tar.gz "@abs_top_builddir@/RPM/SOURCES"
	TARGET_ARCH="`uname -m`"; rpmbuild --define "_topdir @abs_top_builddir@/RPM" --define "php_package_name $(PHP_PACKAGE_NAME)" -ba codepot.spec --target="$${TARGET_ARCH}"
