<?php
require_once 'loginmodel.php';

class MysqlLoginModel extends LoginModel
{
	function LdapLoginModel ()
	{
		parent::LoginModel ();
		$this->load->database ('auth-mysql');
	}

	function authenticate ($userid, $password)
	{
		$this->db->trans_start ();
		
	/*
	TODO:
		$this->db->select ('username');
                $this->db->where ('username', $userid);
                $this->db->where ('passwd', $userid);
	*/
		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		return parent::authenticate ($userid, $password, $email);
	}

	function queryUserInfo ($userid)
	{
		$user['id'] = '';
		$user['email'] = '';

		return $user;
	}
}

?>
