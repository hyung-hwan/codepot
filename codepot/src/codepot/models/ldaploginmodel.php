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

		if (CODEPOT_LDAP_AUTH_MODE == 2)
		{
			$f_rootdn = $this->formatString (CODEPOT_LDAP_ADMIN_BINDDN, $userid, $password);
			$f_rootpw = $this->formatString (CODEPOT_LDAP_ADMIN_PASSWORD, $userid, $password);
			$f_basedn = $this->formatString (CODEPOT_LDAP_USERID_SEARCH_BASE, $userid, $password);
			$f_filter = $this->formatString (CODEPOT_LDAP_USERID_SEARCH_FILTER, $userid, $password);
			
			$bind = @ldap_bind ($ldap, $f_rootdn, $f_rootpw);
			if ($bind === FALSE) 
			{
				$this->setErrorMessage (ldap_error ($ldap));
				ldap_close ($ldap);
				return FALSE;
			}

			$sr = @ldap_search ($ldap, $f_basedn, $f_filter, array("dn"));
			if ($sr === FALSE)
			{
				$this->setErrorMessage (ldap_error ($ldap));
				ldap_close ($ldap);
				return FALSE;
			}

			$ec = @ldap_count_entries ($ldap, $sr);
			if ($ec === FALSE)
			{
				$this->setErrorMessage (ldap_error ($ldap));
				ldap_close ($ldap);
				return FALSE;
			}

			if ($ec <= 0)
			{
				$this->setErrorMessage ('No such user');
				ldap_close ($ldap);
				return FALSE;
			}

			if (($fe = @ldap_first_entry ($ldap, $sr)) === FALSE ||
			    ($f_userid = ldap_get_dn ($ldap, $fe)) === FALSE)
			{
				$this->setErrorMessage (ldap_error ($ldap));
				ldap_close ($ldap);
				return FALSE;
			}
		}
		else
		{
			$f_userid = $this->formatString (CODEPOT_LDAP_USERID_FORMAT, $userid, $password); 
		}

		$f_password = $this->formatString (CODEPOT_LDAP_PASSWORD_FORMAT, $userid, $password);

		$bind = @ldap_bind ($ldap, $f_userid, $f_password);
		if ($bind === FALSE) 
		{
			$this->setErrorMessage (ldap_error ($ldap));
			@ldap_close ($ldap);
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
				    array_key_exists(0, $e) &&
				    array_key_exists(CODEPOT_LDAP_MAIL_ATTRIBUTE_NAME, $e[0]))
				{
					$email = $e[0][CODEPOT_LDAP_MAIL_ATTRIBUTE_NAME][0];
				}
			}
		}

		$insider = FALSE;
		if (CODEPOT_LDAP_INSIDER_ATTRIBUTE_NAME != '' && CODEPOT_LDAP_INSIDER_ATTRIBUTE_VALUE != '')
		{
			$filter = '(' . CODEPOT_LDAP_INSIDER_ATTRIBUTE_NAME . '=*)';
			$r = @ldap_search ($ldap, $f_userid, $filter, array(CODEPOT_LDAP_INSIDER_ATTRIBUTE_NAME));
			if ($r !== FALSE)
			{

				/* SAMPLE LDAP RESULT
				array(2) {
				  ["count"]=>  int(1)
				  [0]=>
				  array(4) {
				    ["mssfu30posixmemberof"]=>
				    array(4) {
					 ["count"]=>
					 int(3)
					 [0]=>
					 string(36) "CN=group01,OU=Groups,DC=abiyo,DC=net"
					 [1]=>
					 string(36) "CN=group02,OU=Groups,DC=abiyo,DC=net"
					 [2]=>
					 string(45) "CN=group03,OU=Groups,DC=abiyo,DC=net"
				    }
				    [0]=>
				    string(20) "mssfu30posixmemberof"
				    ["count"]=>
				    int(1)
				    ["dn"]=>
				    string(37) "CN=user01,CN=Users,DC=abiyo,DC=net"
				  }
				}
				*/
				$e = @ldap_get_entries($ldap, $r);
				if ($e !== FALSE && array_key_exists('count', $e) && ($ec = $e['count']) > 0)
				{
					for ($i = 0; $i < $ec; $i++)
					{
						if (array_key_exists($i, $e) &&
						    array_key_exists(CODEPOT_LDAP_INSIDER_ATTRIBUTE_NAME, $e[$i]))
						{
							$va = $e[$i][CODEPOT_LDAP_INSIDER_ATTRIBUTE_NAME];

							if (array_key_exists('count', $va) && ($vac = $va['count']) > 0)
							{
								for ($j = 0; $j < $vac; $j++)
								{
									if (strcasecmp($va[$j], CODEPOT_LDAP_INSIDER_ATTRIBUTE_VALUE) == 0) 
									{
										$insider = TRUE;
										break;
									}
								}
							}
						}
						if ($insider) break;
					}
				}
			}
		}

		//@ldap_unbind ($ldap);
		@ldap_close ($ldap);
if ($insider) error_log ("$userid is insider");
else error_log ("$userid is NOT insider");

		return parent::authenticate ($userid, $password, $email, $insider);
	}

	function queryUserInfo ($userid)
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

		$bind = @ldap_bind ($ldap, CODEPOT_LDAP_ADMIN_BINDDN, CODEPOT_LDAP_ADMIN_PASSWORD);
		if ($bind === FALSE) 
		{
			$this->setErrorMessage (ldap_error ($ldap));
			@ldap_close ($ldap);
			return FALSE;
		}

		if (CODEPOT_LDAP_AUTH_MODE == 2)
		{
			$f_basedn = $this->formatString (CODEPOT_LDAP_USERID_SEARCH_BASE, $userid, '');
			$f_filter = $this->formatString (CODEPOT_LDAP_USERID_SEARCH_FILTER, $userid, '');
			
			$sr = @ldap_search ($ldap, $f_basedn, $f_filter, array("dn"));
			if ($sr === FALSE)
			{
				$this->setErrorMessage (ldap_error ($ldap));
				ldap_close ($ldap);
				return FALSE;
			}

			$ec = @ldap_count_entries ($ldap, $sr);
			if ($ec === FALSE)
			{
				$this->setErrorMessage (ldap_error ($ldap));
				ldap_close ($ldap);
				return FALSE;
			}

			if ($ec <= 0)
			{
				$this->setErrorMessage ('No such user');
				ldap_close ($ldap);
				return FALSE;
			}

			if (($fe = @ldap_first_entry ($ldap, $sr)) === FALSE ||
			    ($f_userid = ldap_get_dn ($ldap, $fe)) === FALSE)
			{
				$this->setErrorMessage (ldap_error ($ldap));
				ldap_close ($ldap);
				return FALSE;
			}
		}
		else
		{
			$f_userid = $this->formatString (CODEPOT_LDAP_USERID_FORMAT, $userid, '');
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
				    array_key_exists(0, $e) &&
				    array_key_exists(CODEPOT_LDAP_MAIL_ATTRIBUTE_NAME, $e[0]))
				{
					$email = $e[0][CODEPOT_LDAP_MAIL_ATTRIBUTE_NAME][0];
				}
			}
		}

		//@ldap_unbind ($ldap);
		@ldap_close ($ldap);

		$user['id'] = $userid;
		$user['email'] = $email;

		return $user;
	}
}

?>
