
dnl AC_PREREQ(2.59)

AC_INIT([codepot],[0.1.0],[Hyung-Hwan Chung <hyunghwan.chung@gmail.com>],,[http://www.abiyo.net/codepot])
AC_CONFIG_HEADER([./config.h])
AC_CONFIG_AUX_DIR([ac/aux])
AC_CONFIG_MACRO_DIR([ac/m4])

dnl initialize automake
AM_INIT_AUTOMAKE([-Wall -Werror foreign])

dnl check common tools
AC_CHECK_TOOL(LD, ld)
AC_CHECK_TOOL(GREP, grep)
AC_CHECK_TOOL(RM, rm)
AC_CHECK_TOOL(SED, sed)

RM="$RM -f"

# storage directory for file uploads and subversion repositories
depotdir="${localstatedir}/lib/${PACKAGE}"
AC_ARG_WITH(depotdir,
	AC_HELP_STRING(
		[--with-depotdir],
		[directory for file uploads and subversion repositories]),
	[depotdir=$withval])
AC_SUBST(depotdir, $depotdir)

# configuration direcotry
cfgdir="${sysconfdir}/${PACKAGE}"
AC_ARG_WITH(cfgdir,
	AC_HELP_STRING(
		[--with-cfgdir],
		[directory for configuration files]),
	[cfgdir=$withval])
AC_SUBST(cfgdir, $cfgdir)

# www document direcotry
wwwdir="${localstatedir}/www/${PACKAGE}"
AC_ARG_WITH(wwwdir,
	AC_HELP_STRING(
		[--with-wwwdir],
		[directory for www document files]),
	[wwwdir=$withval])
AC_SUBST(wwwdir, $wwwdir)

# log directory
logdir="${localstatedir}/log/${PACKAGE}"
AC_ARG_WITH(logdir,
	AC_HELP_STRING(
		[--with-logdir],
		[directory for log files]),
	[logdir=$withval])
AC_SUBST(logdir, $logdir)

# cache directory
cachedir="${localstatedir}/cache/${PACKAGE}"
AC_ARG_WITH(cachedir,
	AC_HELP_STRING(
		[--with-cachedir],
		[directory for cache files]),
	[cachedir=$withval])
AC_SUBST(cachedir, $cachedir)

AS_AC_EXPAND(PREFIX, $prefix)
AS_AC_EXPAND(LIBDIR, $libdir)
AS_AC_EXPAND(LIBEXECDIR, $libexecdir)
AS_AC_EXPAND(DATADIR, $datadir)
AS_AC_EXPAND(DOCDIR, $docdir)
AS_AC_EXPAND(SYSCONFDIR, $sysconfdir)
AS_AC_EXPAND(LOCALSTATEDIR, $localstatedir)
AS_AC_EXPAND(BINDIR, $bindir)
AS_AC_EXPAND(SBINDIR, $sbindir)

AS_AC_EXPAND(DEPOTDIR, $depotdir)
AS_AC_EXPAND(CFGDIR,   $cfgdir)
AS_AC_EXPAND(WWWDIR,   $wwwdir)
AS_AC_EXPAND(LOGDIR,   $logdir)
AS_AC_EXPAND(CACHEDIR, $cachedir)


AC_CONFIG_FILES([
	Makefile 
	etc/Makefile
	etc/codepot.ini
	etc/codepot.a2ldap
	DEBIAN/Makefile
	DEBIAN/control
	DEBIAN/postinst
	DEBIAN/postrm
	src/config.php
	src/codepot/config/Makefile
	src/codepot/controllers/Makefile
	src/codepot/errors/Makefile
	src/codepot/helpers/Makefile
	src/codepot/hooks/Makefile
	src/codepot/language/english/Makefile
	src/codepot/language/indonesian/Makefile
	src/codepot/language/korean/Makefile
	src/codepot/language/Makefile
	src/codepot/libraries/Makefile
	src/codepot/models/Makefile
	src/codepot/views/Makefile
	src/codepot/Makefile
	src/css/images/Makefile
	src/css/Makefile
	src/js/prettify/Makefile
	src/js/Makefile
	src/system/cache/Makefile
	src/system/codeigniter/Makefile
	src/system/database/drivers/mssql/Makefile
	src/system/database/drivers/mysql/Makefile
	src/system/database/drivers/mysqli/Makefile
	src/system/database/drivers/oci8/Makefile
	src/system/database/drivers/odbc/Makefile
	src/system/database/drivers/postgre/Makefile
	src/system/database/drivers/sqlite/Makefile
	src/system/database/drivers/Makefile
	src/system/database/Makefile
	src/system/fonts/Makefile
	src/system/helpers/Makefile
	src/system/language/english/Makefile
	src/system/language/korean/Makefile
	src/system/language/Makefile
	src/system/libraries/Makefile
	src/system/logs/Makefile
	src/system/plugins/Makefile
	src/system/scaffolding/images/Makefile
	src/system/scaffolding/views/Makefile
	src/system/scaffolding/Makefile
	src/system/Makefile
	src/websvn/cache/Makefile
	src/websvn/doc/Makefile
	src/websvn/include/Makefile
	src/websvn/languages/Makefile
	src/websvn/lib/geshi/Makefile
	src/websvn/lib/Makefile
	src/websvn/temp/Makefile
	src/websvn/templates/calm/images/Makefile
	src/websvn/templates/calm/star-light/smilies/Makefile
	src/websvn/templates/calm/star-light/src/Makefile
	src/websvn/templates/calm/star-light/Makefile
	src/websvn/templates/calm/Makefile
	src/websvn/templates/Makefile
	src/websvn/Makefile
	src/Makefile
])
AC_OUTPUT

