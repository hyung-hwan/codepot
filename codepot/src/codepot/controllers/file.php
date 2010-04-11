<?php

class File extends Controller
{
	var $VIEW_ERROR = 'error';
	var $VIEW_HOME = 'file_home';
	var $VIEW_SHOW = 'file_show';
	var $VIEW_EDIT = 'file_edit';
	var $VIEW_DELETE = 'file_delete';

	function File ()
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
		$this->load->model ('FileModel', 'files');
	
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
			$files = $this->files->getAll ($login['id'], $project);
			if ($files === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$data['project'] = $project;
				$data['files'] = $files;
				$this->load->view ($this->VIEW_HOME, $data);
			}
		}
	}

	function show ($projectid, $name)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('FileModel', 'files');

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
			$file = $this->files->get ($login['id'], $project, $name);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else if ($file === NULL)
			{
				$data['project'] = $project;
				$data['message'] =
					$this->lang->line('MSG_NO_SUCH_FILE').
					" - {$name}";
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$data['project'] = $project;
				$data['file'] = $file;
				$this->load->view ($this->VIEW_SHOW, $data);
			}
		}
	}

	function get ($projectid, $name)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('FileModel', 'files');

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
			$file = $this->files->get ($login['id'], $project, $name);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else if ($file === NULL)
			{
				redirect ('file/create/'. $projectid . '/' . 
					$this->converter->AsciiToHex($name));
			}
			else
			{
				$path = CODEPOT_FILE_DIR . '/' . $file->encname;
				$mtime = @filemtime ($path);
				if ($mtime === FALSE) $mtime = time();
				header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mtime) . " GMT");
				//header("Expires: 0");
				header("Content-Type: application/octet-stream");
				header("Content-Disposition: attachment; filename={$name}");
				header("Content-Transfer-Encoding: binary");
				flush ();
				$x = @readfile($path);
				if ($x === FALSE)
				{
					$data['project'] = $project;
					$data['message'] = "CANNOT GET FILE - {$file->name}";
					$this->load->view ($this->VIEW_ERROR, $data);
				}

				/*
				$this->load->helper('download');	
				$path = CODEPOT_FILE_DIR . '/' . $file->encname;
				$data = @file_get_contents ($path);
				if ($data === FALSE)
				{
					$data['project'] = $project;
					$data['message'] = "CANNOT GET FILE - {$file->name}";
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else
				{
					force_download ($name, $data); 	
				}
				*/
			}
		}
	}

	function _edit_file ($projectid, $name, $mode)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('FileModel', 'files');

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
			$data['project'] = $project;
			$data['message'] = "NO PERMISSION - $projectid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->form_validation->set_rules (
				'file_projectid', 'project ID', 'required|alpha_dash|max_length[32]');
			$this->form_validation->set_rules (
				'file_tag', 'tag', 'required|max_length[50]');
			$this->form_validation->set_rules (
				'file_summary', 'summary', 'required|max_length[255]');
			$this->form_validation->set_rules (
				'file_description', 'description', 'required');
			$this->form_validation->set_error_delimiters (
				'<span class="form_field_error">','</span>');

			$data['mode'] = $mode;
			$data['message'] = '';
			$data['project'] = $project;

			if ($this->input->post('file'))
			{
				$file->projectid = $this->input->post('file_projectid');
				$file->name = '';
				$file->encname = '';
				$file->tag = $this->input->post('file_tag');
				$file->summary = $this->input->post('file_summary');
				$file->description = $this->input->post('file_description');

				if ($this->form_validation->run())
				{
					if ($mode == 'update')
					{
						$file->name = $this->input->post('file_name');

						if ($this->files->update ($login['id'], $file) === FALSE)
						{
							$data['message'] = 'DATABASE ERROR';
							$data['file'] = $file;
							$this->load->view ($this->VIEW_EDIT, $data);
						}
						else 
						{
							redirect ('file/show/' . $project->id . '/' .
								$this->converter->AsciiToHex($file->name));
						}
					}
					else
					{
						$fname = $_FILES['file_name']['name'];
						$ext = substr ($fname, strrpos ($fname, '.') + 1);

						// delete all \" instances ... 
						$_FILES['file_name']['type'] = 
							str_replace('\"', '', $_FILES['file_name']['type']);
						// delete all \\ instances ...  
						$_FILES['file_name']['type'] = 
							str_replace('\\', '', $_FILES['file_name']['type']);

						$config['allowed_types'] = $ext;
						$config['upload_path'] = CODEPOT_FILE_DIR;
						$config['max_size'] = CODEPOT_MAX_UPLOAD_SIZE;
						$config['encrypt_name'] = TRUE;

						$this->load->library ('upload');
						$this->upload->initialize ($config);
					
						if (!$this->upload->do_upload ('file_name'))
						{
							$data['file'] = $file;
							$data['message'] = $this->upload->display_errors('', '');
							$this->load->view ($this->VIEW_EDIT, $data);
						}
						else 
						{
							$upload = $this->upload->data ();

							$file->name = $_FILES['file_name']['name'];
							$file->encname = $upload['file_name'];

							$md5sum = md5_file ($upload['full_path']);
							if ($md5sum === FALSE)
							{
								unlink (CODEPOT_FILE_DIR . "/{$file->encname}");

								$data['message'] = "CANNOT GET MD5SUM OF FILE - {$file->name}";
								$data['file'] = $file;
								$this->load->view ($this->VIEW_EDIT, $data);
							}
							else
							{
								$file->md5sum = $md5sum;

								if ($this->files->create ($login['id'], $file) === FALSE)
								{
									unlink (CODEPOT_FILE_DIR . "/{$file->encname}");
									$data['message'] = 'DATABASE ERROR';
									$data['file'] = $file;
									$this->load->view ($this->VIEW_EDIT, $data);
								}
								else 
								{
									redirect ('file/show/' . $project->id . '/' .
										$this->converter->AsciiToHex($file->name));
								}
							}
						}
					}
				}
				else
				{
					$data['message'] = "Your input is not complete, Bro";
					$data['file'] = $file;
					$this->load->view ($this->VIEW_EDIT, $data);	
				}
			}
			else
			{
				if ($mode == 'update')
				{
					$file = $this->files->get ($login['id'], $project, $name);
					if ($file === FALSE)
					{
						$data['message'] = 'DATABASE ERROR';
						$this->load->view ($this->VIEW_ERROR, $data);
					}
					else if ($file == NULL)
					{
						$data['message'] = "NO SUCH FILE - $name";
						$this->load->view ($this->VIEW_ERROR, $data);
					}
					else
					{
						$data['file'] = $file;
						$this->load->view ($this->VIEW_EDIT, $data);	
					}
				}
				else
				{
					$file->projectid = $projectid;
					$file->name = $name;
					$file->encname = '';
					$file->tag = '';
					$file->summary = '';
					$file->description = '';

					$data['file'] = $file;
					$this->load->view ($this->VIEW_EDIT, $data);	
				}
			}

		}
	}

	function create ($projectid, $name = "")
	{
		return $this->_edit_file ($projectid, $name, "create");
	}

	function update ($projectid, $name)
	{
		return $this->_edit_file ($projectid, $name, "update");
	}

	function delete ($projectid, $name)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('FileModel', 'files');

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
			$data['project'] = $project;
			$data['message'] = "NO PERMISSION - $projectid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$data['message'] = '';
			$data['project'] = $project;

			$this->form_validation->set_rules ('file_confirm', 'confirm', 'alpha');
			$this->form_validation->set_error_delimiters('<span class="form_field_error">','</span>');

			if($this->input->post('file'))
			{
				$file->projectid = $this->input->post('file_projectid');
				$file->name = $this->input->post('file_name');
				$data['file_confirm'] = $this->input->post('file_confirm');

				if ($this->form_validation->run())
				{
					if ($data['file_confirm'] == 'yes')
					{
						$result = $this->files->delete ($login['id'], $file);
						if ($result === FALSE)
						{
							$data['message'] = 'DATABASE ERROR';
							$data['file'] = $file;
							$this->load->view ($this->VIEW_DELETE, $data);
						}
						else
						{
							redirect ("file/home/{$project->id}");
						}
					}
					else 
					{
						redirect ("file/show/{$project->id}/" . 
							$this->converter->AsciiToHex($file->name));
					}
				}
				else
				{
					$data['message'] = "Your input is not complete, Bro.";
					$data['file'] = $file;
					$this->load->view ($this->VIEW_DELETE, $data);
				}
			}
			else
			{
				$file = $this->files->get ($login['id'], $project, $name);
				if ($file === FALSE)
				{
					$data['message'] = 'DATABASE ERROR';
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else if ($file === NULL)
				{
					$data['message'] = "NO SUCH FILE - $name";
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else
				{
					$data['file_confirm'] = 'no';
					$data['file'] = $file;
					$this->load->view ($this->VIEW_DELETE, $data);
				}
			}

		}
	}
}

?>
