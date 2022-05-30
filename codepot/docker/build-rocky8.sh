cd /tmp 

## delete all files under /var/www/html
rm -rf /var/www/html/*

tar -zxvf codepot-0.4.0.tar.gz && mv -f codepot-0.4.0 codepot && \
touch -r *  */* */*/* */*/*/* */*/*/*/* && \
cd codepot && \
./configure \
	--prefix=/usr \
	--libdir=/usr/lib64 \
	--sysconfdir=/etc \
	--with-wwwdir=/var/www/html \
	--with-cfgdir=/etc/codepot \
	--with-depotdir=/var/lib/codepot \
	--with-logdir=/var/log/codepot \
	--with-cachedir=/var/cache/codepot \
	--with-phpextdir=`php-config --extension-dir` \
make && make install && \
cd ../.. && \
rm -rf /var/lib/codepot/* && \
sed -ri -e 's|^database_hostname[[:space:]]*=[[:space:]]*"localhost"$|database_hostname = "/var/lib/codepot/codepot.db"|g' \
        -e 's|^database_driver[[:space:]]*=[[:space:]]*""$|database_driver = "sqlite"|g' \
        -e 's|^database_use_pdo[[:space:]]*=[[:space:]]*"no"$|database_use_pdo = "yes"|g' /etc/codepot/codepot.ini &&  \
mkdir -p /run/php-fpm && \
cp -pf /etc/codepot/codepot.httpd /etc/httpd/conf.d/codepot.conf && \
echo "PerlSwitches -Mlib=/etc/codepot/perl" >> /etc/httpd/conf.d/perl.conf && \
sed -ri -e 's|^max_execution_time[[:space:]]*=.*$|max_execution_time = 120|g' /etc/php.ini


# mod_perl from the epel repo has dependency on perl-devel.
# so i can't get perl-devel removed.
dnf remove -y php-devel subversion-devel make gcc annobin && \
dnf autoremove -y && rm -rf /var/cache/yum/*

rm -rf /root/.subversion
