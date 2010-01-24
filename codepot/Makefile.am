ACLOCAL_AMFLAGS = -I ac/m4
AUTOMAKE_OPTION = foreign

SUBDIRS = etc src DEBIAN

EXTRA_DIST = README mkmf.sh

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
	mkdir -p "/var/tmp/$(PACKAGE)-$(VERSION)/DEBIAN"
	cp "$(PACKAGE)-$(VERSION)/DEBIAN/control" "/var/tmp/$(PACKAGE)-$(VERSION)/DEBIAN/control"
	cp "$(PACKAGE)-$(VERSION)/DEBIAN/postinst" "/var/tmp/$(PACKAGE)-$(VERSION)/DEBIAN/postinst"
	cp "$(PACKAGE)-$(VERSION)/DEBIAN/postrm" "/var/tmp/$(PACKAGE)-$(VERSION)/DEBIAN/postrm"
	cp "$(PACKAGE)-$(VERSION)/DEBIAN/conffiles" "/var/tmp/$(PACKAGE)-$(VERSION)/DEBIAN/conffiles"
	chmod 0755 "/var/tmp/$(PACKAGE)-$(VERSION)/DEBIAN/postinst"
	chmod 0755 "/var/tmp/$(PACKAGE)-$(VERSION)/DEBIAN/postrm"
	fakeroot dpkg -b "/var/tmp/$(PACKAGE)-$(VERSION)"
	$(RM) -r "/var/tmp/$(PACKAGE)-$(VERSION)"
	$(RM) -r "$(PACKAGE)-$(VERSION)"
	mv -f "/var/tmp/$(PACKAGE)-$(VERSION).deb" .
