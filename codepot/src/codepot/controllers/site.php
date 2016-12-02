<?php

class Site extends Controller 
{
	var $VIEW_ERROR = 'error';
	var $VIEW_SHOW = 'site_show';
	var $VIEW_HOME = 'site_home';
	var $VIEW_EDIT = 'site_edit';
	var $VIEW_DELETE = 'site_delete';
	var $VIEW_CATALOG = 'site_catalog';
	var $VIEW_LOG = 'log';

	function Site ()
	{
		parent::Controller ();

		$this->load->helper ('url');
		$this->load->helper ('form');
		$this->load->library ('Converter', 'converter');
		$this->load->library ('WikiHelper', 'wikihelper');
		$this->load->model (CODEPOT_LOGIN_MODEL, 'login');

		$this->load->library ('Language', 'lang');
		$this->lang->load ('common', CODEPOT_LANG);
		$this->lang->load ('site', CODEPOT_LANG);

		$this->load->library ('IssueHelper', 'issuehelper');
		$this->lang->load ('issue', CODEPOT_LANG);
	}

	function index ()
	{
		return $this->home ();
	}

	function home ()
	{
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));

		$this->load->model ('SiteModel', 'sites');
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('LogModel', 'logs');
		$this->load->model ('IssueModel', 'issues');

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

		// get the issue for all users
		/*
		$issues = $this->issues->getMyIssues (
			'', $this->issuehelper->_get_open_status_array($this->lang));
		$recently_resolved_issues = $this->issues->getMyIssues (
			'', $this->issuehelper->_get_resolved_status_array($this->lang), 168);
		*/

		$open_issue_counts_per_project = $this->issues->countIssuesPerProject (
			'', $this->issuehelper->_get_open_status_array($this->lang), 0);

		if ($login['id'] == '')
		{
			$your_open_issue_counts_per_project = array ();
		}
		else
		{
			$your_open_issue_counts_per_project = $this->issues->countIssuesPerProject (
				$login['id'], $this->issuehelper->_get_open_status_array($this->lang), 0);
		}

		if (CODEPOT_MAX_TOP_PROJECTS > 0)
			$commit_counts_per_project = $this->logs->countCodeCommitsPerProject ('', 0, CODEPOT_MAX_TOP_PROJECTS);
		else
			$commit_counts_per_project = array();

		if (CODEPOT_MAX_TOP_COMMITTERS > 0)
			$commit_counts_per_user = $this->logs->countCodeCommitsPerUser ('', 0, CODEPOT_MAX_TOP_COMMITTERS);
		else
			$commit_counts_per_user = array();

		if (/*$issues === FALSE || $recently_resolved_issues === FALSE ||*/
		    $open_issue_counts_per_project === FALSE ||
		    $your_open_issue_counts_per_project === FALSE ||
		    $commit_counts_per_project === FALSE ||
		    $commit_counts_per_user === FALSE)
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
		/*$data['issues'] = $issues;
		$data['recently_resolved_issues'] = $recently_resolved_issues;*/
		$data['open_issue_counts_per_project'] = $open_issue_counts_per_project;
		$data['your_open_issue_counts_per_project'] = $your_open_issue_counts_per_project;
		$data['commit_counts_per_project'] = $commit_counts_per_project;
		$data['commit_counts_per_user'] = $commit_counts_per_user;
		$data['issue_type_array'] = $this->issuehelper->_get_type_array($this->lang);
		$data['issue_status_array'] = $this->issuehelper->_get_status_array($this->lang);
		$data['issue_priority_array'] = $this->issuehelper->_get_priority_array($this->lang);

		//$data['user_name'] = '';
		//$data['user_pass'] = '';
		$this->load->view ($this->VIEW_HOME, $data);
	}

	function catalog ()
	{
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));

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
			$this->load->view ($this->VIEW_CATALOG, $data);
		}
	}

	function show ($siteid = '')
	{
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));

		$this->load->model ('SiteModel', 'sites');

		$data['login'] = $login;

		$site = $this->sites->get ($siteid);
		if ($site === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if ($site === NULL)
		{
			$data['message'] = sprintf ($this->lang->line('SITE_MSG_NO_SUCH_SITE'), $siteid);
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$data['site'] = $site;
			$this->load->view ($this->VIEW_SHOW, $data);
		}
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
			'site_summary', 'summary', 'max_length[255]');
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
			$site->summary = $this->input->post('site_summary');
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
				$data['message'] = $this->lang->line('MSG_FORM_INPUT_INCOMPLETE');
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

	function create ($siteid = '')
	{
		$this->load->model ('SiteModel', 'sites');

		$login = $this->login->getUser ();
		if ($login['id'] == '')
			redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));

		if (!$login['sysadmin?'])
		{
			$data['login'] = $login;
			$data['message'] = $this->lang->line('SITE_MSG_ADMINISTRATORSHIP_REQUIRED');
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$site->id = $siteid;
			$site->name = '';
			$site->summary = '';
			$site->text = '';

			$this->_edit_site ($site, 'create', $login);
		}
	}

	function update ($siteid = '')
	{
		$this->load->model ('SiteModel', 'sites');

		$login = $this->login->getUser ();
		if ($login['id'] == '')
			redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));

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
			$data['message'] = sprintf ($this->lang->line('SITE_MSG_NO_SUCH_SITE'), $siteid);
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if (!$login['sysadmin?'])
		{
			$data['login'] = $login;
			$data['message'] = $this->lang->line('SITE_MSG_ADMINISTRATORSHIP_REQUIRED');
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
						// go back to the site catalog page.	
						redirect ('site/catalog');
					}
				}
				else 
				{
					// the confirm checkbox is not checked.
					// go back to the site catalog page.
					redirect ('site/catalog');
				}
			}
			else
			{
				// the form validation failed.
				// reload the form with an error message.
				$data['message'] = $this->lang->line('MSG_FORM_INPUT_INCOMPLETE');
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

	function delete ($siteid = '')
	{
		$this->load->model ('SiteModel', 'sites');

		$login = $this->login->getUser ();
		if ($login['id'] == '')
			redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));

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
			$data['message'] = sprintf ($this->lang->line('SITE_MSG_NO_SUCH_SITE'), $siteid);
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if (!$login['sysadmin?'])
		{
			$data['login'] = $login;
			$data['message'] = $this->lang->line('SITE_MSG_ADMINISTRATORSHIP_REQUIRED');
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
		$this->load->model ('SiteModel', 'sites');

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

		if ($login['sysadmin?'] && 
		    $this->input->post('purge_log') == 'yes')
		{
			$this->logs->purge ();
		}

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

		$data['site'] = $site;
		$data['login'] = $login;
		$data['log_entries'] = $log_entries;
		$data['page_links'] = $this->pagination->create_links ();

		$this->load->view ($this->VIEW_LOG, $data);
	}

/*
	function userlog ($userid = '', $offset = 0)
	{
		if ($userid != '') 
			$dec_userid = $this->converter->HexToAscii($userid);
		else $dec_userid = $userid;

		$login = $this->login->getUser ();

		$this->load->library ('pagination');
		$this->load->model ('LogModel', 'logs');
		$this->load->model ('SiteModel', 'sites');

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

		if ($login['sysadmin?'] && 
		    $this->input->post('purge_log') == 'yes')
		{
			$this->logs->purge ();
		}

		$num_log_entries = $this->logs->getNumEntries ('', $dec_userid);
		if ($num_log_entries === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
			return;
		}

		$pagecfg['base_url'] = site_url() . "/site/userlog/{$userid}/";
		$pagecfg['total_rows'] = $num_log_entries;
		$pagecfg['per_page'] = CODEPOT_MAX_LOGS_PER_PAGE; 
		$pagecfg['uri_segment'] = 4;
		$pagecfg['first_link'] = $this->lang->line('First');
		$pagecfg['last_link'] = $this->lang->line('Last');

		$log_entries = $this->logs->getEntries ($offset, $pagecfg['per_page'], '', $dec_userid);
		if ($log_entries === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
			return;
		}

		$this->pagination->initialize ($pagecfg);

		$data['site'] = $site;
		$data['login'] = $login;
		$data['log_entries'] = $log_entries;
		$data['page_links'] = $this->pagination->create_links ();

		$this->load->view ($this->VIEW_LOG, $data);
	}
*/

	function wiki ($xlink = '')
	{
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));

		$data['login'] = $login;

		$linkname = $this->converter->HexToAscii ($xlink);

		$link = $this->wikihelper->parseLink ($linkname, NULL, $this->converter);
		if ($link === FALSE || $link === NULL)
		{
			$data['message'] = "INVALID LINK - {$linkname}";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			redirect ($link);
		}
	}

	function image ($xlink = '')
	{
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));

		$data['login'] = $login;

		$linkname = $this->converter->HexToAscii ($xlink);

		$part = explode (':', $linkname);
		if (count($part) == 3)
		{
			$hexwikiname = $this->converter->AsciiTohex($part[1]);
			$hexattname = $this->converter->AsciiTohex($part[2]);
			redirect ("wiki/attachment/{$part[0]}/{$hexwikiname}/{$hexattname}");
		}
	}
}

?>
