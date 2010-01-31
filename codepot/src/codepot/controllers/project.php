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

		$loginid = $this->login->getUserid();
		if (CODEPOT_ALWAYS_REQUIRE_SIGNIN && $loginid == '') 
			redirect ('main/signin');

		$data['loginid'] = $loginid;

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
			$data['project'] = $project;
			$this->load->view ($this->VIEW_HOME, $data);
		}
	}

	function _edit_project ($project, $mode, $loginid)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');

		$data['loginid'] = $loginid;

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
					$this->projects->update ($loginid, $project):
					$this->projects->create ($loginid, $project, $api_base_url);
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

		$loginid = $this->login->getUserid ();
		if ($loginid == '') redirect ('main/signin');

		$project->id = $projectid;
		$project->name = '';
		$project->summary = '';
		$project->description = '';
		$project->members = $loginid;

		$this->_edit_project ($project, 'create', $loginid);
	}

	function update ($projectid)
	{
		$this->load->model ('ProjectModel', 'projects');

		$loginid = $this->login->getUserid ();
		if ($loginid == '') redirect ('main/signin');

		$project = $this->projects->get ($projectid);
		if ($project === FALSE)
		{
			$data['loginid'] = $loginid;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if ($project === NULL)
		{
			$data['loginid'] = $loginid;
			$data['message'] = "NO SUCH PROJECT - $projectid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if (!$this->login->isSysadmin() &&
		         $this->projects->projectHasMember($project->id, $loginid) === FALSE)
		{
			$data['loginid'] = $loginid;
			$data['message'] = "NO PERMISSION - $projectid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->_edit_project ($project, 'update', $loginid);
		}
	}

	function _delete_project ($project, $loginid)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');

		$data['loginid'] = $loginid;
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
					$result = $this->projects->delete ($loginid, $project);
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

		$loginid = $this->login->getUserid ();
		if ($loginid == '') redirect ('main/signin');

		$project = $this->projects->get ($projectid);
		if ($project === FALSE)
		{
			$data['loginid'] = $loginid;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if ($project === NULL)
		{
			$data['loginid'] = $loginid;
			$data['message'] = "NO SUCH PROJECT - $projectid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if (!$this->login->isSysadmin() &&
		         $this->projects->projectHasMember($project->id, $loginid) === FALSE)
		{
			$data['loginid'] = $loginid;
			$data['message'] = "NO PERMISSION - $projectid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->_delete_project ($project, $loginid);
		}
	}
}

?>
