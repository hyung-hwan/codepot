ACLOCAL_AMFLAGS = -I ac/m4
AUTOMAKE_OPTION = foreign

SUBDIRS = etc src 

EXTRA_DIST = README mkmf.sh DEBIAN

distclean-local:
	$(RM) -r autom4te.cache config.h.in~ "$(PACKAGE)-$(VERSION)"

deb: dist-gzip
	tar -zxvf "$(PACKAGE)-$(VERSION).tar.gz"
	cd "$(PACKAGE)-$(VERSION)"; ./configure \
		--prefix=/usr \
		--sysconfdir=/etc \
		--with-wwwdir=/var/www/codepot \
		--with-cfgdir=/etc/codepot \
		--with-depotdir=/var/lib/codepot \
		--with-logdir=/var/log/codepot \
		--with-cachedir=/var/cache/codepot
	rm -rf "/var/tmp/$(PACKAGE)-$(VERSION)"
	cd "$(PACKAGE)-$(VERSION)"; make install DESTDIR="/var/tmp/$(PACKAGE)-$(VERSION)"
	cd "$(PACKAGE)-$(VERSION)/DEBIAN"; make install DESTDIR="/var/tmp/$(PACKAGE)-$(VERSION)"
#	cd "/var/tmp/$(PACKAGE)-$(VERSION)"; find . -type f -a ! -path '*/DEBIAN/*' -exec md5sum {} \; > "/var/tmp/$(PACKAGE)-$(VERSION)/DEBIAN/md5sums"
	fakeroot dpkg -b "/var/tmp/$(PACKAGE)-$(VERSION)"
	$(RM) -r "/var/tmp/$(PACKAGE)-$(VERSION)"
	$(RM) -r "$(PACKAGE)-$(VERSION)"
	mv -f "/var/tmp/$(PACKAGE)-$(VERSION).deb" .

