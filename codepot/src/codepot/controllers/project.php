<?php

class Project extends Controller 
{
	var $VIEW_ERROR = 'error';
	var $VIEW_HOME = 'project_home';
	var $VIEW_EDIT = 'project_edit';
	var $VIEW_DELETE = 'project_delete';

	function Project ()
	{
		parent::Controller ();

		$this->load->helper ('url');
		$this->load->helper ('form');
		$this->load->library ('Converter', 'converter');
		$this->load->model (CODEPOT_LOGIN_MODEL, 'login');

		$this->load->library ('Language', 'lang');
		$this->lang->load ('common', CODEPOT_LANG);
	}

	function home ($projectid = "")
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('LogModel',     'logs');

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '') 
			redirect ('main/signin');

		$data['login'] = $login;

		$project = $this->projects->get ($projectid);
		if ($project === FALSE)
		{
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if ($project === NULL)
		{
			$data['message'] = "NO SUCH PROJECT - $projectid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$svn_commits = $this->logs->getSvnCommits (
				CODEPOT_MAX_SVN_COMMITS_IN_PROJECT, $projectid);
			if ($svn_commits === FALSE)
			{
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$data['project'] = $project;
				$data['svn_commits'] = $svn_commits;
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
			$project->members = $this->input->post('project_members');

			// validate the form
			if ($this->form_validation->run())
			{
				$api_base_url = $this->converter->expand (CODEPOT_API_BASE_URL, $_SERVER);

				// if ok, take action
				$result = ($mode == 'update')?
					$this->projects->update ($login['id'], $project):
					$this->projects->create ($login['id'], $project, $api_base_url);
				if ($result === FALSE)
				{
					$data['message'] = 'DATABASE ERROR';
					$data['project'] = $project;
					$this->load->view ($this->VIEW_EDIT, $data);
				}
				else
				{
					redirect ('project/home/' . $project->id);
				}
			}
			else
			{
				// if not, reload the edit view with an error message
				$data['message'] = 'Your input is not complete, Bro.';
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
		if ($login['id'] == '') redirect ('main/signin');

		$project->id = $projectid;
		$project->name = '';
		$project->summary = '';
		$project->description = '';
		$project->members = $login['id'];

		$this->_edit_project ($project, 'create', $login);
	}

	function update ($projectid)
	{
		$this->load->model ('ProjectModel', 'projects');

		$login = $this->login->getUser ();
		if ($login['id'] == '') redirect ('main/signin');

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
			$data['message'] = "NO SUCH PROJECT - $projectid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if (!$login['sysadmin?'] &&
		         $this->projects->projectHasMember($project->id, $login['id']) === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = "NO PERMISSION - $projectid";
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
					$result = $this->projects->delete ($login['id'], $project);
					if ($result === FALSE)
					{
						$data['message'] = 'DATABASE ERROR';
						$data['project'] = $project;
						$this->load->view ($this->VIEW_DELETE, $data);
					}
					else 
					{
						// the project has been deleted successfully.
						// go back to the user home.	
						redirect ('user/projectlist');
					}
				}
				else 
				{
					// the confirm checkbox is not checked.
					// go back to the project home page.
					redirect ('project/home/' . $project->id);
				}
			}
			else
			{
				// the form validation failed.
				// reload the form with an error message.
				$data['message'] = "Your input is not complete, Bro.";
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

	function delete ($projectid)
	{
		$this->load->model ('ProjectModel', 'projects');

		$login = $this->login->getUser ();
		if ($login['id'] == '') redirect ('main/signin');

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
			$data['message'] = "NO SUCH PROJECT - $projectid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if (!$login['sysadmin?'] &&
		         $this->projects->projectHasMember($project->id, $login['id']) === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = "NO PERMISSION - $projectid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->_delete_project ($project, $login);
		}
	}
}

?>
