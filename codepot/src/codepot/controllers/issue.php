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

	function _show_issue ($projectid, $name, $create)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('IssueModel', 'issues');

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ('main/signin');
		$data['login'] = $login;

		if ($name == '')
		{
			$data['message'] = 'INVALID PARAMETERS';
			$this->load->view ($this->VIEW_ERROR, $data);
			return;
		}

		$name = $this->converter->HexToAscii ($name);

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
			$issue = $this->issues->get ($login['id'], $project, $name);
			if ($issue === FALSE)
			{
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else if ($issue === NULL)
			{
				if ($create)
				{
					redirect ("issue/create/{$projectid}/". 
						$this->converter->AsciiToHex($name));
				}
				else
				{
					$data['message'] = 
						$this->lang->line('MSG_NO_SUCH_ISSUE') . 
						" - {$name}";
					$this->load->view ($this->VIEW_ERROR, $data);
				}
			}
			else
			{
				$data['project'] = $project;
				$data['issue'] = $issue;
				$this->load->view ($this->VIEW_SHOW, $data);
			}
		}
	}

	function show ($projectid = '' , $name = '')
	{
		$this->_show_issue ($projectid, $name, TRUE);
	}

	function show_r ($projectid = '' , $name = '')
	{
		$this->_show_issue ($projectid, $name, FALSE);
	}

	function _edit_issue ($projectid, $name, $mode)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('IssueModel', 'issues');

		$login = $this->login->getUser ();
		if ($login['id'] == '') redirect ('main');
		$data['login'] = $login;

		$name = $this->converter->HexToAscii ($name);

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
			$data['message'] = "NO PERMISSION - $projectid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->form_validation->set_rules (
				'issue_projectid', 'project ID', 'required|alpha_dash|max_length[32]');
			$this->form_validation->set_rules (
				'issue_name', 'name', 'required|max_length[255]');
			$this->form_validation->set_rules (
				'issue_text', 'text', 'required');
			$this->form_validation->set_error_delimiters (
				'<span class="form_field_error">','</span>');

			$data['mode'] = $mode;
			$data['message'] = '';
			$data['project'] = $project;

			if ($this->input->post('issue'))
			{
				$issue->projectid = $this->input->post('issue_projectid');
				$issue->name = $this->input->post('issue_name');
				$issue->text = $this->input->post('issue_text');

				if ($this->form_validation->run())
				{
					$result = ($mode == 'update')?
						$this->issues->update ($login['id'], $issue):
						$this->issues->create ($login['id'], $issue);
					if ($result === FALSE)
					{
						$data['message'] = 'DATABASE ERROR';
						$data['issue'] = $issue;
						$this->load->view ($this->VIEW_EDIT, $data);	
					}
					else
					{
						redirect ('issue/show/' . $project->id . '/' . 
							$this->converter->AsciiToHex($issue->name));
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
					$issue = $this->issues->get ($login['id'], $project, $name);
					if ($issue === FALSE)
					{
						$data['message'] = 'DATABASE ERROR';
						$this->load->view ($this->VIEW_ERROR, $data);
					}
					else if ($issue == NULL)
					{
						$data['message'] = 
							$this->lang->line('MSG_NO_SUCH_ISSUE') . 
							" - {$name}";
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
					$issue->name = $name;
					$issue->text = '';

					$data['issue'] = $issue;
					$this->load->view ($this->VIEW_EDIT, $data);	
				}
			}

		}
	}

	function create ($projectid = '', $name = '')
	{
		return $this->_edit_issue ($projectid, $name, 'create');
	}

	function update ($projectid = '', $name = '')
	{
		return $this->_edit_issue ($projectid, $name, 'update');
	}

	function delete ($projectid = '', $name = '')
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('IssueModel', 'issues');

		$login = $this->login->getUser ();
		if ($login['id'] == '') redirect ('main');
		$data['login'] = $login;

		$name = $this->converter->HexToAscii ($name);

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
				$issue->name = $this->input->post('issue_name');
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
						redirect ("issue/show/{$project->id}/" . 
							$this->converter->AsciiToHex($issue->name));
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
				$issue = $this->issues->get ($login['id'], $project, $name);
				if ($issue === FALSE)
				{
					$data['message'] = 'DATABASE ERROR';
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else if ($issue === NULL)
				{
					$data['message'] = 
						$this->lang->line('MSG_NO_SUCH_ISSUE') . 
						" - {$name}";
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
