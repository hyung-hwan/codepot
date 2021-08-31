cd /tmp 

## epel-release for mod_perl
dnf install -y epel-release

dnf install -y \
	subversion subversion-perl \
	mariadb-server mariadb httpd \
	php php-mysqli php-gd \
	perl-Digest-SHA \
	perl-DBD-MySQL perl-LDAP \
	mod_dav_svn mod_perl \
	php-devel subversion-devel perl-devel make 

dnf remove -y mariadb-gssapi-server mariadb-backup

svn co http://code.miflux.com/svn/codepot/trunk/codepot && \
touch -r *  */* */*/* */*/*/* */*/*/*/* && \
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
make && make install && \
mkdir -p /var/lib/codepot/svnrepo /var/lib/codepot/files && \
mkdir -p /var/cache/codepot /var/log/codepot && \
chown -R apache:apache /var/lib/codepot /var/cache/codepot /var/log/codepot && \
mysql_install_db --user=mysql --ldata=/var/lib/mysql && \
(/usr/bin/mysqld_safe --datadir=/var/lib/mysql &) && sleep 5 && \
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
mkdir -p /run/php-fpm && 
install -m 0755 -D -t /usr/sbin docker/httpd-fg.sh && \
cd .. && \
cd .. && \
\
cp -pf /etc/codepot/codepot.httpd /etc/httpd/conf.d/codepot.conf && \
echo "PerlSwitches -Mlib=/etc/codepot/perl" >> /etc/httpd/conf.d/perl.conf 

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

dnf remove -y php-dev subversion-devel perl-devel make && \
dnf autoremove -y && rm -rf /var/lib/apt/lists/*

rm -rf /root/.subversion
