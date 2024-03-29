# CODEPOT

 Codepot is a simple web-based source code manager. It incorporates the
 subversion revision control system and wiki-based documentation, and supports
 authentication via LDAP or DBMS. If you opt for the simplest, you can manage
 multiple subversion repositories independent of each other. Going beyond it,
 you can track issues, write documents, and upload release files.

## DOCKER CONTAINER

The easiest wasy to get codepot up and running is to run it in docker.
You may pull the image from the Docker Hub and run a container from the image.

For example, you may run the following commands to start the container.

```
$ docker pull hyunghwan/codepot:ubnt
$ docker run -dit --restart=unless-stopped --name=codepot -p 1080:1080 hyunghwan/codepot:ubnt --port=1080 --https-redirected=yes --hide-index-page=yes
```

Then you can open a web browser to http://docker-host-address:1080/ to access the codepot system.

The image runs the apache2 server in the foreground by default. You may open
a shell session to configure various aspects of codepot.

```
$ docker exec -it codepot /bin/bash
```

The first thing to do is to create a local user, assuming you use local user authentication.
In the shell session created, use the codepot-user command to create and enable a new user.

```
$ /usr/sbin/codepot-user add username password username@your.domain
$ /usr/sbin/codepot-user enable username
```

You can use the created user account to sign in to codepot and start creating a new project.

If you like to keep the data persistenly on the docker host, map /var/lib/codepot to a local
directory or a volume on the host when starting the container.

```
$ docker run -dit --restart=unless-stopped --name=codepot -p 1080:1080 -v ${HOME}/codepot-data:/var/lib/codepot hyunghwan/codepot:ubnt --port=1080 --https-redirected=yes --hide-index-page=yes
```

If you run this command, codepot stores all data under ${HOME}/codepot-data from the host 
perspective. The master configuration file is placed in ${HOME}/codepot-data. You may
edit ${HOME}/codepot-data/codepot.ini for configuration changes without entering the container.

## INSTALLATION ON CENTOS

A RPM package is provided for RedHat/CentOS Linux. The RPM package specifies
dependency which must be met prior to or at the same time as the installation
of the rpm package. 

```
$ rpm -ivh codepot-X.X.X-Y.Y.Y.Y.rpm
```

You can use the yum utility to be hassle-free instead. However, some required
packages are not available in the base CentOS repository but in the RPMforge
repository. You may be required to add the RPMforge repository to the system.
View http://wiki.centos.org/AdditionalResources/Repositories/RPMForge for 
RPMforge set-up.

```
$ yum localinstall --nogpgcheck codepot-X.X.X-Y.Y.Y.Y.rpm
```

Once you have all required package installed, you can proceed to configure
the system. The following steps shown assume the default installation of
CentOS 5.

X.X.X is the version number and Y.Y.Y.Y is the release number. For example,
to install Codepot 0.2.0 for a 32-bit x86 CentOS 5 server running PHP 5.3,
you should get the RPM package file - codepot-0.2.0-php53.1.el5.i686.rpm.

```
 1. Add the following line to /etc/httpd/conf.d/perl.conf. It must be placed 
 after 'LoadModule perl_module modules/mod_perl.so'.

   PerlSwitches -Mlib=/etc/codepot/perl

 2. Customize the Subversion WebDAV access in /etc/httpd/conf.d/codepot.conf
 which the RPM package copies from /etc/codepot/codepot.http upon fresh
 installation. The default file installed specify the location '/svn'. It
 must match the path part of the subversion base URL(svn_base_url) specified
 in /etc/codepot/codepot.ini. You can skip this step if you don't need any
 customizations.

 3. Create a database named 'codepot' into the running MySQL server. The
 schema is defined in /etc/codepot/codepot.mysql. You can execute the following
 in the MySQL command prompt.

   mysql> create database codepot;
   mysql> use codepot;
   mysql> source /etc/codepot/codepot.mysql;

 4. Customize various configuration items in /etc/codepot/codepot.ini.
 Set all items beginning with database_  to match the database settings.
 Customize the values to match your system database.
 
   database_hostname = "localhost"
   database_username = "root"
   database_password = ""
   database_name = "codepot"
   database_driver = "mysqli"
   database_prefix = ""

 5. Add a local user using the 'codepot-user' command. This command is used
 to add a user to the database and a user entry added is effective if codepot
 is configured to use 'DbLoginModel'. You can change it by chaning the value
 of 'login_mode' in /etc/codepot/codepot.ini.

   $ /usr/sbin/codepot-user add username password username@your.domain

 6. Enable the user added.
   
   $ /usr/sbin/codepot-user enable username

 7. Optionally, you can set this user to be a system administrator
 in /etc/codepot/codepot.ini.

   sysadmin_userids = "username"

 8. If you don't have the right subversion extension for PHP installed, you can
 install the codepot-peclsvn package. Codepot requires the 'svn.so' extension 
 built of at least r333800 from http://svn.php.net/repository/pecl/svn/trunk.

   $ rpm -ivh codepot-peclsvn-X.X.X-Y.Y.Y.Y.rpm

 9. Check if you have SELinux on and its current mode with the sestatus command.

   $ /usr/sbin/sestatus
   SELinux status:                 enabled
   SELinuxfs mount:                /selinux
   Current mode:                   enforcing
   Mode from config file:          enforcing
   Policy version:                 21
   Policy from config file:        targeted

 If you have SELinux on in the enforcing mode, check if the data directories
 used by Codepot has the right context set. The 'httpd_sys_content_t' context
 type should be set. The Codepot RPM package sets the context type upon fresh 
 installation only.

   $ ls -Zd /var/lib/codepot
   drwxr-xr-x  root root user_u:object_r:httpd_sys_content_t /var/lib/codepot

   $ ls -Z /var/lib/codepot
   drwxr-xr-x  apache apache system_u:object_r:httpd_sys_content_t attachments
   drwxr-xr-x  apache apache system_u:object_r:httpd_sys_content_t files
   drwxr-xr-x  apache apache system_u:object_r:httpd_sys_content_t svnrepo
   drwxr-xr-x  apache apache system_u:object_r:httpd_sys_content_t usericons

   $ ls -lZd /var/cache/codepot
   drwxr-xr-x  apache apache system_u:object_r:httpd_sys_content_t /var/cache/codepot

 The following commands may help.
   $ chcon -R -t httpd_sys_content_t /var/lib/codepot
   $ chcon -R -t httpd_sys_content_t /var/cache/codepot


 If it doesn't work with 'httpd_sys_content_t', you may require 'httpd_sys_content_rw_t'.
   $ chcon -R -t httpd_sys_content_rw_t /var/lib/codepot
   $ chcon -R -t httpd_sys_content_rw_t /var/cache/codepot

 10. If you have SELinux on in the enforcing mode, ensure to allow httpd to
 execute an external command.

   $ /usr/sbin/getsebool httpd_ssi_exec
   httpd_ssi_exec --> off

 If httpd_ssi_exec is off, switch it to on.

   $ /usr/sbin/setsebool -P httpd_ssi_exec=1
   $ /usr/sbin/getsebool httpd_ssi_exec
   httpd_ssi_exec --> on

 The only external command Codepot executes is '/etc/codepot/cloc.pl' which
 is configured under 'cloc_command_path' in /etc/codepot/codepot.ini. Some
 CLOC graphs won't work properly when the execution of this command fails.

 11. Restart httpd.

   $ service httpd restart

 12. Access http://your-server/codepot/
```

## INSTALLATION ON DEBIAN

Here is how to install Codepot into the standard locations under Debian Linux.

```
 * Install required packages.
   $ sudo apt-get install subversion
   $ sudo apt-get install apache2-mpm-prefork
   $ sudo apt-get install libapache2-svn
   $ sudo apt-get install libapache2-mod-auth-mysql # optional
   $ sudo apt-get install php5 php5-ldap php5-mysql
   $ sudo apt-get install php5-svn # if available

 * Install the mysql server if you want to store projects in a local mysql 
   database.
   $ sudo apt-get install mysql-server

 * Install the openldap server if you want to use a local LDAP server.
   $ sudo apt-get install slapd

 * Enable the apache server modules
   $ sudo a2enmod php5
   $ sudo a2enmod authnz_ldap # enable ldap-based authentication
   $ sudo a2enmod dav_svn
   $ sudo a2enmod userdir # to enable $HOME/public_html
   $ sudo /etc/init.d/apache2 restart

 * Install source code
   $ ./configure \
        --with-wwwdir=/var/www/codepot \
        --with-cfgdir=/etc/codepot \
        --with-depotdir=/var/lib/codepot \
        --with-logdir=/var/log/codepot \
        --with-cachedir=/var/cache/codepot
   $ make install
   $ mkdir -p /var/lib/codepot/svnrepo /var/lib/codepot/files
   $ mkdir -p /var/cache/codepot /var/log/codepot
  
 * Customize CFGDIR/codepot.ini 

 * Initialize database using CFGDIR/codepot.mysql

 * Configure apache web server authentication for subversion webdav access 
   based on CFGDIR/codepot.httpd 

 * To enable SSL 
   $ sudo a2enmod ssl 
   $ sudo a2ensite default-ssl
   $ sudo hostname actual.domain.name
   $ sudo make-ssl-cert generate-default-snakeoil --force-overwrite
   $ sudo hostname ${HOSTNAME}
   $ sudo /etc/init.d/apache2 restart
```
 
Note that make-ssl-cert is provided by the ssl-cert package.

## INSTALLATION WITH SOURCE CODE

Codepot uses the standard autoconf & automake build system. You can execute
'configure' followed by 'make' and 'make install' in principle. However, there
are some key options you should be aware of. See this sample run below.

```
$ ./configure --prefix=/usr \
              --libdir=/usr/lib64 \
              --sysconfdir=/etc \
              --with-wwwdir=/var/www/html/codepot \
              --with-cfgdir=/etc/codepot \
              --with-depotdir=/var/lib/codepot \
              --with-logdir=/var/log/codepot \
              --with-cachedir=/var/cache/codepot \
              --with-phpextdir=/usr/lib64/php/modules \
              --with-phpextinidir=/etc/php.d
$ make
$ make install 
```

You should take note of the following key directory options:

- wwwdir: The directory where most of the codepot program files are installed.
- cfgdir: The directory where the codepot configuration file(codepot.ini) and other supporting files are stored.
- depotdir: Subversion repostiories and various files uploaded are stored under this directory.
- cachedir: Cache directory.
- phpextdir: PHP extension directory. The peclsvn extension(svn.so) goes here.
- phpextinidir: The configuration file(svn.ini) to enable the extension goes here.
 
You should customize the value of these directories according to your system
configuration.

## UPGRADING FROM 0.2.0

You must make the following changes to your existing database manually
if you are upgrading from 0.2.0.

```
mysql> ALTER TABLE user_settings CHANGE code_hide_details code_hide_metadata CHAR(1) NOT NULL;
mysql> ALTER TABLE site ADD COLUMN(summary VARCHAR(255) NOT NULL);
mysql> RENAME TABLE user TO user_account;
mysql> ALTER TABLE file DROP INDEX encname;
mysql> create the file_list table according to the definition found in codepot.mysql
mysql> INSERT INTO file_list (projectid, name, filename, encname, md5sum, description) SELECT projectid, name, name, encname, md5sum, summary FROM file WHERE md5sum != '';
mysql> ALTER TABLE file DROP COLUMN summary;
mysql> ALTER TABLE file DROP COLUMN md5sum;
mysql> ALTER TABLE file DROP COLUMN encname;
mysql> ALTER TABLE project ADD COLUMN (codecharset VARCHAR(32));
mysql> DROP TABLE issue_attachment;
mysql> create the issue_file_list table according to the definition found in codepot.mysql
mysql> ALTER TABLE issue_change ADD COLUMN(createdon datetime not null, createdby varchar(32) not null);
mysql> UPDATE issue_change SET createdby=updatedby, createdon=updatedon;
mysql> create the issue_coderev table according to the definition found in codepot.mysql
mysql> ALTER TABLE user_settings ADD COLUMN(user_summary VARCHAR(255) NULL);
mysql> CREATE INDEX projectid_index on project_membership(projectid);
mysql> CREATE INDEX userid_index on project_membership(userid);
```

## LICENSE

This software is licensed under the GNU General Public License.

```
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
```

This software contains the following third-party components.

```
------------------------------------------------------------------------
Name                                         License
------------------------------------------------------------------------
CodeIgniter 2.2.6                            See src/system/license.txt
Google code prettify                         Apache License 2.0
JavaScript Creole 1.0 Wiki Markup Parser     See src/js/creole.js
jQuery JavaScript Library v1.11.2            MIT See http://jquery.org/license
jQuery UI 1.9.2                              MIT See http://jquery.org/license
X-editable 1.5.1                             MIT
CLOC 1.91                                    GPL
Flot                                         https://github.com/flot/flot/blob/master/LICENSE.txt
Chart.js                                     MIT
Font Awesome 4.3.0                           MIT & SIL OFL 1.1
D3.js 3.5.5                                  BSD
CodeFlower                                   MIT
ACE                                          BSD (http://ace.c9.io)
Medium-editor                                https://github.com/yabwe/medium-editor/blob/master/LICENSE
PDFJS                                        https://github.com/mozilla/pdf.js
WebODF                                       http://webodf.org/
ICONMONSTR Icons                             https://iconmonstr.com/
------------------------------------------------------------------------
```
