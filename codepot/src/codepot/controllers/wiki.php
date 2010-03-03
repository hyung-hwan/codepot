<?php

class Wiki extends Controller
{
	var $VIEW_ERROR = 'error';
	var $VIEW_HOME = 'wiki_home';
	var $VIEW_SHOW = 'wiki_show';
	var $VIEW_EDIT = 'wiki_edit';
	var $VIEW_DELETE = 'wiki_delete';

	function Wiki ()
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
		$this->load->model ('WikiModel', 'wikis');
	
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
			$wikis = $this->wikis->getAll ($login['id'], $project);
			if ($wikis === FALSE)
			{
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$data['project'] = $project;
				$data['wikis'] = $wikis;
				$this->load->view ($this->VIEW_HOME, $data);
			}
		}
	}

	function show ($projectid, $name)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('WikiModel', 'wikis');

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ('main/signin');
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
		else
		{
			if ($name == '__PROJECT_HOME__')
			{
				redirect ("project/home/{$projectid}");
			}
			else if ($name == '__WIKI_HOME__')
			{
				redirect ("wiki/home/{$projectid}");
			}
			else
			{
				$ex = explode (':', $name);
				if (count($ex) == 2) 
				{
					if ($ex[0] == '__PROJECT_HOME__')
					{
						redirect ("project/home/{$ex[1]}");
					}
					else if ($ex[0] == '__WIKI_HOME__')
					{
						redirect ("wiki/home/{$ex[1]}");
					}
				}

				$wiki = $this->wikis->get ($login['id'], $project, $name);
				if ($wiki === FALSE)
				{
					$data['message'] = 'DATABASE ERROR';
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else if ($wiki === NULL)
				{
					redirect ("wiki/create/{$projectid}/" . 
						$this->converter->AsciiToHex($name));
				}
				else
				{
					$data['project'] = $project;
					$data['wiki'] = $wiki;
					$this->load->view ($this->VIEW_SHOW, $data);
				}
			}
		}
	}

	function _edit_wiki ($projectid, $name, $mode)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('WikiModel', 'wikis');

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
		else if ($name == '__PROJECT_HOME__' || 
		         $name == '__WIKI_HOME__')
		{
			$data['message'] = "RESERVED WIKI PAGE - $name ";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->form_validation->set_rules (
				'wiki_projectid', 'project ID', 'required|alpha_dash|max_length[32]');
			$this->form_validation->set_rules (
				'wiki_name', 'name', 'required|max_length[255]');
			$this->form_validation->set_rules (
				'wiki_text', 'text', 'required');
			$this->form_validation->set_error_delimiters (
				'<span class="form_field_error">','</span>');

			$data['mode'] = $mode;
			$data['message'] = '';
			$data['project'] = $project;

			if ($this->input->post('wiki'))
			{
				$wiki->projectid = $this->input->post('wiki_projectid');
				$wiki->name = $this->input->post('wiki_name');
				$wiki->text = $this->input->post('wiki_text');

				if ($this->form_validation->run())
				{
					$result = ($mode == 'update')?
						$this->wikis->update ($login['id'], $wiki):
						$this->wikis->create ($login['id'], $wiki);
					if ($result === FALSE)
					{
						$data['message'] = 'DATABASE ERROR';
						$data['wiki'] = $wiki;
						$this->load->view ($this->VIEW_EDIT, $data);	
					}
					else
					{
						redirect ('wiki/show/' . $project->id . '/' . 
							$this->converter->AsciiToHex($wiki->name));
					}
				}
				else
				{
					$data['message'] = "Your input is not complete, Bro";
					$data['wiki'] = $wiki;
					$this->load->view ($this->VIEW_EDIT, $data);	
				}
			}
			else
			{
				if ($mode == 'update')
				{
					$wiki = $this->wikis->get ($login['id'], $project, $name);
					if ($wiki === FALSE)
					{
						$data['message'] = 'DATABASE ERROR';
						$this->load->view ($this->VIEW_ERROR, $data);
					}
					else if ($wiki == NULL)
					{
						$data['message'] = "NO SUCH WIKI PAGE - $name";
						$this->load->view ($this->VIEW_ERROR, $data);
					}
					else
					{
						$data['wiki'] = $wiki;
						$this->load->view ($this->VIEW_EDIT, $data);	
					}
				}
				else
				{
					$wiki->projectid = $projectid;
					$wiki->name = $name;
					$wiki->text = '';

					$data['wiki'] = $wiki;
					$this->load->view ($this->VIEW_EDIT, $data);	
				}
			}

		}
	}

	function create ($projectid, $name = "")
	{
		return $this->_edit_wiki ($projectid, $name, "create");
	}

	function update ($projectid, $name)
	{
		return $this->_edit_wiki ($projectid, $name, "update");
	}

	function delete ($projectid, $name)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('WikiModel', 'wikis');

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
		else if ($name == '__PROJECT_HOME__' || 
		         $name == '__WIKI_HOME__')
		{
			$data['message'] = "RESERVED WIKI PAGE - $name ";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$data['message'] = "";
			$data['project'] = $project;

			$this->form_validation->set_rules ('wiki_confirm', 'confirm', 'alpha');
			$this->form_validation->set_error_delimiters('<span class="form_field_error">','</span>');

			if($this->input->post('wiki'))
			{
				$wiki->projectid = $this->input->post('wiki_projectid');
				$wiki->name = $this->input->post('wiki_name');
				$data['wiki_confirm'] = $this->input->post('wiki_confirm');

				if ($this->form_validation->run())
				{
					if ($data['wiki_confirm'] == 'yes')
					{
						$result = $this->wikis->delete ($login['id'], $wiki);
						if ($result === FALSE)
						{
							$data['message'] = 'DATABASE ERROR';
							$data['wiki'] = $wiki;
							$this->load->view ($this->VIEW_DELETE, $data);
						}
						else
						{
							redirect ('wiki/home/' . $project->id);
						}
					}
					else 
					{
						redirect ('wiki/show/' . $project->id . '/' . 
							$this->converter->AsciiToHex($wiki->name));
					}
				}
				else
				{
					$data['message'] = "Your input is not complete, Bro.";
					$data['wiki'] = $wiki;
					$this->load->view ($this->VIEW_DELETE, $data);
				}
			}
			else
			{
				$wiki = $this->wikis->get ($login['id'], $project, $name);
				if ($wiki === FALSE)
				{
					$data['message'] = 'DATABASE ERROR';
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else if ($wiki === NULL)
				{
					$data['message'] = "NO SUCH WIKI PAGE - $name";
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else
				{
					$data['wiki_confirm'] = 'no';
					$data['wiki'] = $wiki;
					$this->load->view ($this->VIEW_DELETE, $data);
				}
			}

		}
	}
}
