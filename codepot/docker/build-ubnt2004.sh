cd /tmp 

#apt update && 
#DEBIAN_FRONTEND=noninteractive apt install -y --no-install-recommends \
#	subversion apache2 \
#	php libapache2-mod-php php-gd php-sqlite3 \
#	libapache2-mod-perl2 libapache2-mod-svn \
#	libswitch-perl libconfig-simple-perl libdigest-sha-perl \
#	libdbd-sqlite3-perl libnet-ldap-perl libsvn-perl libmail-sendmail-perl \
#	sqlite3 php-dev libsvn-dev make vim


svn co http://code.miflux.com/svn/codepot/trunk/codepot && \
cd codepot && \
./configure \
	--prefix=/usr \
	--libdir=/usr/lib64 \
	--sysconfdir=/etc \
	--with-wwwdir=/var/www/html/codepot \
	--with-cfgdir=/etc/codepot \
	--with-depotdir=/var/lib/codepot \
	--with-logdir=/var/log/codepot \
	--with-cachedir=/var/cache/codepot \
	--with-phpextdir=`php-config --extension-dir` \
	--with-phpextinidir=`php-config --ini-dir | sed 's|/cli/|/apache2/|g'` && \
make && make install && \
install -m 0755 -D -t /usr/sbin docker/apache2-fg.sh && \
cd ../.. && \
rm -rf /var/lib/codepot/* && \
sed -ri -e 's|^database_hostname[[:space:]]*=[[:space:]]*"localhost"$|database_hostname = "/var/lib/codepot/codepot.db"|g' \
        -e 's|^database_driver[[:space:]]*=[[:space:]]*""$|database_driver = "sqlite"|g' \
        -e 's|^database_use_pdo[[:space:]]*=[[:space:]]*"no"$|database_use_pdo = "yes"|g' /etc/codepot/codepot.ini &&  \
sed -ri -e 's|Digest::SHA1|Digest::SHA|g' /usr/sbin/codepot-user && \
sed -ri -e 's|Digest::SHA1|Digest::SHA|g' /etc/codepot/perl/Codepot/AccessHandler.pm && \
cp -pf /etc/codepot/codepot.httpd /etc/apache2/conf-enabled/codepot.conf && \
echo "PerlSwitches -Mlib=/etc/codepot/perl" >> /etc/apache2/conf-enabled/perl.conf 

cat <<EOF > /var/www/html/index.html
<html>
<head>
<title>Codepot</title>
<meta http-equiv="refresh" content="0;URL='/codepot'" />
</head>
<body>
<p>Access <a href="/codepot">this page</a> for codepot.</p>
</body>
</html>
EOF

apt remove --purge -y --allow-remove-essential php-dev libsvn-dev make libfdisk1 && \
apt autoremove --purge -y && rm -rf /var/lib/apt/lists/*

rm -rf /root/.subversion
