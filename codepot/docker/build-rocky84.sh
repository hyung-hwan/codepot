cd /tmp 

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
install -m 0755 -D -t /usr/sbin docker/httpd-fg.sh && \
cd ../.. && \
rm -rf /var/lib/codepot/* && \
sed -ri -e 's|^database_hostname[[:space:]]*=[[:space:]]*"localhost"$|database_hostname = "/var/lib/codepot/codepot.db"|g' \
        -e 's|^database_driver[[:space:]]*=[[:space:]]*""$|database_driver = "sqlite"|g' \
        -e 's|^database_use_pdo[[:space:]]*=[[:space:]]*"no"$|database_use_pdo = "yes"|g' /etc/codepot/codepot.ini &&  \
sed -ri -e 's|Digest::SHA1|Digest::SHA|g' /usr/sbin/codepot-user && \
sed -ri -e 's|Digest::SHA1|Digest::SHA|g' /etc/codepot/perl/Codepot/AccessHandler.pm && \
mkdir -p /run/php-fpm && \
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

# mod_perl has dependency on perl-devel which i think is wrong.
# so i can't get perl-devel removed.
dnf remove -y php-dev subversion-devel make && \
dnf autoremove -y && rm -rf /var/cache/yum/*

rm -rf /root/.subversion
