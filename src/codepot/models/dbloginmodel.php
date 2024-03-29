<?php
require_once 'loginmodel.php';

class DbLoginModel extends LoginModel
{
	function __construct ()
	{
		parent::__construct ();
		$this->load->database ();
	}

	function rand_string ($length)
	{
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

		$str = '';
		$size = strlen ($chars);
		for( $i = 0; $i < $length; $i++ )
		{
			$str .= $chars[ rand( 0, $size - 1 ) ];
		}

		return $str;
	}

	function format_password_with_salt ($password, $salt)
	{
		return '{ssha1}' . sha1($password . $salt) . bin2hex($salt);
	}

	function format_password ($password, $salt_length)
	{
		$salt = $this->rand_string ($salt_length);
		return $this->format_password_with_salt ($password, $salt);
	}

	function authenticate ($userid, $passwd)
	{
		$this->db->trans_begin ();
		
		$this->db->select ('userid,passwd,email');
		$this->db->where ('userid', $userid);
		$this->db->where ('enabled', 'Y');
		$query = $this->db->get ('user_account');

		if ($this->db->trans_status() == FALSE)
		{
			$this->setErrorMessage ($this->db->_error_message());
			$this->db->trans_rollback ();
			return FALSE;
		}

		$result = $query->result ();
		if (empty($result))
		{
			$this->db->trans_rollback ();
			$this->setErrorMessage ('No such user');
			return FALSE;
		}

		$this->db->trans_commit();

		$user = $result[0];
		if (strlen($user->passwd) < 10) 
		{
			$this->setErrorMessage ('wrongly formatted password');
			return FALSE;
		}
		// the last 10 characters are the salt.
		$hexsalt = substr ($user->passwd, -10);
		$binsalt = pack('H*' , $hexsalt);

		if (strcmp ($this->format_password_with_salt($passwd,$binsalt),$user->passwd) != 0) 
		{
			$this->setErrorMessage ('invalid credential'); // invalid password
			return FALSE;
		}

		// TODO: implement $insider like LdapLoginModel
		return parent::__authenticate ($userid, $user->passwd, $user->email);
	}

	function changePassword ($userid, $passwd)
	{
		$this->db->trans_begin ();

		$this->db->where ('userid', $userid);
		$this->db->set ('passwd', format_password($passwd,5));
		$this->db->update ('user_account');

		if ($this->db->trans_status() === FALSE)
		{
			$this->setErrorMessage ($this->db->_error_message());
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->trans_commit ();
		return TRUE;
	}

	function queryUserInfo ($userid)
	{
		$this->db->trans_start ();

		$this->db->select ('email');
		$this->db->where ('userid', $userid);
		$query = $this->db->get ('user_account');

		if ($this->db->trans_status() == FALSE)
		{
			$this->setErrorMessage ($this->db->_error_message());
			$this->db->trans_complete ();
			return FALSE;
		}

		$result = $query->result ();
		if (empty($result))
		{
			$this->db->trans_complete ();
			return FALSE;
		}

		$user['id'] = $userid;
		$user['email'] = $result[0]->email;

		return $user;
	}
}

?>
