
cfgdir=$(CFGDIR)
cfg_DATA = codepot.ini codepot.mysql codepot.pgsql codepot.sqlite codepot.a2ldap codepot.httpd
cfg_SCRIPTS = start-commit pre-commit post-commit pre-revprop-change post-revprop-change cloc.pl

perldir=$(CFGDIR)/perl/Codepot
perl_SCRIPTS=perl/Codepot/AccessHandler.pm perl/Codepot/AuthenHandler.pm

EXTRA_DIST = $(cfg_DATA) $(cfg_SCRIPTS) $(perl_SCRIPTS) 

install-exec-hook:
	perl -e 'use Digest::SHA1' 2>/dev/null || \
	perl -e 'use Digest::SHA' 2>/dev/null && \
	sed -ri -e 's|Digest::SHA1|Digest::SHA|g' "$(DESTDIR)$(CFGDIR)/perl/Codepot/AccessHandler.pm"
