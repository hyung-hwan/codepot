<?php

class User extends Controller
{
	var $VIEW_ERROR = 'error';
	var $VIEW_HOME = 'user_home';
	var $VIEW_PROJECT_LIST = 'project_list';

	function User ()
	{
		parent::Controller ();

		$this->load->helper ('url');
		$this->load->helper ('form');
		$this->load->library ('Converter', 'converter');
		$this->load->model (CODEPOT_LOGIN_MODEL, 'login');

		$this->load->library ('Language', 'lang');
		$this->lang->load ('common', CODEPOT_LANG);
	}

	function index ()
	{
		return $this->home ();
	}

	function home ()
	{
		$loginid = $this->login->getUserid();
		if (CODEPOT_ALWAYS_REQUIRE_SIGNIN && $loginid == '')
			redirect ('main/signin');

		$this->load->model ('ProjectModel', 'projects');

		$latest_projects = $this->projects->getLatestProjects ($loginid, CODEPOT_MAX_LATEST_PROJECTS);
		if ($latest_projects === FALSE)
		{
			$data['loginid'] = $loginid;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$data['loginid'] = $loginid;
			$data['latest_projects'] = $latest_projects;
			$data['user_name'] = '';
			$data['user_pass'] = '';
			$this->load->view ($this->VIEW_HOME, $data);
		}
	}

	function projectlist ()
	{
		$loginid = $this->login->getUserid();
		if (CODEPOT_ALWAYS_REQUIRE_SIGNIN && $loginid == '')
			redirect ('main/signin');

		$this->load->model ('ProjectModel', 'projects');

		$projects = $this->projects->getMyProjects ($loginid);
		$other_projects = $this->projects->getOtherProjects ($loginid);

		if ($projects === FALSE || $other_projects === FALSE)
		{
			$data['loginid'] = $loginid;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$data['loginid'] = $loginid;
			$data['projects'] = $projects;
			$data['other_projects'] = $other_projects;
			$this->load->view ($this->VIEW_PROJECT_LIST, $data);
		}
	}

	function preference ()
	{
		$loginid = $this->login->getUserid();
		if ($loginid == '') redirect ('main/signin');

		$this->load->view (	
			$this->VIEW_ERROR, 
			array (
				'loginid' => $loginid,
				'message' => 'USER PREFERENCE NOT SUPPORTED YET'
			)
		);
	}

}

?>
