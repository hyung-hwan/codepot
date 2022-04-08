
sbin_SCRIPTS = codepot-user codepot-mon-httpd

EXTRA_DIST = $(sbin_SCRIPTS)

install-exec-hook:
	perl -e 'use Digest::SHA1' 2>/dev/null || \
	perl -e 'use Digest::SHA' 2>/dev/null && \
	sed -ri -e 's|Digest::SHA1|Digest::SHA|g' "$(DESTDIR)$(sbindir)/codepot-user"
