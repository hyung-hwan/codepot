#!/bin/bash
set -e

CODEPOT_CONFIG_FILE="/var/lib/codepot/codepot.ini"
HTTPD_CONFIG_FILE="/etc/apache2/apache2.conf"
MPM_PREFORK_CONFIG_FILE="/etc/apache2/mods-available/mpm_prefork.conf"

SERVICE_PORT=""
HIDE_INDEX_PAGE=""
HTTPS_REDIRECTED=""
MPM_PREFORK_MAX_WORKERS=""
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

		hide-index-page)
			opt=${OPTARG}
			HIDE_INDEX_PAGE="${!OPTIND}"
			OPTIND=$(($OPTIND + 1))
			;;

		hide-index-page=*)
			HIDE_INDEX_PAGE=${OPTARG#*=}
			opt=${OPTARG%=$val}
			;;

		https-redirected)
			opt=${OPTARG}
			HTTPS_REDIRECTED="${!OPTIND}"
			OPTIND=$(($OPTIND + 1))
			;;

		https-redirected=*)
			HTTPS_REDIRECTED=${OPTARG#*=}
			opt=${OPTARG%=$val}
			;;

		mpm-prefork-max-workers=*)
			MPM_PREFORK_MAX_WORKERS=${OPTARG#*=}
			opt=${OPTARG%=$val}
			;;

		*)
			echo "Warning: unknown option - $OPTARG"
			;;
		esac
		;;

	h)
		echo "-------------------------------------------------------------------------"
		echo "This container runs a http service on port 80."
		echo "Use an external reverse proxy to enable https as it doesn't"
		echo "enable the HTTP service."
		echo "Extra options allowed when running the container: "
		echo " -h                         print this help message"
		echo " -p                number   specify the port number"
		echo " -port             number   specify the port number"
		echo " -hide-index-page  yes/no   hide/show the index page script from the URL"
		echo " -https-redirected yes/no   indicate if the requets are HTTPS redirected"
		echo "-------------------------------------------------------------------------"
		exit 0
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

## fall back to default values if the given values are not proper
echo "${SERVICE_PORT}" | grep -q -E '^[[:digit:]]+$' || SERVICE_PORT="80"
[[ "${HIDE_INDEX_PAGE}" == "" ]] && HIDE_INDEX_PAGE="no"
[[ "${HTTPS_REDIRECTED}" == "" ]] && HTTPS_REDIRECTED="no"


# Note: we don't just use "apache2ctl" here because it itself is just a shell-script wrapper around apache2 which provides extra functionality like "apache2ctl start" for launching apache2 in the background.
# (also, when run as "apache2ctl <apache args>", it does not use "exec", which leaves an undesirable resident shell process)

: "${APACHE_CONFDIR:=/etc/apache2}"
: "${APACHE_ENVVARS:=$APACHE_CONFDIR/envvars}"
if test -f "$APACHE_ENVVARS"; then
	. "$APACHE_ENVVARS"
fi

# Apache gets grumpy about PID files pre-existing
: "${APACHE_RUN_DIR:=/var/run/apache2}"
: "${APACHE_PID_FILE:=$APACHE_RUN_DIR/apache2.pid}"
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

chown www-data:www-data /var/lib/codepot

for i in /var/cache/codepot /var/log/codepot \
	/var/lib/codepot/attachments \
	/var/lib/codepot/files \
	/var/lib/codepot/issuefiles \
	/var/lib/codepot/svnrepo \
	/var/lib/codepot/usericons
do
	[ ! -d "$i" ] && {
		mkdir -p "$i"
		chown www-data:www-data "$i"
	}
done

[ ! -f /var/lib/codepot/codepot.db ] && {
	sqlite3 -init /etc/codepot/codepot.sqlite /var/lib/codepot/codepot.db ""
	chown www-data:www-data /var/lib/codepot/codepot.db
}

[ ! -f "${CODEPOT_CONFIG_FILE}" ] && {
	cp -pf /etc/codepot/codepot.ini "${CODEPOT_CONFIG_FILE}"
	chown www-data:www-data "${CODEPOT_CONFIG_FILE}"
}

grep -F -q  '<Location "/">' /etc/apache2/conf-enabled/codepot.conf || {
        cat <<EOF >> /etc/apache2/conf-enabled/codepot.conf
<Location "/">
        SetEnv CODEPOT_CONFIG_FILE ${CODEPOT_CONFIG_FILE}
</Location>
EOF
}

sed -r -i "s|PerlSetEnv CODEPOT_CONFIG_FILE .*\$|PerlSetEnv CODEPOT_CONFIG_FILE ${CODEPOT_CONFIG_FILE}|g" /etc/apache2/conf-enabled/codepot.conf


## change the port number as specified on the command line
echo "Configuring to listen on the port[$SERVICE_PORT] hide-index-page[$HIDE_INDEX_PAGE] https-redirected[$HTTPS_REDIRECTED] mpm-prefork-max-workers[$MPM_PREFORK_MAX_WORKERS]"

sed -r -i "s|^Listen[[:space:]]+.*|Listen ${SERVICE_PORT}|g" "/etc/apache2/ports.conf"
sed -r -i "s|^<VirtualHost .+$|<VirtualHost *:${SERVICE_PORT}>|g" "/etc/apache2/sites-available/000-default.conf"

if [[ "${HTTPS_REDIRECTED}" =~ [Yy][Ee][Ss] ]]
then
	## The DAV COPY request contains the header 'Destination: https://' if the origin request
	## is HTTPS. This container is configured to server on HTTP only. If HTTPS is redirected
	## to HTTP, we must translate https:// to http:// in the Destination header.
	## Otherwise, the response is 502 Bad Gateway.
	echo "RequestHeader edit Destination ^https: http: early" > /etc/apache2/conf-enabled/codepot-dav-https-redirected.conf
else
	rm -f /etc/apache2/conf-enabled/codepot-dav-https-redirected.conf
fi

if [[ "${HIDE_INDEX_PAGE}" =~ [Yy][Ee][Ss] ]]
then
	sed -r -i 's|^index_page[[:space:]]*=.*$|index_page=""|g' "${CODEPOT_CONFIG_FILE}"

        echo 'RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L]' > /var/www/html/.htaccess

	sed -r -i '/<Directory \/var\/www\/>/,/<\/Directory>/s|^[[:space:]]*AllowOverride[[:space:]]+.*$|\tAllowOverride All|g' "${HTTPD_CONFIG_FILE}"

else
	sed -r -i 's|^index_page[[:space:]]*=.*$|index_page="index.php"|g' "${CODEPOT_CONFIG_FILE}"
	rm -rf /var/www/html/.htaccess

	sed -r -i '/<Directory \/var\/www\/>/,/<\/Directory>/s|^[[:space:]]*AllowOverride[[:space:]]+.*$|\tAllowOverride None|g' "${HTTPD_CONFIG_FILE}"
fi

if [[ -n "${MPM_PREFORK_MAX_WORKERS}" && -f "${MPM_PREFORK_CONFIG_FILE}" ]]
then
	sed -r -i "s/^([[:space:]]*MaxRequestWorkers[[:space:]]+)[[:digit:]]+[[:space:]]*$/\1${MPM_PREFORK_MAX_WORKERS}/g" "${MPM_PREFORK_CONFIG_FILE}"
fi

#httpd server in the foreground
exec apache2 -DFOREGROUND
