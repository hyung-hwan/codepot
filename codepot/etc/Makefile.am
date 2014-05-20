
cfgdir=$(CFGDIR)
cfg_DATA = codepot.ini codepot.mysql codepot.a2ldap 
cfg_SCRIPTS = start-commit pre-commit post-commit pre-revprop-change post-revprop-change  

perldir=$(CFGDIR)/perl/Codepot
perl_SCRIPTS=perl/Codepot/AccessHandler.pm perl/Codepot/AuthenHandler.pm

EXTRA_DIST = $(cfg_DATA)  $(cfg_SCRIPTS) $(perl_SCRIPTS) 


install-data-hook:
	$(INSTALL) -D -m 0644 "@abs_top_builddir@/etc/codepot.httpd" "$(DESTDIR)@sysconfdir@/httpd/conf.d/codepot.conf"

uninstall-hook:
	$(RM) -f "$(DESTDIR)@sysconfdir@/httpd/conf.d/codepot.conf"
