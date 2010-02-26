<?php

class API extends Controller 
{
	function API()
	{
		parent::Controller();
	}

	function projectHasMember ($projectid, $userid)
	{
		if (!isset($projectid) || !isset($userid)) return 'NO';

		// TODO: access control - may allow localhost only
		$this->load->model ('ProjectModel', 'projects');
		print ($this->projects->projectHasMember ($projectid, $userid) === FALSE)? 'NO': 'YES';
	}

	function projectIsOwnedBy ($projectid, $userid)
	{
		if (!isset($projectid) || !isset($userid)) return 'NO';

		// TODO: access control - may allow localhost only
		$this->load->model ('ProjectModel', 'projects');
		print ($this->projects->projectIsOwnedBy ($projectid, $userid) === FALSE)? 'NO': 'YES';
	}

	function logSvnCommit ($repo, $rev)
	{
		if (!isset($repo) || !isset($rev)) return;

		// TODO: access control - may allow localhost only
		$this->load->model ('LogModel', 'logs');
		$this->logs->writeSvnCommit ($repo, $rev);
	}
}

