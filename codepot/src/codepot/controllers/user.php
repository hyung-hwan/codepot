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
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ('main/signin');

		$this->load->model ('SiteModel', 'sites');
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('LogModel', 'logs');

                $site = $this->sites->get (CODEPOT_DEFAULT_SITEID);
		if ($site === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
			return;
		}
		if ($site === NULL) $site = $this->sites->getDefault ();

		$latest_projects = $this->projects->getLatestProjects ($login['id'], CODEPOT_MAX_LATEST_PROJECTS);
		if ($latest_projects === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
			return;
		}

		$svn_commits = $this->logs->getSvnCommits (CODEPOT_MAX_SVN_COMMITS);
		if ($svn_commits === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
			return;
		}

		$data['login'] = $login;
		$data['latest_projects'] = $latest_projects;
		$data['svn_commits'] = $svn_commits;
		$data['site'] = $site;
		//$data['user_name'] = '';
		//$data['user_pass'] = '';
		$this->load->view ($this->VIEW_HOME, $data);
	}

	function projectlist ()
	{
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ('main/signin');

		$this->load->model ('ProjectModel', 'projects');

		$projects = $this->projects->getMyProjects ($login['id']);
		$other_projects = $this->projects->getOtherProjects ($login['id']);

		if ($projects === FALSE || $other_projects === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$data['login'] = $login;
			$data['projects'] = $projects;
			$data['other_projects'] = $other_projects;
			$this->load->view ($this->VIEW_PROJECT_LIST, $data);
		}
	}

	function preference ()
	{
		$login = $this->login->getUser();
		if ($login['id'] == '') redirect ('main/signin');

		$this->load->view (	
			$this->VIEW_ERROR, 
			array (
				'login' => $login,
				'message' => 'USER PREFERENCE NOT SUPPORTED YET'
			)
		);
	}

	function admin ()
	{
		$login = $this->login->getUser();
		if ($login['id'] == '') redirect ('main/signin');


		if ($login['sysadmin?'])
		{
			echo "...Site Administration...";
		}
		else
		{
			$this->load->view (	
				$this->VIEW_ERROR, 
				array (
					'login' => $login,
					'message' => 'NO PERMISSION'
				)
			);
		}
	}

}

?>
