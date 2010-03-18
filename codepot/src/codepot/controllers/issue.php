<?php

class Issue extends Controller
{
	var $VIEW_ERROR = 'error';
	var $VIEW_HOME = 'issue_home';
	var $VIEW_SHOW = 'issue_show';
	var $VIEW_EDIT = 'issue_edit';
	var $VIEW_DELETE = 'issue_delete';

	var $TYPE_DEFECT       = 'defect';
	var $TYPE_REQUEST      = 'request';
	var $TYPE_OTHER        = 'other';

	var $STATUS_NEW        = 'new';
	var $STATUS_ACCEPTED   = 'accepted';
	var $STATUS_REJECTED   = 'rejected';
	var $STATUS_FIXED      = 'fixed';
	var $STATUS_WONTFIX    = 'wontfix';
	var $STATUS_DUPLICATE  = 'duplicate';
	var $STATUS_OTHER      = 'other';

	var $PRIORITY_CRITICAL = 'critical';
	var $PRIORITY_HIGH     = 'high';
	var $PRIORITY_MEDIUM   = 'medium';
	var $PRIORITY_LOW      = 'low';
	var $PRIORITY_OTHER    = 'other';

	function Issue ()
	{
		parent::Controller ();

		$this->load->helper ('url');
		$this->load->helper ('form');
		$this->load->library ('Converter', 'converter');
		$this->load->model (CODEPOT_LOGIN_MODEL, 'login');

		$this->load->library ('Language', 'lang');
		$this->lang->load ('common', CODEPOT_LANG);
		$this->lang->load ('issue', CODEPOT_LANG);
	}

	function home ($projectid = '', $offset = 0)
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
				$filter->summary = $this->input->post('filter_summary');
				$filter->owner = $this->input->post('filter_owner');
				$data['filter'] = $filter;
			}
			else
			{
				$filter->summary = '';
				$filter->owner = '';
				$data['filter'] = $filter;
			}


			$this->load->library ('pagination');

			$num_entries = $this->issues->getNumEntries ($login['id'], $project);
			if ($num_entries === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
				return;
			}

			$pagecfg['base_url'] = site_url() . "/issue/home/{$projectid}/";
			$pagecfg['total_rows'] = $num_entries;
			$pagecfg['per_page'] = CODEPOT_MAX_ISSUES_PER_PAGE;
			$pagecfg['uri_segment'] = 4;
			$pagecfg['first_link'] = $this->lang->line('First');
			$pagecfg['last_link'] = $this->lang->line('Last');

			//$issues = $this->issues->getAll ($login['id'], $project);
			$issues = $this->issues->getEntries ($login['id'], $offset, $pagecfg['per_page'], $project);
			if ($issues === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$this->pagination->initialize ($pagecfg);
				$data['page_links'] = $this->pagination->create_links ();
				$data['issue_type_array'] = $this->_get_type_array();
				$data['issue_status_array'] = $this->_get_status_array();
				$data['issue_priority_array'] = $this->_get_priority_array();
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
			$change_post = $this->input->post('issue_change');
			if ($change_post == 'change')
			{
				$change->type = $this->input->post('issue_change_type');
				$change->status = $this->input->post('issue_change_status');
				$change->owner = $this->input->post('issue_change_owner');
				$change->priority = $this->input->post('issue_change_priority');
				$change->comment = $this->input->post('issue_change_comment');

				if (!$login['sysadmin?'] && 
				    $this->projects->projectHasMember($project->id, $login['id']) === FALSE)
				{
					$data['project'] = $project;
					$data['message'] = "NO PERMISSION - $projectid";
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else if ($this->issues->change ($login['id'], $project, $id, $change) === FALSE)
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
			else if ($change_post == 'undo')
			{
				if (!$login['sysadmin?'] && 
				    $this->projects->projectHasMember($project->id, $login['id']) === FALSE)
				{
					$data['project'] = $project;
					$data['message'] = "NO PERMISSION - $projectid";
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else if ($this->issues->undo_last_change ($login['id'], $project, $id) === FALSE)
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
					$this->lang->line('MSG_NO_SUCH_ISSUE').
					" - {$id}";
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$data['issue_type_array'] = $this->_get_type_array();
				$data['issue_status_array'] = $this->_get_status_array();
				$data['issue_priority_array'] = $this->_get_priority_array();
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
				'issue_description', 'description', 'required');
			$this->form_validation->set_rules (
				'issue_type', 'type', 'required');
			$this->form_validation->set_rules (
				'issue_type', 'status', 'required');
			$this->form_validation->set_rules (
				'issue_type', 'priority', 'required');
			$this->form_validation->set_error_delimiters (
				'<span class="form_field_error">','</span>');

			$data['mode'] = $mode;
			$data['message'] = '';
			$data['project'] = $project;
			$data['issue_type_array'] = $this->_get_type_array();
			$data['issue_status_array'] = $this->_get_status_array();
			$data['issue_priority_array'] = $this->_get_priority_array();

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
						$this->issues->update_partial ($login['id'], $issue):
						$this->issues->create ($login['id'], $issue);
					if ($id === FALSE)
					{
						$data['message'] = 'DATABASE ERROR';
						$data['issue'] = $issue;
						$this->load->view ($this->VIEW_EDIT, $data);	
					}
					else
					{
						redirect ("issue/show/{$project->id}/" . 
							$this->converter->AsciiToHex((string)$id));
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
					$issue->description = '';
					$issue->type = $this->TYPE_DEFECT;
					$issue->status = $this->STATUS_NEW;
					$issue->priority = $this->PRIORITY_OTHER;
					$issue->owner = '';

					$data['issue'] = $issue;
					$this->load->view ($this->VIEW_EDIT, $data);	
				}
			}

		}
	}

	function create ($projectid = '')
	{
		return $this->_edit_issue ($projectid, '', 'create');
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

	function _get_type_array ()
	{
		return array (
			$this->TYPE_DEFECT  => 
				$this->lang->line('ISSUE_TYPE_DEFECT'),
			$this->TYPE_REQUEST => 
				$this->lang->line('ISSUE_TYPE_REQUEST'),
			$this->TYPE_OTHER   => 
				$this->lang->line('ISSUE_TYPE_OTHER')
		);
	}

	function _get_status_array ()
	{
		return array (
			$this->STATUS_NEW       => 
				$this->lang->line('ISSUE_STATUS_NEW'),
			$this->STATUS_ACCEPTED  => 
				$this->lang->line('ISSUE_STATUS_ACCEPTED'),
			$this->STATUS_REJECTED  => 
				$this->lang->line('ISSUE_STATUS_REJECTED'),
			$this->STATUS_FIXED     => 
				$this->lang->line('ISSUE_STATUS_FIXED'),
			$this->STATUS_WONTFIX   => 
				$this->lang->line('ISSUE_STATUS_WONTFIX'),
			$this->STATUS_DUPLICATE => 
				$this->lang->line('ISSUE_STATUS_DUPLICATE'),
			$this->STATUS_OTHER     => 
				$this->lang->line('ISSUE_STATUS_OTHER')
		);
	}

	function _get_priority_array ()
	{
		return array (
			$this->PRIORITY_CRITICAL => 
				$this->lang->line('ISSUE_PRIORITY_CRITICAL'),
			$this->PRIORITY_HIGH     => 
				$this->lang->line('ISSUE_PRIORITY_HIGH'),
			$this->PRIORITY_MEDIUM   => 
				$this->lang->line('ISSUE_PRIORITY_MEDIUM'),
			$this->PRIORITY_LOW      => 
				$this->lang->line('ISSUE_PRIORITY_LOW'),
			$this->PRIORITY_OTHER    => 
				$this->lang->line('ISSUE_PRIORITY_OTHER')
		);

	}
}
