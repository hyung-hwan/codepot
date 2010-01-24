<?php

//
// TODO: rename this class to something else
//       AuthModel or IdentityModel?
//

class LoginModel extends Model
{
	var $error_message = '';

	function LoginModel ()
	{
		parent::Model ();
		$this->load->library ('session');
	}

	function isSysadmin ()
	{
		$userid = $this->getUserid ();
		if ($userid === NULL) return FALSE;
		return $userid == CODEPOT_SYSADMIN_USERID;
	}

	function getUserid ()
	{
		$server1 = $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];
		$server2 = $this->session->userdata('server');
		if ($server1 != $server2) return '';
		$userid = $this->session->userdata('userid');
		return ($userid === NULL)? '': $userid;
	}

	function authenticate ($userid, $password)
	{
		$server = $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];
		$this->session->set_userdata (
			array (
				'userid' => $userid,
				'server' => $server
			)
		);
		return TRUE;
	}

	function deauthenticate ()
	{
		//$this->session->unset_userdata ('userid');
		//$this->session->unset_userdata ('server');
		$this->session->sess_destroy ();
	}

	function getErrorMessage ()
	{
		return $this->error_message;
	}

	function setErrorMessage ($msg)
	{
		$this->error_message = $msg;
	}

	function formatString ($fmt, $userid, $password)
	{
		$fmt = preg_replace(sprintf('/\$\{?%s\}?/', 'userid'), $userid, $fmt);
		$fmt = preg_replace(sprintf('/\$\{?%s\}?/', 'password'), $password, $fmt);
		return $fmt;
	}
}
