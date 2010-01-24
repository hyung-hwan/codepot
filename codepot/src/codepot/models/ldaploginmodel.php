<?php
require_once 'loginmodel.php';

class LdapLoginModel extends LoginModel
{
	function LdapLoginModel ()
	{
		parent::LoginModel ();
	}

	function authenticate ($userid, $password)
	{
		$ldap = @ldap_connect (
			CODEPOT_LDAP_SERVER_HOST, CODEPOT_LDAP_SERVER_PORT);
		if ($ldap === FALSE)
		{
			$this->setErrorMessage ("Can't connect to LDAP server");
			return FALSE;
		}

		if (CODEPOT_LDAP_SERVER_PROTOCOL_VERSION !== FALSE)
		{
			ldap_set_option ($ldap, LDAP_OPT_PROTOCOL_VERSION, CODEPOT_LDAP_SERVER_PROTOCOL_VERSION);
		}

		$f_userid = $this->formatString (CODEPOT_LDAP_USERID_FORMAT, $userid, $password); 
		$f_password = $this->formatString (CODEPOT_LDAP_PASSWORD_FORMAT, $userid, $password);

		$bind = @ldap_bind ($ldap, $f_userid, $f_password);
		if ($bind === FALSE) 
		{
			$this->setErrorMessage (ldap_error ($ldap));
			ldap_close ($ldap);
			return FALSE;
		}

		@ldap_unbind ($bind);
		@ldap_close ($ldap);

		return parent::authenticate ($userid, $password);
	}
}

?>
