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
		//$ldap = @ldap_connect (
		//	CODEPOT_LDAP_SERVER_HOST, CODEPOT_LDAP_SERVER_PORT);
		$ldap = @ldap_connect (CODEPOT_LDAP_SERVER_URI);
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

		$email = '';
		if (CODEPOT_LDAP_MAIL_ATTRIBUTE_NAME != '')
		{
			$filter = '(' . CODEPOT_LDAP_MAIL_ATTRIBUTE_NAME . '=*)';
			$r = @ldap_search ($ldap, $f_userid, $filter, array(CODEPOT_LDAP_MAIL_ATTRIBUTE_NAME));
			if ($r !== FALSE)
			{
				$e = @ldap_get_entries($ldap, $r);
				if ($e !== FALSE && count($e) > 0 && 
				    array_key_exists(CODEPOT_LDAP_MAIL_ATTRIBUTE_NAME, $e[0]))
				{
					$email = $e[0][CODEPOT_LDAP_MAIL_ATTRIBUTE_NAME][0];
				}
			}
		}


		@ldap_unbind ($bind);
		@ldap_close ($ldap);

		return parent::authenticate ($userid, $password, $email);
	}

	function queryUserInfo ($userid)
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

		$bind = @ldap_bind ($ldap, CODEPOT_LDAP_ADMIN_BINDDN, CODEPOT_LDAP_ADMIN_PASSWORD);
		if ($bind === FALSE) 
		{
			$this->setErrorMessage (ldap_error ($ldap));
			ldap_close ($ldap);
			return FALSE;
		}

		$f_userid = $this->formatString (CODEPOT_LDAP_USERID_FORMAT, $userid, '');
		$email = '';

		if (CODEPOT_LDAP_MAIL_ATTRIBUTE_NAME != '')
		{
			$filter = '(' . CODEPOT_LDAP_MAIL_ATTRIBUTE_NAME . '=*)';
			$r = @ldap_search ($ldap, $f_userid, $filter, array(CODEPOT_LDAP_MAIL_ATTRIBUTE_NAME));
			if ($r !== FALSE)
			{
				$e = @ldap_get_entries($ldap, $r);
				if ($e !== FALSE && count($e) > 0 && 
				    array_key_exists(CODEPOT_LDAP_MAIL_ATTRIBUTE_NAME, $e[0]))
				{
					$email = $e[0][CODEPOT_LDAP_MAIL_ATTRIBUTE_NAME][0];
				}
			}
		}


		@ldap_unbind ($bind);
		@ldap_close ($ldap);

		$user['id'] = $userid;
		$user['email'] = $email;

		return $user;
	}
}

?>
