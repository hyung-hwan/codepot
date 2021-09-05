<?php

class API extends CI_Controller 
{
	function __construct ()
	{
		parent::__construct ();
	}

	function check_access ()
	{
		$server_name = $_SERVER['SERVER_NAME'];
		if ($server_name != 'localhost' &&
		    $server_name != '127.0.0.1') die;
	}

	function projectHasMember ($projectid, $userid)
	{
		$this->check_access ();

		if (!isset($projectid) || !isset($userid)) return 'NO';

		$this->load->model ('ProjectModel', 'projects');
		print ($this->projects->projectHasMember ($projectid, $userid) === FALSE)? 'NO': 'YES';
	}

	function projectIsOwnedBy ($projectid, $userid)
	{
		$this->check_access ();

		if (!isset($projectid) || !isset($userid)) return 'NO';

		$this->load->model ('ProjectModel', 'projects');
		print ($this->projects->projectIsOwnedBy ($projectid, $userid) === FALSE)? 'NO': 'YES';
	}

	function projectIsCommitable ($projectid, $userid)
	{
		$this->check_access ();

		if (!isset($projectid) || !isset($userid)) return 'NO';

		$this->load->model ('ProjectModel', 'projects');
		print ($this->projects->projectHasMember ($projectid, $userid) === FALSE ||
		       $this->projects->projectIsCommitable ($projectid) === FALSE)? 'NO': 'YES';
	}

	function logCodeCommit ($type, $repo, $rev, $userid)
	{
		$this->check_access ();

		if (!isset($repo) || !isset($rev)) return;

		$this->load->model ('LogModel', 'logs');
		$this->logs->writeCodeCommit ($type, $repo, $rev, $userid);


		/*
		$this->load->library ('email');
		$this->email->from ('xxxx');
		$this->email->to ('xxxx');
		$this->email->subject ('xxxx');
		$this->email->message ('xxxx');
		$this->email->send ();
		*/
	}

	function logCodeRevpropChange ($type, $repo, $rev, $userid, $propname, $action)
	{
		$this->check_access ();

		if (!isset($repo) || !isset($rev) || !isset($propname) || !isset($action)) return;

		$this->load->model ('LogModel', 'logs');
		$this->logs->writeCodeRevpropChange ($type, $repo, $rev, $userid, $propname, $action);
	}
}

