#!/bin/bash
set -e

SERVICE_PORT=""
while getopts ":hp:-:" oc
do
	case "${oc}" in
	-)
		case "${OPTARG}" in
		port)
			opt=${OPTARG}
			SERVICE_PORT="${!OPTIND}"
			OPTIND=$(($OPTIND + 1))
			;;
		port=*)
			SERVICE_PORT=${OPTARG#*=}
                    	opt=${OPTARG%=$val}
			;;

		*)
               		echo "Warning: unknown option - $OPTARG"
			;;
		esac
		;;

	h)
		echo "-----------------------------------------------------------"
		echo "This container runs a http service on port 80."
		echo "Use an external reverse proxy to enable https as it doesn't"
		echo "enable the HTTP service."
		echo "Extra options allowed when running the container: "
		echo " -h             print this help message"
		echo " -p    number   specify the port number"
		echo " -port number   specify the port number"
		echo "-----------------------------------------------------------"
		;;
	p)
		SERVICE_PORT=${OPTARG#*=}
                opt=${OPTARG%=$val}
		;;

	*)
               	echo "Warning: unknown option - $OPTARG"
		;;
	esac
done
echo "${SERVICE_PORT}" | grep -q -E '^[[:digit:]]+$' || SERVICE_PORT="80"


# Note: we don't just use "apache2ctl" here because it itself is just a shell-script wrapper around apache2 which provides extra functionality like "apache2ctl start" for launching apache2 in the background.
# (also, when run as "apache2ctl <apache args>", it does not use "exec", which leaves an undesirable resident shell process)

: "${APACHE_CONFDIR:=/etc/httpd}"
: "${APACHE_ENVVARS:=$APACHE_CONFDIR/envvars}"
if test -f "$APACHE_ENVVARS"; then
	. "$APACHE_ENVVARS"
fi

# Apache gets grumpy about PID files pre-existing
: "${APACHE_RUN_DIR:=/var/run/httpd}"
: "${APACHE_PID_FILE:=$APACHE_RUN_DIR/httpd.pid}"
rm -f "$APACHE_PID_FILE"

# create missing directories
# (especially APACHE_RUN_DIR, APACHE_LOCK_DIR, and APACHE_LOG_DIR)
for e in "${!APACHE_@}"; do
	if [[ "$e" == *_DIR ]] && [[ "${!e}" == /* ]]; then
		# handle "/var/lock" being a symlink to "/run/lock", but "/run/lock" not existing beforehand, so "/var/lock/something" fails to mkdir
		#   mkdir: cannot create directory '/var/lock': File exists
		dir="${!e}"
		while [ "$dir" != "$(dirname "$dir")" ]; do
			dir="$(dirname "$dir")"
			if [ -d "$dir" ]; then
				break
			fi
			absDir="$(readlink -f "$dir" 2>/dev/null || :)"
			if [ -n "$absDir" ]; then
				mkdir -p "$absDir"
			fi
		done

		mkdir -p "${!e}"
	fi
done


[ ! -d /var/lib/codepot/attachments ] && mkdir -p /var/lib/codepot/attachments
[ ! -d /var/lib/codepot/files ] && mkdir -p /var/lib/codepot/files
[ ! -d /var/lib/codepot/issuefiles ] && mkdir -p /var/lib/codepot/issuefiles
[ ! -d /var/lib/codepot/svnrepo ] && mkdir -p /var/lib/codepot/svnrepo
[ ! -d /var/lib/codepot/usericons ] && mkdir -p /var/lib/codepot/usericons
[ ! -f /var/lib/codepot/codepot.db ] && sqlite3 -init /etc/codepot/codepot.sqlite /var/lib/codepot/codepot.db ""

mkdir -p /var/cache/codepot /var/log/codepot
chown -R apache:apache /var/lib/codepot /var/cache/codepot /var/log/codepot

[ ! -f /var/lib/codepot/codepot.ini ] && cp -pf /etc/codepot/codepot.ini /var/lib/codepot/codepot.ini

grep -F -q 'env[CODEPOT_CONFIG_FILE]' /etc/php-fpm.d/www.conf || {
	echo 'env[CODEPOT_CONFIG_FILE] = /var/lib/codepot/codepot.ini' >> /etc/php-fpm.d/www.conf
}

## change the port number as specified on the command line
echo "Configuring to listen on the port [$SERVICE_PORT]"
sed -r -i "s|^Listen[[:space:]]+.*|Listen ${SERVICE_PORT}|g" /etc/httpd/conf/httpd.conf

## Enable DAV to work with Apache running HTTP through SSL hardware (problem description) 
## by replacing https: with http: in the Destination header:
##RequestHeader edit Destination ^https: http: early

php-fpm
exec httpd -DFOREGROUND
