<?php

class Issue extends Controller
{
	var $VIEW_ERROR = 'error';
	var $VIEW_HOME = 'issue_home';
	var $VIEW_SHOW = 'issue_show';
	var $VIEW_EDIT = 'issue_edit';
	var $VIEW_DELETE = 'issue_delete';

	function Issue ()
	{
		parent::Controller ();

		$this->load->helper ('url');
		$this->load->helper ('form');
		$this->load->library ('Converter', 'converter');
		$this->load->model (CODEPOT_LOGIN_MODEL, 'login');

		$this->load->library ('Language', 'lang');
		$this->lang->load ('common', CODEPOT_LANG);

	}

	function home ($projectid = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('IssueModel', 'issues');
	
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
			$data['message'] = 
				$this->lang->line('MSG_NO_SUCH_PROJECT') . 
				" - {$projectid}";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			if ($this->input->post('filter'))
			{
				$filter->status = $this->input->post('filter_status');
				$filter->owner = $this->input->post('filter_owner');
				$data['filter'] = $filter;
			}
			else
			{
				$filter->status = '';
				$filter->owner = '';
				$data['filter'] = $filter;
			}

			$issues = $this->issues->getAll ($login['id'], $project);
			if ($issues === FALSE)
			{
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$data['project'] = $project;
				$data['issues'] = $issues;
				$this->load->view ($this->VIEW_HOME, $data);
			}
		}
	}

	function show ($projectid = '', $hexid = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('IssueModel', 'issues');

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ('main/signin');
		$data['login'] = $login;

		if ($hexid == '')
		{
			$data['message'] = 'INVALID PARAMETERS';
			$this->load->view ($this->VIEW_ERROR, $data);
			return;
		}

		$id = $this->converter->HexToAscii ($hexid);

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
			if ($this->input->post('issue_change'))
			{
				$change->type = $this->input->post('issue_change_type');
				$change->status = $this->input->post('issue_change_status');
				$change->owner = $this->input->post('issue_change_owner');
				$change->priority = $this->input->post('issue_change_priority');
				$change->comment = $this->input->post('issue_change_comment');

				if ($this->issues->change ($login['id'], $project, $id, $change) === FALSE)
				{
					$data['project'] = $project;
					$data['message'] = 'DATABASE ERROR';
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else
				{
					redirect ("/issue/show/{$projectid}/{$hexid}");
				}
				return;
			}

			$issue = $this->issues->get ($login['id'], $project, $id);
			if ($issue === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else if ($issue === NULL)
			{
				$data['project'] = $project;
				$data['message'] = 
					$this->lang->line('MSG_NO_SUCH_ISSUE') . 
					" - {$id}";
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$data['project'] = $project;
				$data['issue'] = $issue;
				$this->load->view ($this->VIEW_SHOW, $data);
			}
		}
	}

	function _edit_issue ($projectid, $hexid, $mode)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('IssueModel', 'issues');

		$login = $this->login->getUser ();
		if ($login['id'] == '') redirect ('main');
		$data['login'] = $login;

		$id = $this->converter->HexToAscii ($hexid);

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
		else if (!$login['sysadmin?'] && 
		         $this->projects->projectHasMember($project->id, $login['id']) === FALSE)
		{
			$data['project'] = $project;
			$data['message'] = "NO PERMISSION - $projectid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->form_validation->set_rules (
				'issue_projectid', 'project ID', 'required|alpha_dash|max_length[32]');
			$this->form_validation->set_rules (
				'issue_summary', 'summary', 'required|max_length[255]');
			$this->form_validation->set_rules (
				'issue_status', 'status', 'required');
			$this->form_validation->set_rules (
				'issue_type', 'type', 'required');
			$this->form_validation->set_rules (
				'issue_priority', 'priority', 'required');
			$this->form_validation->set_rules (
				'issue_owner', 'owner', 'required');
			$this->form_validation->set_rules (
				'issue_description', 'description', 'required');
			$this->form_validation->set_error_delimiters (
				'<span class="form_field_error">','</span>');

			$data['mode'] = $mode;
			$data['message'] = '';
			$data['project'] = $project;

			if ($this->input->post('issue'))
			{
				$issue->projectid = $this->input->post('issue_projectid');
				$issue->id = $this->input->post('issue_id');
				$issue->summary = $this->input->post('issue_summary');
				$issue->description = $this->input->post('issue_description');
				$issue->type = $this->input->post('issue_type');
				$issue->status = $this->input->post('issue_status');
				$issue->priority = $this->input->post('issue_priority');
				$issue->owner = $this->input->post('issue_owner');

				if ($this->form_validation->run())
				{
					$id = ($mode == 'update')?
						$this->issues->update ($login['id'], $issue):
						$this->issues->create ($login['id'], $issue);
					if ($id === FALSE)
					{
						$data['message'] = 'DATABASE ERROR';
						$data['issue'] = $issue;
						$this->load->view ($this->VIEW_EDIT, $data);	
					}
					else
					{
						redirect ("issue/show/{$project->id}/{$hexid}");
					}
				}
				else
				{
					$data['message'] = "Your input is not complete, Bro";
					$data['issue'] = $issue;
					$this->load->view ($this->VIEW_EDIT, $data);	
				}
			}
			else
			{
				if ($mode == 'update')
				{
					$issue = $this->issues->get ($login['id'], $project, $id);
					if ($issue === FALSE)
					{
						$data['message'] = 'DATABASE ERROR';
						$this->load->view ($this->VIEW_ERROR, $data);
					}
					else if ($issue == NULL)
					{
						$data['message'] = 
							$this->lang->line('MSG_NO_SUCH_ISSUE') . 
							" - {$id}";
						$this->load->view ($this->VIEW_ERROR, $data);
					}
					else
					{
						$data['issue'] = $issue;
						$this->load->view ($this->VIEW_EDIT, $data);	
					}
				}
				else
				{
					$issue->projectid = $projectid;
					$issue->id = $id;
					$issue->summary = '';
					$issue->type = '';
					$issue->status = '';
					$issue->owner = '';
					$issue->priority = '';
					$issue->description = '';

					$data['issue'] = $issue;
					$this->load->view ($this->VIEW_EDIT, $data);	
				}
			}

		}
	}

	function create ($projectid = '', $hexid = '')
	{
		return $this->_edit_issue ($projectid, $hexid, 'create');
	}

	function update ($projectid = '', $hexid = '')
	{
		return $this->_edit_issue ($projectid, $hexid, 'update');
	}

	function delete ($projectid = '', $hexid = '')
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('IssueModel', 'issues');

		$login = $this->login->getUser ();
		if ($login['id'] == '') redirect ('main');
		$data['login'] = $login;

		$id = $this->converter->HexToAscii ($hexid);

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
		else if (!$login['sysadmin?'] && 
		         $this->projects->projectHasMember($project->id, $login['id']) === FALSE)
		{
			$data['project'] = $project;
			$data['message'] = "NO PERMISSION - $projectid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$data['message'] = '';
			$data['project'] = $project;

			$this->form_validation->set_rules ('issue_confirm', 'confirm', 'alpha');
			$this->form_validation->set_error_delimiters('<span class="form_field_error">','</span>');

			if($this->input->post('issue'))
			{
				$issue->projectid = $this->input->post('issue_projectid');
				$issue->id = $this->input->post('issue_id');
				$data['issue_confirm'] = $this->input->post('issue_confirm');

				if ($this->form_validation->run())
				{
					if ($data['issue_confirm'] == 'yes')
					{
						$result = $this->issues->delete ($login['id'], $issue);
						if ($result === FALSE)
						{
							$data['message'] = 'DATABASE ERROR';
							$data['issue'] = $issue;
							$this->load->view ($this->VIEW_DELETE, $data);
						}
						else
						{
							redirect ("issue/home/{$project->id}");
						}
					}
					else 
					{
						redirect ("issue/show/{$project->id}/{$hexid}");
					}
				}
				else
				{
					$data['message'] = "Your input is not complete, Bro.";
					$data['issue'] = $issue;
					$this->load->view ($this->VIEW_DELETE, $data);
				}
			}
			else
			{
				$issue = $this->issues->get ($login['id'], $project, $id);
				if ($issue === FALSE)
				{
					$data['message'] = 'DATABASE ERROR';
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else if ($issue === NULL)
				{
					$data['message'] = 
						$this->lang->line('MSG_NO_SUCH_ISSUE') . 
						" - {$id}";
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else
				{
					$data['issue_confirm'] = 'no';
					$data['issue'] = $issue;
					$this->load->view ($this->VIEW_DELETE, $data);
				}
			}

		}
	}
}
