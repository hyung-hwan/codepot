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
use URI;
use DBI;

use Apache2::Const -compile => qw(OK DECLINED FORBIDDEN HTTP_UNAUTHORIZED HTTP_INTERNAL_SERVER_ERROR PROXYREQ_PROXY);

sub get_config
{
	my $cfg = new Config::Simple();

	if (!$cfg->read ($ENV{'CODEPOT_CONFIG_FILE'}))
	{
		return undef;
	}

	my $config = {
		ldap_server_uri => $cfg->param ("ldap_server_uri"),
		ldap_server_protocol_version => $cfg->param ("ldap_server_protocol_version"),
		ldap_auth_mode => $cfg->param ("ldap_auth_mode"),
		ldap_userid_format => $cfg->param ("ldap_userid_format"),
		ldap_password_format => $cfg->param ("ldap_password_format"),
		ldap_userid_admin_binddn => $cfg->param ("ldap_admin_binddn"),
		ldap_userid_admin_password => $cfg->param ("ldap_admin_password"),
		ldap_userid_search_base => $cfg->param ("ldap_userid_search_base"),
		ldap_userid_search_fitler => $cfg->param ("ldap_userid_search_filter"),

		database_hostname => $cfg->param ("database_hostname"),
		database_username => $cfg->param ("database_username"),
		database_password => $cfg->param ("database_password"),
		database_name => $cfg->param ("database_name"),
		database_driver => $cfg->param ("database_driver"),
		database_prefix => $cfg->param ("database_prefix")
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

sub authenticate 
{
	my ($cfg, $userid, $password) = @_;
	my $binddn;
	my $passwd;

	# get the next line removed once you implement the second mode
	if ($cfg->{ldap_auth_mode} == 2) { return -2; }

	my $uri = URI->new ($cfg->{ldap_server_uri});
	my $ldap = Net::LDAP->new ($uri->host, 
			scheme => $uri->scheme,
			port => $uri->port,
			version => $cfg->{ldap_server_protocol_version}
	);
	if (!defined($ldap))
	{
		# error
		return -1;
	}

	if ($cfg->{ldap_auth_mode} == 2)
	{
		# YET TO BE WRITTEN
	}
	else
	{
		$binddn = format_string ($cfg->{ldap_userid_format}, $userid, $password);
	}

	$passwd = format_string ($cfg->{ldap_password_format}, $userid, $password);
	my $res = $ldap->bind ($binddn, password => $passwd);

	print $res->code;
	print "\n";

	$ldap->unbind();
	return ($res->code == 0)? 1: 0;
}

sub open_database
{
	my ($cfg) = @_;

	my $dbtype = $cfg->{database_driver};
	my $dbname = $cfg->{database_name};
	my $dbhost = $cfg->{database_hostname};

	my $dbh = DBI->connect(
		"DBI:$dbtype:$dbname:$dbhost",
		$cfg->{database_username},
		$cfg->{database_password},
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
	my ($dbh, $prefix, $projectid, $userid) = @_;

	my $query = $dbh->prepare ("SELECT projectid FROM ${prefix}project_membership WHERE userid=? AND projectid=?");
	if (!$query || !$query->execute ($userid, $projectid))
	{
		return (-1, $dbh->errstr());
	}

	my @row = $query->fetchrow_array;
	return (((scalar(@row) > 0)? 1: 0), undef);
}

sub is_project_public
{
	my ($dbh, $prefix, $projectid) = @_;

	my $query = $dbh->prepare ("SELECT public FROM ${prefix}project WHERE id=?");
	if (!$query || !$query->execute ($projectid))
	{
		return (-1, $dbh->errstr());
	}

	my @row = $query->fetchrow_array;
	return (((scalar(@row) > 0 && $row[0] eq 'Y')? 1: 0), undef);
}

sub __handler 
{
	my ($r, $cfg, $dbh) = @_;
	my ($empty, $base, $repo, $dummy) = split ('/', $r->uri(), 4);
	my $method = uc($r->method());

	my $author;
	my $userid = undef;
	my $password = undef;

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

	if ($method eq "GET" ||
	    $method eq "HEAD" ||
	    $method eq "OPTIONS" ||
	    $method eq "REPORT" ||
	    $method eq "PROPFIND")
	{
		my ($public, $errmsg) = is_project_public ($dbh, $cfg->{database_prefix}, $repo);
		if ($public <= -1)
		{
			# failed to contact the authentication server
			$r->log_error ("Cannot check if a project is public - $errmsg");
			return Apache2::Const::HTTP_INTERNAL_SERVER_ERROR;
		}
		elsif ($public >= 1)
		{
			return Apache2::Const::OK;
		}
	}
	
	my $auth = authenticate ($cfg, $userid, $password);
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
	my ($member, $errmsg) = is_project_member ($dbh, $cfg->{database_prefix}, $repo, $userid);
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
		$r->log_error ("Cannot load configuration");
		return Apache2::Const::HTTP_INTERNAL_SERVER_ERROR;
	}

	my $dbh = open_database ($cfg);
	if (!defined($dbh))
	{
		$r->log_error ("Cannot open database");
		return Apache2::Const::HTTP_INTERNAL_SERVER_ERROR;
	}

	$res = __handler ($r, $cfg, $dbh);

	close_database ($dbh);
	return $res;
}
1;
