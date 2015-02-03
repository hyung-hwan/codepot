<?php

class User extends Controller 
{
	var $VIEW_ERROR = 'error';
	var $VIEW_LOG = 'log';
	var $VIEW_HOME = 'user_home';
	var $VIEW_SETTINGS = 'user_settings';

	function User ()
	{
		parent::Controller ();

		$this->load->helper ('url');
		$this->load->helper ('form');
		$this->load->library ('Converter', 'converter');
		$this->load->model (CODEPOT_LOGIN_MODEL, 'login');

		$this->load->library ('Language', 'lang');
		$this->lang->load ('common', CODEPOT_LANG);
		$this->lang->load ('user', CODEPOT_LANG);
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

		if ($login['id'] == '')
		{
			redirect ('site/home');
			return;
		}

		$this->load->library ('IssueHelper', 'issuehelper');
		$this->lang->load ('issue', CODEPOT_LANG);

		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('IssueModel', 'issues');

		$projects = $this->projects->getMyProjects ($login['id']);

		$issues = $this->issues->getMyIssues (
			$login['id'], $this->issuehelper->_get_open_status_array($this->lang));
		if ($projects === FALSE || $issues === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$data['login'] = $login;
			$data['projects'] = $projects;
			$data['issues'] = $issues;
			$data['issue_type_array'] = $this->issuehelper->_get_type_array($this->lang);
			$data['issue_status_array'] = $this->issuehelper->_get_status_array($this->lang);
			$data['issue_priority_array'] = $this->issuehelper->_get_priority_array($this->lang);
			$this->load->view ($this->VIEW_HOME, $data);
		}
	}

	function log ($offset = 0)
	{
		$login = $this->login->getUser ();

		if ($login['id'] == '')
		{
			redirect ('site/home');
			return;
		}

		$this->load->model ('ProjectModel', 'projects');
		$user->id = $login['id'];

		$myprojs = $this->projects->getMyProjects ($login['id']);
		if ($myprojs === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if ($myprojs === NULL)
		{
			$data['login'] = $login;
			$data['message'] = 'NO PROJECTS';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->load->library ('pagination');
			$this->load->model ('LogModel', 'logs');

			$numprojs = count($myprojs);
			for ($i = 0; $i < $numprojs; $i++) 
				$projids[$i] = $myprojs[$i]->id;
		
			$num_log_entries = $this->logs->getNumEntries ($projids);
			if ($num_log_entries === FALSE)
			{
				$data['login'] = $login;
				$data['project'] = $project;
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
				return;
			}

			$pagecfg['base_url'] = site_url() . "/user/log/";
			$pagecfg['total_rows'] = $num_log_entries;
			$pagecfg['per_page'] = CODEPOT_MAX_LOGS_PER_PAGE; 
			$pagecfg['uri_segment'] = 3;
			$pagecfg['first_link'] = $this->lang->line('First');
			$pagecfg['last_link'] = $this->lang->line('Last');
	
			$log_entries = $this->logs->getEntries ($offset, $pagecfg['per_page'], $projids);
			if ($log_entries === FALSE)
			{
				$data['login'] = $login;
				$data['project'] = $project;
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
				return;
			}

			$this->pagination->initialize ($pagecfg);

			$data['user'] = $user;
			$data['login'] = $login;
			$data['log_entries'] = $log_entries;
			$data['page_links'] = $this->pagination->create_links ();

			$this->load->view ($this->VIEW_LOG, $data);
		}
	}

	function settings ()
	{
		$this->load->model ('UserModel', 'users');
		$this->load->library(array('encrypt', 'form_validation', 'session'));

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ('main/signin');

		if ($login['id'] == '')
		{
			redirect ('site/home');
			return;
		}

		$data['login'] = $login;
		$data['message'] = '';

		$icon_fname = FALSE;
		$uploaded_fname = FALSE;

		if($this->input->post('settings'))
		{
			if (array_key_exists ('icon_img_file_name', $_FILES))
			{
				$fname = $_FILES['icon_img_file_name']['name'];

				if (strpos ($fname, ':') !== FALSE)
				{
					$data['message'] = $this->lang->line ('FILE_MSG_NAME_NO_COLON');
					$data['file'] = $file;
					$this->load->view ($this->VIEW_EDIT, $data);
					return;
				}

				// delete all \" instances ... 
				$_FILES['icon_img_file_name']['type'] = 
					str_replace('\"', '', $_FILES['icon_img_file_name']['type']);
				// delete all \\ instances ...  
				$_FILES['icon_img_file_name']['type'] = 
					str_replace('\\', '', $_FILES['icon_img_file_name']['type']);

				$config['allowed_types'] = 'png';
				$config['upload_path'] = CODEPOT_USERICON_DIR;
				$config['max_size'] = CODEPOT_MAX_UPLOAD_SIZE;
				$config['max_width'] = 100; // TODO: make it configurable.
				$config['max_height'] = 100;
				$config['encrypt_name'] = TRUE;

				$this->load->library ('upload');
				$this->upload->initialize ($config);
		
				if ($this->upload->do_upload ('icon_img_file_name'))
				{
					$upload = $this->upload->data ();
					$uploaded_fname = $upload['file_name'];
					$icon_fname = $login['id'] . '.png';
				}
			}

			$settings->code_hide_line_num = $this->input->post('code_hide_line_num');
			$settings->code_hide_details = $this->input->post('code_hide_details');
			$settings->icon_name = $icon_fname;
			$settings->uploaded_icon_name = $uploaded_fname;

			if ($this->users->storeSettings ($login['id'], $settings) === FALSE)
			{
				@unlink (CODEPOT_USERICON_DIR . '/' . $uploaded_fname);
				$data['message'] = 'DATABASE ERROR';
				$data['settings'] = $settings;
				$this->load->view ($this->VIEW_SETTINGS, $data);
			}
			else
			{
				$this->login->setUserSettings ($settings);

				$data['message'] = 'SETTINGS STORED SUCCESSFULLY';
				$data['settings'] = $settings;
				$this->load->view ($this->VIEW_SETTINGS, $data);
			}
		}
		else
		{
			$settings = $this->users->fetchSettings ($login['id']);
			if ($settings === FALSE || $settings === NULL)
			{
				if ($settings === FALSE) $data['message'] = 'DATABASE ERROR';
				$settings->code_hide_line_num = ' ';
				$settings->code_hide_details = ' ';
				$settings->icon_name = '';
			}

			$data['settings'] = $settings;
			$this->load->view ($this->VIEW_SETTINGS, $data);
		}
	}
}

?>
