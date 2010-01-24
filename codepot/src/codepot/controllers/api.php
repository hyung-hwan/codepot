<?php

class API extends Controller 
{
	function API()
	{
		parent::Controller();
	}

	function projectHasMember ($projectid, $userid)
	{
		$this->load->model ('ProjectModel', 'projects');
		print ($this->projects->projectHasMember ($projectid, $userid) === FALSE)? 'NO': 'YES';
	}

	function projectIsOwnedBy ($projectid, $userid)
	{
		$this->load->model ('ProjectModel', 'projects');
		print ($this->projects->projectIsOwnedBy ($projectid, $userid) === FALSE)? 'NO': 'YES';
	}
}

