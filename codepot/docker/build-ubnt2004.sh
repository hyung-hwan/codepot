cd /tmp 

apt-get update && 
DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
	subversion mariadb-server mariadb-client apache2 \
	php libapache2-mod-php php-mysql php-gd \
	libapache2-mod-perl2 libapache2-mod-svn \
	libswitch-perl libconfig-simple-perl libdigest-sha-perl \
	libdbd-mysql-perl libnet-ldap-perl \
	php-dev libsvn-dev make 

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
mkdir -p /var/lib/codepot/svnrepo /var/lib/codepot/files && \
mkdir -p /var/cache/codepot /var/log/codepot && \
chown -R www-data:www-data /var/lib/codepot /var/cache/codepot /var/log/codepot && \
service mysql start && sleep 5 && \
mysql -e 'create database codepot' && \
mysql -e 'source /etc/codepot/codepot.mysql' codepot && \
mysql -e 'create user "codepot"@"localhost" identified by "codepot"' && \
mysql -e 'grant all privileges on codepot.* to "codepot"@"localhost"' && \
sed -ri -e 's|^database_hostname[[:space:]]*=[[:space:]]*""$|database_hostname = "localhost"|g' \
        -e 's|^database_username[[:space:]]*=[[:space:]]*""$|database_username = "codepot"|g' \
        -e 's|^database_password[[:space:]]*=[[:space:]]*""$|database_password = "codepot"|g' \
        -e 's|^database_name[[:space:]]*=[[:space:]]*""$|database_name = "codepot"|g' \
        -e 's|^database_driver[[:space:]]*=[[:space:]]*""$|database_driver = "mysqli"|g' /etc/codepot/codepot.ini &&  \
sed -ri -e 's|Digest::SHA1|Digest::SHA|g' /usr/sbin/codepot-user && \
sed -ri -e 's|Digest::SHA1|Digest::SHA|g' /etc/codepot/perl/Codepot/AccessHandler.pm && \
cd .. && \
cd .. && \
\
cp -pf /etc/codepot/codepot.httpd /etc/apache2/conf-enabled/codepot.conf && \
echo "PerlSwitches -Mlib=/etc/codepot/perl" >> /etc/apache2/conf-enabled/perl.conf 



apt-get remove -y php-dev libsvn-dev make && \
apt-get auto-remove -y && rm -rf /var/lib/apt/lists/*
