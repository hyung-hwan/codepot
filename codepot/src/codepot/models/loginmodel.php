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

	function getUser ()
	{
		$server1 = $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];
		$server2 = $this->session->userdata('server');
		if ($server1 != $server2) 
		{
			$userid = '';
			$email = '';
			$issysadmin = FALSE;
			$settings = NULL;
		}
		else
		{
			$userid = $this->session->userdata('userid');
			if ($userid === NULL) $userid = '';

			$email = $this->session->userdata('email');
			if ($email === NULL) $email = '';

			$issysadmin = $this->session->userdata('sysadmin?');
			if ($issysadmin === NULL) $issysadmin = FALSE;

			$settings = $this->session->userdata('user_settings');
			if ($settings !== NULL) $settings = unserialize ($settings);
		}

		return array (
			'id' => $userid, 
			'email' => $email,
			'sysadmin?' => $issysadmin,
			'settings' => $settings
		);
	}

	function authenticate ($userid, $password, $email = '')
	{
		$server = $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];

		$sysadmin = FALSE;
		$ids = explode (',', CODEPOT_SYSADMIN_USERIDS);
		foreach ($ids as $id)
		{
			if (trim($id) == $userid) 
			{
				$sysadmin = TRUE;
				break;
			}
		}

		$this->session->set_userdata (
			array (
				'userid' => $userid,
				'server' => $server,
				'email' => $email,
				'sysadmin?' => $sysadmin
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

	function setUserSettings ($settings)
	{
		$this->session->set_userdata ('user_settings', serialize($settings));
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
