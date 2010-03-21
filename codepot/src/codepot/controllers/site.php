<?php

class Site extends Controller 
{
	var $VIEW_ERROR = 'error';
	var $VIEW_SHOW = 'site_show';
	var $VIEW_HOME = 'site_home';
	var $VIEW_EDIT = 'site_edit';
	var $VIEW_DELETE = 'site_delete';
	var $VIEW_LOG = 'log';
        var $VIEW_PROJECT_LIST = 'project_list';
        var $VIEW_SITE_ADMINHOME = 'site_adminhome';

	function Site ()
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

                $site = $this->sites->get ($this->config->config['language']);
		if ($site === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
			return;
		}
		if ($site === NULL && CODEPOT_DEFAULT_SITE_LANGUAGE != '') 
		{
                	$site = $this->sites->get (CODEPOT_DEFAULT_SITE_LANGUAGE);
			if ($site === FALSE)
			{
				$data['login'] = $login;
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
				return;
			}
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

		$log_entries = $this->logs->getEntries (0, CODEPOT_MAX_LOGS_IN_SITE_HOME);
		if ($log_entries === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
			return;
		}

		$data['login'] = $login;
		$data['latest_projects'] = $latest_projects;
		$data['log_entries'] = $log_entries;
		$data['site'] = $site;
		//$data['user_name'] = '';
		//$data['user_pass'] = '';
		$this->load->view ($this->VIEW_HOME, $data);
	}

	function adminhome ()
	{
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ('main/signin');

		$this->load->model ('SiteModel', 'sites');

		$sites = $this->sites->getAll ($login['id']);

		if ($sites === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$data['login'] = $login;
			$data['sites'] = $sites;
			$this->load->view ($this->VIEW_SITE_ADMINHOME, $data);
		}
	}

	function show ($siteid)
	{
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ('main/signin');

		$this->load->model ('SiteModel', 'sites');

		$data['login'] = $login;

                $site = $this->sites->get ($siteid);
		if ($site === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
			return;
		}

		if ($site === NULL)
		{
			$data['message'] = "NO SUCH SITE - {$siteid}";
			$this->load->view ($this->VIEW_ERROR, $data);
			return;
		}

		$data['site'] = $site;
		$this->load->view ($this->VIEW_SHOW, $data);
	}

	function _edit_site ($site, $mode, $login)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');

		$data['login'] = $login;

                // SET VALIDATION RULES
		$this->form_validation->set_rules (
			'site_id', 'ID', 'required|alpha_dash|max_length[32]');
		$this->form_validation->set_rules (
			'site_name', 'name', 'required|max_length[128]');
		$this->form_validation->set_rules (
			'site_text', 'text', 'required');
		$this->form_validation->set_error_delimiters(
			'<span class="form_field_error">','</span>');

		$data['message'] = '';
		$data['mode'] = $mode;

		if($this->input->post('site'))
		{
			$tmpid = ($mode == 'update')? 
				$site->id: $this->input->post('site_id');

			// recompose the site information from POST data.
			unset ($site);
			$site->id = $tmpid;
			$site->name = $this->input->post('site_name');
			$site->text = $this->input->post('site_text');

			// validate the form
			if ($this->form_validation->run())
			{
				// if ok, take action
				$result = ($mode == 'update')?
					$this->sites->update ($login['id'], $site):
					$this->sites->create ($login['id'], $site);
				if ($result === FALSE)
				{
					$data['message'] = 'DATABASE ERROR';
					$data['site'] = $site;
					$this->load->view ($this->VIEW_EDIT, $data);
				}
				else
				{
					redirect ("site/show/{$site->id}");
				}
			}
			else
			{
				// if not, reload the edit view with an error message
				$data['message'] = 'Your input is not complete, Bro.';
				$data['site'] = $site;
				$this->load->view ($this->VIEW_EDIT, $data);
			}
		}
		else
		{
			if ($mode == 'update')
			{
				$data['site'] = $site;
				$this->load->view ($this->VIEW_EDIT, $data);
			}
			else
			{
				$data['site'] = $site;
				$this->load->view ($this->VIEW_EDIT, $data);
			}
		}
	}

	function create ($siteid = "")
	{
		$this->load->model ('SiteModel', 'sites');

		$login = $this->login->getUser ();
		if ($login['id'] == '') redirect ('main/signin');

		if (!$login['sysadmin?'])
		{
			$data['login'] = $login;
			$data['message'] = "NO PERMISSION";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$site->id = $siteid;
			$site->name = '';
			$site->text = '';

			$this->_edit_site ($site, 'create', $login);
		}
	}

	function update ($siteid)
	{
		$this->load->model ('SiteModel', 'sites');

		$login = $this->login->getUser ();
		if ($login['id'] == '') redirect ('main/signin');

		$site = $this->sites->get ($siteid);
		if ($site === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if ($site === NULL)
		{
			$data['login'] = $login;
			$data['message'] = "NO SUCH SITE - $siteid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if (!$login['sysadmin?'])
		{
			$data['login'] = $login;
			$data['message'] = "NO PERMISSION - $siteid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->_edit_site ($site, 'update', $login);
		}
	}

	function _delete_site ($site, $login)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');

		$data['login'] = $login;
		$data['message'] = '';

		$this->form_validation->set_rules ('site_confirm', 'confirm', 'alpha');
		$this->form_validation->set_error_delimiters('<span class="form_field_error">','</span>');

		if($this->input->post('site'))
		{
			/* the site form has been posted */
			$data['site_confirm'] = $this->input->post('site_confirm');

			if ($this->form_validation->run())
			{
				if ($data['site_confirm'] == 'yes')
				{
					$result = $this->sites->delete ($login['id'], $site);
					if ($result === FALSE)
					{
						$data['message'] = 'DATABASE ERROR';
						$data['site'] = $site;
						$this->load->view ($this->VIEW_DELETE, $data);
					}
					else 
					{
						// the site has been deleted successfully.
						// go back to the site admin home.	
						redirect ('site/adminhome');
					}
				}
				else 
				{
					// the confirm checkbox is not checked.
					// go back to the site adminhome page.
					redirect ('site/adminhome');
				}
			}
			else
			{
				// the form validation failed.
				// reload the form with an error message.
				$data['message'] = "Your input is not complete, Bro.";
				$data['site'] = $site;
				$this->load->view ($this->VIEW_DELETE, $data);
			}
		}
		else
		{
			/* no site posting is found. this is the fresh load */
			$data['site_confirm'] = 'no';
			$data['site'] = $site;
			$this->load->view ($this->VIEW_DELETE, $data);
		}
	}

	function delete ($siteid)
	{
		$this->load->model ('SiteModel', 'sites');

		$login = $this->login->getUser ();
		if ($login['id'] == '') redirect ('main/signin');

		$site = $this->sites->get ($siteid);
		if ($site === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if ($site === NULL)
		{
			$data['login'] = $login;
			$data['message'] = "NO SUCH SITE - $siteid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if (!$login['sysadmin?'])
		{
			$data['login'] = $login;
			$data['message'] = "NO PERMISSION - $siteid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->_delete_site ($site, $login);
		}
	}

	function log ($offset = 0)
	{
		$login = $this->login->getUser ();

		$this->load->library ('pagination');
		$this->load->model ('LogModel', 'logs');
		
		$num_log_entries = $this->logs->getNumEntries ();
		if ($num_log_entries === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
			return;
		}

		$pagecfg['base_url'] = site_url() . '/site/log/';
		$pagecfg['total_rows'] = $num_log_entries;
		$pagecfg['per_page'] = CODEPOT_MAX_LOGS_PER_PAGE; 
		$pagecfg['uri_segment'] = 3;
		$pagecfg['first_link'] = $this->lang->line('First');
		$pagecfg['last_link'] = $this->lang->line('Last');

		$log_entries = $this->logs->getEntries ($offset, $pagecfg['per_page']);
		if ($log_entries === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
			return;
		}


		$this->pagination->initialize ($pagecfg);

		$data['login'] = $login;
		$data['log_entries'] = $log_entries;
		$data['page_links'] = $this->pagination->create_links ();

		$this->load->view ($this->VIEW_LOG, $data);
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
