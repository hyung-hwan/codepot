
dnl AC_PREREQ(2.59)

AC_INIT([codepot],[0.4.0],,,[http://code.abiyo.net/@codepot])
AC_CONFIG_HEADERS([./config.h])
AC_CONFIG_AUX_DIR([ac/aux])
AC_CONFIG_MACRO_DIR([ac/m4])

dnl initialize automake
AM_INIT_AUTOMAKE([-Wall -Werror foreign])

dnl check common tools
AC_CHECK_TOOL(LD, ld)
AC_CHECK_TOOL(GREP, grep)
AC_CHECK_TOOL(RM, rm)
AC_CHECK_TOOL(RMDIR, rmdir)
AC_CHECK_TOOL(SED, sed)

RM="$RM -f"

# storage directory for file uploads and subversion repositories
depotdir="${localstatedir}/lib/${PACKAGE}"
AC_ARG_WITH(depotdir,
	AS_HELP_STRING(
		[--with-depotdir],
		[directory for file uploads and subversion repositories]),
	[depotdir=$withval])
AC_SUBST(depotdir, $depotdir)

# configuration direcotry
cfgdir="${sysconfdir}/${PACKAGE}"
AC_ARG_WITH(cfgdir,
	AS_HELP_STRING(
		[--with-cfgdir],
		[directory for configuration files]),
	[cfgdir=$withval])
AC_SUBST(cfgdir, $cfgdir)

# www document direcotry
wwwdir="${localstatedir}/www/${PACKAGE}"
AC_ARG_WITH(wwwdir,
	AS_HELP_STRING(
		[--with-wwwdir],
		[directory for www document files]),
	[wwwdir=$withval])
AC_SUBST(wwwdir, $wwwdir)

# log directory
logdir="${localstatedir}/log/${PACKAGE}"
AC_ARG_WITH(logdir,
	AS_HELP_STRING(
		[--with-logdir],
		[directory for log files]),
	[logdir=$withval])
AC_SUBST(logdir, $logdir)

# cache directory
cachedir="${localstatedir}/cache/${PACKAGE}"
AC_ARG_WITH(cachedir,
	AS_HELP_STRING(
		[--with-cachedir],
		[directory for cache files]),
	[cachedir=$withval])
AC_SUBST(cachedir, $cachedir)

phpextdir="${libdir}/php/modules"
AC_ARG_WITH(phpextdir,
	AS_HELP_STRING(
		[--with-phpextdir],
		[directory where a php extension file is stored]),
	[phpextdir=$withval])
AC_SUBST(phpextdir, $phpextdir)

phpextinidir="${sysconfdir}/php.d"
AC_ARG_WITH(phpextinidir,
	AS_HELP_STRING(
		[--with-phpextinidir],
		[directory where a php extension is enabled]),
	[phpextinidir=$withval])
AC_SUBST(phpextinidir, $phpextinidir)

AS_AC_EXPAND(PREFIX, $prefix)
AS_AC_EXPAND(LIBDIR, $libdir)
AS_AC_EXPAND(LIBEXECDIR, $libexecdir)
AS_AC_EXPAND(DATAROOTDIR, $datarootdir)
AS_AC_EXPAND(DATADIR, $datadir)
AS_AC_EXPAND(DOCDIR, $docdir)
AS_AC_EXPAND(SYSCONFDIR, $sysconfdir)
AS_AC_EXPAND(LOCALSTATEDIR, $localstatedir)
AS_AC_EXPAND(BINDIR, $bindir)
AS_AC_EXPAND(SBINDIR, $sbindir)

AS_AC_EXPAND(DEPOTDIR,     $depotdir)
AS_AC_EXPAND(CFGDIR,       $cfgdir)
AS_AC_EXPAND(WWWDIR,       $wwwdir)
AS_AC_EXPAND(LOGDIR,       $logdir)
AS_AC_EXPAND(CACHEDIR,     $cachedir)
AS_AC_EXPAND(PHPEXTDIR,    $phpextdir)
AS_AC_EXPAND(PHPEXTINIDIR, $phpextinidir)


AC_CONFIG_FILES([
	Makefile 
	codepot.spec
	etc/Makefile
	etc/codepot.ini
	etc/codepot.a2ldap
	etc/codepot.httpd
	etc/pre-commit
	etc/start-commit
	etc/post-commit
	etc/pre-revprop-change
	etc/post-revprop-change
	sbin/Makefile
	sbin/codepot-user
	pecl-svn/Makefile
	DEBIAN/Makefile
	DEBIAN/control
	DEBIAN/postinst
	DEBIAN/postrm

	docker/Makefile

	src/Makefile
	src/config.php
	src/codepot/config/Makefile
	src/codepot/controllers/Makefile
	src/codepot/core/Makefile
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
	src/css/fonts/Makefile
	src/css/images/Makefile
	src/css/Makefile
	src/js/prettify/Makefile
	src/js/Makefile
	src/ace/Makefile

	src/system/fonts/Makefile
	src/system/libraries/javascript/Makefile
	src/system/libraries/Cache/drivers/Makefile
	src/system/libraries/Cache/Makefile
	src/system/libraries/Makefile
	src/system/language/english/Makefile
	src/system/language/Makefile
	src/system/helpers/Makefile
	src/system/core/Makefile
	src/system/database/drivers/odbc/Makefile
	src/system/database/drivers/pdo/Makefile
	src/system/database/drivers/postgre/Makefile
	src/system/database/drivers/mssql/Makefile
	src/system/database/drivers/cubrid/Makefile
	src/system/database/drivers/mysql/Makefile
	src/system/database/drivers/sqlsrv/Makefile
	src/system/database/drivers/oci8/Makefile
	src/system/database/drivers/sqlite/Makefile
	src/system/database/drivers/mysqli/Makefile
	src/system/database/drivers/Makefile
	src/system/database/Makefile
	src/system/Makefile

])
AC_OUTPUT

