#
# This file is not desinged to be used in conjuntion with other AAA providers.
# This file requires to be used alone as shown below for apache httpd2. 
# You may change AuthName or SVNParentPath.
#
# <Location "/svn">
#        DAV svn
#        SVNParentPath "/var/lib/codepot/svnrepo"
#        PerlAccessHandler Codepot::AccessHandler
#        PerlAuthenHandler Codepot::AuthenHandler
#        PerlSetEnv CODEPOT_CONFIG_FILE /etc/codepot/codepot.ini
#        AuthType Basic
#        AuthName "codepot"
#        require valid-user
# </Location>
#
# If you do not move the handler files to the default library directory,
# a switch to indicate the location of the files are needed when loading
# the mod_perl module. Somewhere in  your httpd configuration, specify
# the -Mlib switch.
#
#   LoadModule perl_module modules/mod_perl.so
#   PerlSwitches -Mlib=/etc/codepot/perl
#

package Codepot::AccessHandler;

use strict;
use warnings;

use Apache2::Access ();
use Apache2::RequestUtil ();
use Apache2::RequestRec ();
use Apache2::Log;
use APR::Table;
use APR::Base64;

use Config::Simple;
use Net::LDAP;
use Net::LDAP qw(LDAP_SUCCESS);
use URI;
use DBI;
use Digest::SHA1 qw (sha1_hex);

use Apache2::Const -compile => qw(OK DECLINED FORBIDDEN HTTP_UNAUTHORIZED HTTP_INTERNAL_SERVER_ERROR PROXYREQ_PROXY);

sub get_config
{
	my $cfg = new Config::Simple();

	if (!$cfg->read ($ENV{'CODEPOT_CONFIG_FILE'}))
	{
		return undef;
	}

	my $config = {
		login_model => $cfg->param ('login_model'),
		
		ldap_server_uri => $cfg->param ('ldap_server_uri'),
		ldap_server_protocol_version => $cfg->param ('ldap_server_protocol_version'),
		ldap_auth_mode => $cfg->param ('ldap_auth_mode'),
		ldap_userid_format => $cfg->param ('ldap_userid_format'),
		ldap_password_format => $cfg->param ('ldap_password_format'),
		ldap_admin_binddn => $cfg->param ('ldap_admin_binddn'),
		ldap_admin_password => $cfg->param ('ldap_admin_password'),
		ldap_userid_search_base => $cfg->param ('ldap_userid_search_base'),
		ldap_userid_search_filter => $cfg->param ('ldap_userid_search_filter'),

		database_hostname => $cfg->param ('database_hostname'),
		database_port => $cfg->param ("database_port"),
		database_username => $cfg->param ('database_username'),
		database_password => $cfg->param ('database_password'),
		database_name => $cfg->param ('database_name'),
		database_driver => $cfg->param ('database_driver'),
		database_prefix => $cfg->param ('database_prefix'),

		svn_read_access => $cfg->param ('svn_read_access')
	};

	return $config;
}


sub format_string 
{
	my ($fmt, $userid, $password) = @_;

	my $out = $fmt;
	$out =~ s/\$\{userid\}/$userid/g;
	$out =~ s/\$\{password\}/$password/g;

	return $out;
}

sub authenticate_ldap
{
	my ($r, $cfg, $userid, $password) = @_;
	my $binddn;
	my $passwd;

	my $uri = URI->new ($cfg->{ldap_server_uri});
	my $ldap = Net::LDAP->new (
		$uri->host, 
		scheme => $uri->scheme,
		port => $uri->port,
		version => $cfg->{ldap_server_protocol_version}
	);
	if (!defined($ldap))
	{
		$r->log_error ('Cannot create LDAP');
		return -1;
	}

	if ($cfg->{ldap_auth_mode} == 2)
	{
		my $f_rootdn = format_string ($cfg->{ldap_admin_binddn}, $userid, $password);
		my $f_rootpw = format_string ($cfg->{ldap_admin_password}, $userid, $password);
		my $f_basedn = format_string ($cfg->{ldap_userid_search_base}, $userid, $password);
		my $f_filter = format_string ($cfg->{ldap_userid_search_filter}, $userid, $password);

		my $res = $ldap->bind ($f_rootdn, password => $f_rootpw);
		if ($res->code != LDAP_SUCCESS) 
		{ 	
			$r->log_error ("Cannot bind LDAP as $f_rootdn - " . $res->error());
			$ldap->unbind();
			return -1; 
		}
		
		$res = $ldap->search (base => $f_basedn, scope => 'sub', filter => $f_filter);
		if ($res->code != LDAP_SUCCESS) 
		{ 	
			$ldap->unbind();
			return 0;
		}

		my $entry = $res->entry(0); # get the first entry only
		if (!defined($entry))
		{
			$ldap->unbind();
			return 0;
		}

		$binddn = $entry->dn ();
	}
	else
	{
		$binddn = format_string ($cfg->{ldap_userid_format}, $userid, $password);
	}

	$passwd = format_string ($cfg->{ldap_password_format}, $userid, $password);
	my $res = $ldap->bind ($binddn, password => $passwd);
	if ($res->code != LDAP_SUCCESS)
	{
		#$r->log_error ("Cannot bind LDAP as $binddn - " . $res->error());
		$ldap->unbind();
		return 0;
	}

	$ldap->unbind();
	return 1;
}

sub authenticate_database
{
	my ($dbh, $prefix, $userid, $password, $qc) = @_;
	
	my $query = $dbh->prepare ("SELECT ${qc}userid${qc},${qc}passwd${qc} FROM ${qc}${prefix}user_account${qc} WHERE ${qc}userid${qc}=? and ${qc}enabled${qc}='Y'");
	if (!$query || !$query->execute ($userid))
	{
		return (-1, $dbh->errstr());
	}

	my @row = $query->fetchrow_array;
	$query->finish ();

	if (scalar(@row) <= 0) { return (0, undef); }

	my $db_pw = $row[1];
	if (length($db_pw) < 10) { return (0, undef); }

	my $hexsalt = substr ($db_pw, -10);
	my $binsalt = pack ('H*', $hexsalt);

	my $fmt_pw = '{ssha1}' . sha1_hex ($password . $binsalt) . $hexsalt;
	return  (($fmt_pw eq $db_pw? 1: 0), undef);
}

sub open_database
{
	my ($cfg) = @_;

	my $dbtype = $cfg->{database_driver};
	my $dbname = $cfg->{database_name};
	my $dbhost = $cfg->{database_hostname};
	my $dbport = $cfg->{database_port};

	if ($dbtype eq 'postgre') { $dbtype = 'Pg'; }
	elsif ($dbtype eq 'oci8') { $dbtype = 'Oracle'; }
	elsif ($dbtype eq 'mysqli') { $dbtype = 'mysql'; }

	my $dbstr;
	my $dbuser;
	my $dbpass;
	if ($dbtype eq 'Oracle')
	{
		$dbstr = "DBI:$dbtype:";
		$dbuser = $cfg->{database_username} . '/' . $cfg->{database_password} . '@' . $dbhost;
		$dbpass = '';
	}
	else
	{
		$dbstr = "DBI:$dbtype:database=$dbname;";
		if (length($dbhost) > 0) { $dbstr .= "host=$dbhost;"; }
		if (length($dbport) > 0) { $dbstr .= "port=$dbport;"; }

		$dbuser = $cfg->{database_username};
		$dbpass = $cfg->{database_password};
	}

	my $dbh = DBI->connect(
		$dbstr, $dbuser, $dbpass,
		{ RaiseError => 0, PrintError => 0, AutoCommit => 0 }
	);

	return $dbh;
}

sub close_database
{
	my ($dbh) = @_;
	$dbh->disconnect ();
}

sub is_project_member
{
	my ($dbh, $prefix, $projectid, $userid, $qc) = @_;

	my $query = $dbh->prepare ("SELECT ${qc}projectid${qc} FROM ${qc}${prefix}project_membership${qc} WHERE ${qc}userid${qc}=? AND ${qc}projectid${qc}=?");
	if (!$query || !$query->execute ($userid, $projectid))
	{
		return (-1, $dbh->errstr());
	}

	my @row = $query->fetchrow_array;
	$query->finish ();
	return (((scalar(@row) > 0)? 1: 0), undef);
}

sub is_project_public
{
	my ($dbh, $prefix, $projectid, $qc) = @_;

	my $query = $dbh->prepare ("SELECT ${qc}public${qc} FROM ${qc}${prefix}project${qc} WHERE ${qc}id${qc}=?");
	if (!$query || !$query->execute ($projectid))
	{
		return (-1, $dbh->errstr());
	}

	my @row = $query->fetchrow_array;
	$query->finish ();
	return (((scalar(@row) > 0 && $row[0] eq 'Y')? 1: 0), undef);
}

sub is_read_method
{
	my ($method) = @_;

	return $method eq "GET"     || $method eq "HEAD" ||
	       $method eq "OPTIONS" || $method eq "REPORT" ||
	       $method eq "PROPFIND";
}
sub __handler 
{
	my ($r, $cfg, $dbh) = @_;
	my ($empty, $base, $repo, $dummy) = split ('/', $r->uri(), 4);
	my $method = uc($r->method());
	my $is_method_r = is_read_method ($method);

	my $author;
	my $userid = undef;
	my $password = undef;

	my $public = undef;
	my $member = undef;
	my $errmsg = undef;

	my $qc = '';
	if ($cfg->{database_driver} eq 'oci8') { $qc = '"'; }
	
	if ($r->proxyreq() == Apache2::Const::PROXYREQ_PROXY)
	{
		$author = $r->headers_in->{'Proxy-Authorization'};
	}
	else
	{
		$author = $r->headers_in->{'Authorization'};
	}

	if (defined($author))
	{
		my ($rc, $pass) = $r->get_basic_auth_pw ();
		if ($rc != Apache2::Const::OK) { return $rc; }

		#$author = APR::Base64::decode((split(/ /,$author))[1]);
		#($userid,$password) = split(/:/, $author);

		$userid = $r->user();
		$password = $pass;
	}

	if (!defined($userid)) { $userid = ""; }
	if (!defined($password)) { $password = ""; }

	if ($is_method_r)
	{
		($public, $errmsg) = is_project_public ($dbh, $cfg->{database_prefix}, $repo, $qc);
		if ($public <= -1)
		{
			# failed to contact the authentication server
			$r->log_error ("Cannot check if a project is public - $errmsg");
			return Apache2::Const::HTTP_INTERNAL_SERVER_ERROR;
		}
		elsif ($public >= 1)
		{
			if (lc($cfg->{svn_read_access}) eq 'anonymous')
			{
				# grant an anonymous user the read access.
				return Apache2::Const::OK;
			}
		}
	}
	
	my $auth = -3;
	if ($cfg->{login_model} eq 'LdapLoginModel')
	{
		$auth = authenticate_ldap ($r, $cfg, $userid, $password);
	}
	elsif ($cfg->{login_model} eq 'DbLoginModel')
	{
		($auth, $errmsg) = authenticate_database (
			$dbh, $cfg->{database_prefix}, $userid, $password, $qc);
		if ($auth <= -1)
		{
			$r->log_error ("Database error - $errmsg");
		}
	}
	if ($auth <= -1)
	{
		# failed to contact the authentication server
		return Apache2::Const::HTTP_INTERNAL_SERVER_ERROR;
	}
	elsif ($auth == 0)
	{
		# authentication denied
		$r->note_basic_auth_failure ();
		return Apache2::Const::HTTP_UNAUTHORIZED;
	}

	# authentication successful. 
	if ($is_method_r && $public >= 1 && lc($cfg->{svn_read_access}) eq 'authenticated')
	{
		# grant read access to an authenticated user regardless of membership 
		# this applies to a public project only
		return Apache2::Const::OK;
	}

	($member, $errmsg) = is_project_member ($dbh, $cfg->{database_prefix}, $repo, $userid, $qc);
	if ($member <= -1)
	{
		$r->log_error ("Cannot check project membership - $errmsg");
		return Apache2::Const::HTTP_INTERNAL_SERVER_ERROR;
	}
	elsif ($member == 0)
	{
		# access denined
		return Apache2::Const::FORBIDDEN;
	}
	else
	{
		# the user is a member of project. access granted.
		return Apache2::Const::OK;
	}
}

sub handler: method
{
	my ($class, $r) = @_;
	my $res;
	my $cfg;

	$cfg = get_config (); 
	if (!defined($cfg))
	{
		$r->log_error ('Cannot load configuration');
		return Apache2::Const::HTTP_INTERNAL_SERVER_ERROR;
	}

	my $dbh = open_database ($cfg);
	if (!defined($dbh))
	{
		$r->log_error ('Cannot open database - ' . $DBI::errstr);
		return Apache2::Const::HTTP_INTERNAL_SERVER_ERROR;
	}

	$res = __handler ($r, $cfg, $dbh);

	close_database ($dbh);
	return $res;
}
1;
