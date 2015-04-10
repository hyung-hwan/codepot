dnl $Id: config.m4 263540 2008-07-25 12:41:27Z tony2001 $
dnl config.m4 for extension svn
dnl vim:se ts=2 sw=2 et:

PHP_ARG_WITH(svn, for svn support,

[  --with-svn=[/path/to/svn-prefix]      Include svn support])

PHP_ARG_WITH(svn-apr, for specifying the location of apr for svn,

[  --with-svn-apr=[/path/to/apr-prefix]  Location of apr-1-config / apr-config])


if test "$PHP_SVN" != "no"; then

	AC_MSG_CHECKING([for svn includes])
	for i in $PHP_SVN /usr/local /usr /opt /sw; do
		if test -r $i/include/subversion-1/svn_client.h ; then
			SVN_DIR=$i/include/subversion-1
			PHP_SVN_INCLUDES="-I$SVN_DIR"
			PHP_SVN_LDFLAGS="-lsvn_client-1 -lsvn_fs-1 -lsvn_repos-1 -lsvn_subr-1"
			SVN_VER_MAJOR=`grep '#define SVN_VER_MAJOR' $SVN_DIR/svn_version.h|$SED 's/#define SVN_VER_MAJOR[ \t]*//;s/[ \t]*$//'`
			SVN_VER_MINOR=`grep '#define SVN_VER_MINOR' $SVN_DIR/svn_version.h|$SED 's/#define SVN_VER_MINOR[ \t]*//;s/[ \t]*$//'`
			SVN_VER_PATCH=`grep '#define SVN_VER_PATCH' $SVN_DIR/svn_version.h|$SED 's/#define SVN_VER_PATCH[ \t]*//;s/[ \t]*$//'`
			AC_MSG_RESULT(Found libsvn $SVN_VER_MAJOR.$SVN_VER_MINOR.$SVN_VER_PATCH)
			break;
		fi
	done

	if test "$PHP_SVN_LDFLAGS" = ""; then
		AC_MSG_ERROR([failed to find svn_client.h])
	fi

	dnl check SVN version, we need at least 1.3
	if test "$SVN_VER_MAJOR" -le 1 -a "$SVN_VER_MINOR" -le 3; then
		AC_MSG_ERROR([minimum libsvn is 1.3])
	fi

	AC_MSG_CHECKING([for apr and apr-util])
	for i in $PHP_SVN_APR $PHP_SVN /usr/local /usr /opt /sw; do
		dnl APR 1.0 tests
		if test -r $i/bin/apr-1-config ; then
			apr_config_path="$i/bin/apr-1-config"
			break;
		elif test -r $i/apache2/bin/apr-1-config ; then
			apr_config_path="$i/apache2/bin/apr-1-config"
			break;
		elif test -r $i/apache/bin/apr-1-config ; then
			apr_config_path="$i/apache/bin/apr-1-config"
			break;
		dnl APR 0.9 tests
		elif test -r $i/bin/apr-config ; then
			apr_config_path="$i/bin/apr-config"
			break;
		elif test -r $i/apache2/bin/apr-config ; then
			apr_config_path="$i/apache2/bin/apr-config"
			break;
		elif test -r $i/apache/bin/apr-config ; then
			apr_config_path="$i/apache/bin/apr-config"
			break;
		fi
	done

	if test "$apr_config_path" = ""; then
		AC_MSG_ERROR([failed to find apr-config / apr-1-config])
	fi

	APR_VERSION=`$apr_config_path --version`
	APR_INCLUDES=`$apr_config_path --includes --cppflags`
	APR_LDFLAGS=`$apr_config_path --link-ld`

	AC_MSG_RESULT(Found apr $APR_VERSION)

	PHP_SVN_INCLUDES="$PHP_SVN_INCLUDES $APR_INCLUDES"
	PHP_SVN_LDFLAGS="$PHP_SVN_LDFLAGS $APR_LDFLAGS"

	echo libsvn includes: \"$PHP_SVN_INCLUDES\"
	echo libsvn ldflags: \"$PHP_SVN_LDFLAGS\"

	AC_DEFINE(HAVE_SVNLIB,1,[ ])

	INCLUDES="$INCLUDES $PHP_SVN_INCLUDES"

	PHP_EVAL_LIBLINE($PHP_SVN_LDFLAGS, SVN_SHARED_LIBADD)
	PHP_SUBST(SVN_SHARED_LIBADD)

	PHP_NEW_EXTENSION(svn, svn.c, $ext_shared,,$PHP_SVN_INCLUDES)
fi
