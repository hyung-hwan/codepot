EXTRA_DIST = conffiles copyright changelog

docdir=$(DOCDIR)
doc_DATA = copyright changelog

# Specifying debdir to /DEBIAN conflicts with 'make distcheck'
debdir=/DEBIAN
deb_DATA = control conffiles
deb_SCRIPTS = postinst postrm

install-data-hook:
	gzip -9 -f $(DESTDIR)$(DOCDIR)/changelog

uninstall-hook:
	$(RM) -f $(DESTDIR)$(DOCDIR)/changelog.gz

