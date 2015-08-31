<?php

class Project extends Controller 
{
	var $VIEW_ERROR = 'error';
	var $VIEW_HOME = 'project_home';
	var $VIEW_EDIT = 'project_edit';
	var $VIEW_DELETE = 'project_delete';
	var $VIEW_CATALOG = 'project_catalog';
	var $VIEW_LOG = 'log';

	function Project ()
	{
		parent::Controller ();

		$this->load->helper ('url');
		$this->load->helper ('form');
		$this->load->library ('Converter', 'converter');
		$this->load->model (CODEPOT_LOGIN_MODEL, 'login');

		$this->load->library ('Language', 'lang');
		$this->lang->load ('common', CODEPOT_LANG);
		$this->lang->load ('project', CODEPOT_LANG);
	}

	function catalog ($filter = '', $offset = '')
	{
		$this->load->model ('ProjectModel', 'projects');
	
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));
		$data['login'] = $login;

		if ($filter == '')
		{
			$search = new stdClass();
			$search->id = '';
			$search->name = '';
			$search->summary = '';
			$search->or = '';
		}
		else
		{
			parse_str ($this->converter->HexToAscii($filter), $search);
			if (!array_key_exists ('id', $search)) $search['id'] = '';
			if (!array_key_exists ('name', $search)) $search['name'] = '';
			if (!array_key_exists ('summary', $search)) $search['summary'] = '';
			if (!array_key_exists ('or', $search)) $search['or'] = '';

			$search = (object) $search;
		}

		$data['search'] = $search;

		$this->load->library ('pagination');

		if ($filter == '' && $offset == '')
		{
			$offset = 0;
			$pagecfg['base_url'] = site_url() . "/project/catalog/";
			$pagecfg['uri_segment'] = 3;
		}
		else if ($filter != '' && $offset == '')
		{
			if (is_numeric($filter))
			{
				$offset = (integer) $filter;
				$pagecfg['base_url'] = site_url() . "/project/catalog/";
				$pagecfg['uri_segment'] = 3;
			}
			else
			{
				$offset = 0;
				$pagecfg['base_url'] = site_url() . "/project/catalog/{$filter}/";
				$pagecfg['uri_segment'] = 4;
			}
		}
		else 
		{
			$offset = (integer) $offset;
			$pagecfg['base_url'] = site_url() . "/project/catalog/{$filter}/";
			$pagecfg['uri_segment'] = 4;
		}

		$num_entries = $this->projects->getNumEntries ($login['id'], $search);
		if ($num_entries === FALSE)
		{
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
			return;
		}

		$pagecfg['total_rows'] = $num_entries;
		$pagecfg['per_page'] = CODEPOT_MAX_PROJECTS_PER_PAGE;
		$pagecfg['first_link'] = $this->lang->line('First');
		$pagecfg['last_link'] = $this->lang->line('Last');

		$projects = $this->projects->getEntries ($login['id'], $offset, $pagecfg['per_page'], $search);
		if ($projects === FALSE)
		{
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->pagination->initialize ($pagecfg);
			$data['page_links'] = $this->pagination->create_links ();
			$data['req_page_offset'] = $offset;
			$data['req_page_size'] = CODEPOT_MAX_PROJECTS_PER_PAGE;
			$data['total_num_projects'] = $num_entries;
			$data['projects'] = $projects;
			$this->load->view ($this->VIEW_CATALOG, $data);
		}
	}

	function home ($projectid = "")
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('LogModel',     'logs');

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '') 
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));

		$data['login'] = $login;

		$project = $this->projects->get ($projectid);
		if ($project === FALSE)
		{
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if ($project === NULL)
		{
			$data['message'] = 
				$this->lang->line('MSG_NO_SUCH_PROJECT') . 
				" - {$projectid}";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			if ($project->public !== 'Y' && $login['id'] == '')
			{
				// non-public projects require sign-in.
				redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));
			}

			$log_entries = $this->logs->getEntries (
				0, CODEPOT_MAX_LOGS_IN_PROJECT_HOME, $projectid);
			if ($log_entries === FALSE)
			{
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$data['project'] = $project;
				$data['log_entries'] = $log_entries;
				$this->load->view ($this->VIEW_HOME, $data);
			}
		}
	}

	function _edit_project ($project, $mode, $login)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');

		$data['login'] = $login;

		// SET VALIDATION RULES
		$this->form_validation->set_rules (
			'project_id', 'ID', 'required|alpha_dash|max_length[32]');
		$this->form_validation->set_rules (
			'project_name', 'name', 'required|max_length[255]');
		$this->form_validation->set_rules (
			'project_summary', 'summary', 'required|max_length[255]');
		$this->form_validation->set_rules (
			'project_description', 'description', 'required');
		$this->form_validation->set_rules (
			'project_commitable', 'commitable', 'alpha');
		$this->form_validation->set_rules (
			'project_public', 'public', 'alpha');
		$this->form_validation->set_rules (
			'project_members', 'members', 'required');
		$this->form_validation->set_error_delimiters(
			'<span class="form_field_error">','</span>');

		$data['message'] = '';
		$data['mode'] = $mode;

		if($this->input->post('project'))
		{
			$tmpid = ($mode == 'update')? 
				$project->id: $this->input->post('project_id');

			// recompose the project information from POST data.
			unset ($project);
			$project->id = $tmpid;
			$project->name = $this->input->post('project_name');
			$project->summary = $this->input->post('project_summary');
			$project->description = $this->input->post('project_description');
			$project->commitable = $this->input->post('project_commitable');
			$project->public = $this->input->post('project_public');
			$project->members = array_unique (preg_split ('/[[:space:],]+/', $this->input->post('project_members')));

			// validate the form
			if ($this->form_validation->run())
			{
				$api_base_url = $this->converter->expand (CODEPOT_API_BASE_URL, $_SERVER);

				// if ok, take action
				$result = ($mode == 'update')?
					$this->projects->update ($login['id'], $project):
					$this->projects->create ($login['id'], $project, $api_base_url, $repo_error);
				if ($result === FALSE)
				{
					if ($repo_error)
						$data['message'] = 'REPOSITORY ERROR';
					else
						$data['message'] = 'DATABASE ERROR';
					$data['project'] = $project;
					$this->load->view ($this->VIEW_EDIT, $data);
				}
				else
				{
					redirect ("project/home/{$project->id}");
				}
			}
			else
			{
				// if not, reload the edit view with an error message
				$data['message'] = $this->lang->line('MSG_FORM_INPUT_INCOMPLETE');
				$data['project'] = $project;
				$this->load->view ($this->VIEW_EDIT, $data);
			}
		}
		else
		{
			if ($mode == 'update')
			{
				$data['project'] = $project;
				$this->load->view ($this->VIEW_EDIT, $data);
			}
			else
			{
				$data['project'] = $project;
				$this->load->view ($this->VIEW_EDIT, $data);
			}
		}
	}

	function create ($projectid = "")
	{
		$this->load->model ('ProjectModel', 'projects');

		$login = $this->login->getUser ();
		if ($login['id'] == '')
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));

		$project->id = $projectid;
		$project->name = '';
		$project->summary = '';
		$project->description = '';
		$project->commitable = 'Y';
		$project->public = 'Y';
		$project->members = array ($login['id']);

		$this->_edit_project ($project, 'create', $login);
	}

	function update ($projectid = '')
	{
		$this->load->model ('ProjectModel', 'projects');

		$login = $this->login->getUser ();
		if ($login['id'] == '')
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));

		$project = $this->projects->get ($projectid);
		if ($project === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if ($project === NULL)
		{
			$data['login'] = $login;
			$data['message'] = 
				$this->lang->line('MSG_NO_SUCH_PROJECT') . 
				" - {$projectid}";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if (!$login['sysadmin?'] &&
		         $this->projects->projectHasMember($project->id, $login['id']) === FALSE)
		{
			$data['project'] = $project;
			$data['login'] = $login;
			$data['message'] = sprintf (
				$this->lang->line('MSG_PROJECT_MEMBERSHIP_REQUIRED'), $projectid);
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->_edit_project ($project, 'update', $login);
		}
	}

	function _delete_project ($project, $login)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');

		$data['login'] = $login;
		$data['message'] = '';

		$this->form_validation->set_rules ('project_confirm', 'confirm', 'alpha');
		$this->form_validation->set_error_delimiters('<span class="form_field_error">','</span>');

		if($this->input->post('project'))
		{
			/* the project form has been posted */
			$data['project_confirm'] = $this->input->post('project_confirm');

			if ($this->form_validation->run())
			{
				if ($data['project_confirm'] == 'yes')
				{
					$result = $this->projects->delete (
						$login['id'], $project, 
						($login['sysadmin?'] || CODEPOT_FORCE_PROJECT_DELETE));
					if ($result === FALSE)
					{
						$data['message'] = 'DATABASE ERROR';
						$data['project'] = $project;
						$this->load->view ($this->VIEW_DELETE, $data);
					}
					else 
					{
						// the project has been deleted successfully.
						// go to the project catalog.	
						redirect ('project/catalog');
					}
				}
				else 
				{
					// the confirm checkbox is not checked.
					// go back to the project home page.
					redirect ("project/home/{$project->id}");
				}
			}
			else
			{
				// the form validation failed.
				// reload the form with an error message.
				$data['message'] = $this->lang->line('MSG_FORM_INPUT_INCOMPLETE');
				$data['project'] = $project;
				$this->load->view ($this->VIEW_DELETE, $data);
			}
		}
		else
		{
			/* no project posting is found. this is the fresh load */
			$data['project_confirm'] = 'no';
			$data['project'] = $project;
			$this->load->view ($this->VIEW_DELETE, $data);
		}
	}

	function delete ($projectid = '')
	{
		$this->load->model ('ProjectModel', 'projects');

		$login = $this->login->getUser ();
		if ($login['id'] == '')
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));

		$project = $this->projects->get ($projectid);
		if ($project === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if ($project === NULL)
		{
			$data['login'] = $login;
			$data['message'] = 
				$this->lang->line('MSG_NO_SUCH_PROJECT') . 
				" - {$projectid}";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if (!$login['sysadmin?'] &&
		         $this->projects->projectHasMember($project->id, $login['id']) === FALSE)
		{
			$data['login'] = $login;
			$data['project'] = $project;
			$data['message'] = sprintf (
				$this->lang->line('MSG_PROJECT_MEMBERSHIP_REQUIRED'), $projectid);
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->_delete_project ($project, $login);
		}
	}

	function log ($projectid = '', $offset = 0)
	{
		$this->load->model ('ProjectModel', 'projects');

		$login = $this->login->getUser ();

		$project = $this->projects->get ($projectid);
		if ($project === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if ($project === NULL)
		{
			$data['login'] = $login;
			$data['message'] = 
				$this->lang->line('MSG_NO_SUCH_PROJECT') . 
				" - {$projectid}";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->load->library ('pagination');
			$this->load->model ('LogModel', 'logs');
		
			$num_log_entries = $this->logs->getNumEntries ($projectid);
			if ($num_log_entries === FALSE)
			{
				$data['login'] = $login;
				$data['project'] = $project;
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
				return;
			}

			$pagecfg['base_url'] = site_url() . "/project/log/{$projectid}/";
			$pagecfg['total_rows'] = $num_log_entries;
			$pagecfg['per_page'] = CODEPOT_MAX_LOGS_PER_PAGE; 
			$pagecfg['uri_segment'] = 4;
			$pagecfg['first_link'] = $this->lang->line('First');
			$pagecfg['last_link'] = $this->lang->line('Last');
	
			$log_entries = $this->logs->getEntries ($offset, $pagecfg['per_page'], $projectid);
			if ($log_entries === FALSE)
			{
				$data['login'] = $login;
				$data['project'] = $project;
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
				return;
			}

			$this->pagination->initialize ($pagecfg);

			$data['project'] = $project;
			$data['login'] = $login;
			$data['log_entries'] = $log_entries;
			$data['page_links'] = $this->pagination->create_links ();

			$this->load->view ($this->VIEW_LOG, $data);
		}
	}

	function enjson_catalog ($filter = '', $offset = '')
	{
		$this->load->model ('ProjectModel', 'projects');
	
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
		{
			$status = 'signin';
			$projects = array();
		}
		else
		{

			if ($filter == '')
			{
				$search->id = '';
				$search->name = '';
				$search->summary = '';
				$search->or = '';
			}
			else
			{
				parse_str ($this->converter->HexToAscii($filter), $search);
				if (!array_key_exists ('id', $search)) $search['id'] = '';
				if (!array_key_exists ('name', $search)) $search['name'] = '';
				if (!array_key_exists ('summary', $search)) $search['summary'] = '';
				if (!array_key_exists ('or', $search)) $search['or'] = '';

				$search = (object) $search;
			}

			if ($filter == '' && $offset == '')
			{
				$offset = 0;
			}
			else if ($filter != '' && $offset == '')
			{
				if (is_numeric($filter))
				{
					$offset = (integer)$filter;
				}
				else
				{
					$offset = 0;
				}
			}
			else 
			{
				$offset = (integer) $offset;
			}

			// get the total number of entries available
			$num_entries = $this->projects->getNumEntries ($login['id'], $search);
			if ($num_entries === FALSE)
			{
				$status = 'dberr';
				$projects = array ();
			}
			else
			{
				// get project entries staring from the offset.
				$projects = $this->projects->getEntries ($login['id'], $offset, CODEPOT_MAX_PROJECTS_PER_PAGE, $search);
				if ($projects === FALSE)
				{
					$status = 'dberr';
					$projects = array ();
				}
				else
				{
					$status = 'ok';
					// exclude the description column
					foreach ($projects as $p) unset ($p->description);
				}
			}
		}

		$result = array (
			'status' => $status,
			'total_num_projects' => $num_entries,
			'req_page_offset' => $offset,
			'req_page_size' => CODEPOT_MAX_PROJECTS_PER_PAGE,
			'projects' => $projects
		);

		print codepot_json_encode ($result);
	}

	function enjson_quickfind ($needle = '')
	{
		// this function is to serve the intermediate search
		// by the quick project finder in the task bar.
		// it returns the array of {id: XXX, value: YYY} in the
		// json format.

		$this->load->model ('ProjectModel', 'projects');

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
		{
			$projects = array ();
		}
		else if (empty($needle))
		{
			// return no result if $needle is empty
			$projects = array ();
		}
		else
		{
			$needle = $this->converter->HexToAscii($needle);
			$projects = $this->projects->quickfindEntries ($login['id'], $needle);
			if ($projects === FALSE) $projects = array ();
		}

		foreach ($projects as &$p)
		{
			$p->label = ($p->id != $p->value)? ($p->id . ' - ' . $p->value): $p->value;
			//$p->value = $p->id;
		}

		print codepot_json_encode ($projects);
	}
}

?>
