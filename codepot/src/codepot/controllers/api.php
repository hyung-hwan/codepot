<?php

class API extends Controller 
{
	function API()
	{
		parent::Controller();
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

		// TODO: access control - may allow localhost only
		$this->load->model ('ProjectModel', 'projects');
		print ($this->projects->projectHasMember ($projectid, $userid) === FALSE)? 'NO': 'YES';
	}

	function projectIsOwnedBy ($projectid, $userid)
	{
		$this->check_access ();

		if (!isset($projectid) || !isset($userid)) return 'NO';

		// TODO: access control - may allow localhost only
		$this->load->model ('ProjectModel', 'projects');
		print ($this->projects->projectIsOwnedBy ($projectid, $userid) === FALSE)? 'NO': 'YES';
	}

	function logCodeCommit ($type, $repo, $rev)
	{
		$this->check_access ();

		if (!isset($repo) || !isset($rev)) return;

		// TODO: access control - may allow localhost only
		$this->load->model ('LogModel', 'logs');
		$this->logs->writeCodeCommit ($type, $repo, $rev, '');
	}
}

