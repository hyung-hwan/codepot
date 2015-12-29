<?php

class File extends Controller
{
	var $VIEW_ERROR = 'error';
	var $VIEW_HOME = 'file_home';
	var $VIEW_SHOW = 'file_show';
	var $VIEW_EDIT = 'file_edit';

	function File ()
	{
		parent::Controller ();
		$this->load->helper ('url');
		$this->load->helper ('form');
		$this->load->library ('Converter', 'converter');
		$this->load->model (CODEPOT_LOGIN_MODEL, 'login');

		$this->load->library ('Language', 'lang');
		$this->lang->load ('common', CODEPOT_LANG);
		$this->lang->load ('file', CODEPOT_LANG);
	}

	function home ($projectid = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('FileModel', 'files');
	
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

	function show ($projectid = '', $name = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('FileModel', 'files');

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));
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
			if ($project->public !== 'Y' && $login['id'] == '')
			{
				// non-public projects require sign-in.
				redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));
			}

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
				$data['message'] = sprintf (
					$this->lang->line('FILE_MSG_NO_SUCH_FILE'), $name);
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

	function get ($projectid = '', $name = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('FileModel', 'files');

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));
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
			if ($project->public !== 'Y' && $login['id'] == '')
			{
				// non-public projects require sign-in.
				redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));
			}

			$file = $this->files->fetchFile ($login['id'], $project, $name);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else if ($file === NULL)
			{
				/*redirect ("file/create/{$projectid}/" . 
					$this->converter->AsciiToHex($name));*/
				$data['project'] = $project;
				$data['message'] = "CANNOT FIND FILE - {$name}";
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$path = CODEPOT_FILE_DIR . '/' . $file->encname;

				$stat = @stat($path);
				if ($stat === FALSE)
				{
					$data['project'] = $project;
					$data['message'] = "CANNOT GET FILE - {$file->name}";
					$this->load->view ($this->VIEW_ERROR, $data);
					return;
				}

				$etag = sprintf ('%x-%x-%x-%x', $stat['dev'], $stat['ino'], $stat['size'], $stat['mtime']);
				$lastmod = gmdate ('D, d M Y H:i:s', $stat['mtime']);

				header ('Last-Modified: ' . $lastmod . ' GMT');
				header ('Etag: ' . $etag);

				if ((isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) ||
				    (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $stat['mtime']))
				{
					header('Not Modified', true, 304);
					flush ();
					return;
				}

				/*
				header('Content-Type: application/octet-stream');
				header('Content-Length: ' . $stat['size']);
				header('Content-Disposition: attachment; filename=' . $name);
				header('Content-Transfer-Encoding: binary');
				//header('Expires: 0');
				//header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				//header('Pragma: public');
				flush ();

				$x = @readfile($path);
				if ($x === FALSE)
				{
					$data['project'] = $project;
					$data['message'] = "CANNOT GET FILE - {$file->name}";
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				*/
				codepot_readfile ($path, $name);

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

	function update ($projectid = '', $name = '')
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('FileModel', 'files');

		$login = $this->login->getUser ();
		if ($login['id'] == '')
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));
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
			$data['message'] = sprintf (
				$this->lang->line('MSG_PROJECT_MEMBERSHIP_REQUIRED'), $projectid);
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->form_validation->set_rules (
				'file_name', 'name', 'required|max_length[255]');
			$this->form_validation->set_rules (
				'file_tag', 'tag', 'required|max_length[50]');
			$this->form_validation->set_rules (
				'file_description', 'description', 'required');
			$this->form_validation->set_error_delimiters (
				'<span class="form_field_error">','</span>');

			$data['message'] = '';
			$data['project'] = $project;

			if ($this->input->post('file'))
			{
				$file->name = $this->input->post('file_name');
				$file->tag = $this->input->post('file_tag');
				$file->description = $this->input->post('file_description');

				if ($this->form_validation->run())
				{
					if ($this->files->update ($login['id'], $projectid, $name, $file) === FALSE)
					{
						$data['message'] = 'DATABASE ERROR';
						$data['file'] = $file;
						$this->load->view ($this->VIEW_EDIT, $data);
					}
					else 
					{
						redirect ("file/show/{$project->id}/" . $this->converter->AsciiToHex($file->name));
					}
				}
				else
				{
					$data['message'] = $this->lang->line('MSG_FORM_INPUT_INCOMPLETE');
					$data['file'] = $file;
					$this->load->view ($this->VIEW_EDIT, $data);
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
				else if ($file == NULL)
				{
					$data['message'] = sprintf 
						($this->lang->line('FILE_MSG_NO_SUCH_FILE'), $name);
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else
				{
					$data['file'] = $file;
					$this->load->view ($this->VIEW_EDIT, $data);
				}
			}
		}
	}

	function xhr_import ($projectid = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('FileModel', 'files');
		$this->load->library ('upload');

		$login = $this->login->getUser ();
		$revision_saved = -1;

		if ($login['id'] == '')
		{
			$status = 'error - anonymous user';
		}
		else
		{
			$project = $this->projects->get ($projectid);
			if ($project === FALSE)
			{
				$status = "error - failed to get the project {$projectid}";
			}
			else if ($project === NULL)
			{
				$status = "error - no such project {$projectid}";
			}
			else if (!$login['sysadmin?'] && 
			         $this->projects->projectHasMember($projectid, $login['id']) === FALSE)
			{
				$status = "error - not a member {$login['id']}";
			}
			else
			{
				$post_new_tag = $this->input->post('file_new_tag');
				$post_new_name = $this->input->post('file_new_name');
				$post_new_description = $this->input->post('file_new_description');
				$post_new_file_count = $this->input->post('file_new_file_count');

				if ($post_new_tag === FALSE || ($post_new_tag = trim($post_new_tag)) == '')
				{
					$status = 'error - no tag';
				}
				else if ($post_new_name === FALSE || ($post_new_name = trim($post_new_name)) == '')
				{
					$status = 'error - no name';
				}
				else if ($post_new_description === FALSE || ($post_new_description = trim($post_new_description)) == '')
				{
					$status = 'error - no description';
				}
				else
				{
					if ($post_new_file_count === FALSE || $post_new_file_count <= 0) $post_new_file_count = 0;

					$status = '';
					$import_files = array ();
					for ($i = 0; $i < $post_new_file_count; $i++)
					{
						$fid = "file_new_file_{$i}";
						if (array_key_exists($fid, $_FILES) && $_FILES[$fid]['name'] != '')
						{
							$d = $this->input->post("file_new_file_desc_{$i}");
							if ($d === FALSE || ($d = trim($d)) == '')
							{
								$status = "error - no short description for {$_FILES[$fid]['name']}";
								break;
							}

							if (strpos($_FILES[$fid]['name'], ':') !== FALSE ||
							    strpos($_FILES[$fid]['name'], '/') !== FALSE)
							{
								// prevents these letters for wiki creole 
								$status = "error - colon or slash not allowed - {$_FILES[$fid]['name']}";
								break;
							}

							array_push ($import_files, array ('fid' => $fid, 'name' => $_FILES[$fid]['name'], 'desc' => $d));
						}
					}

					if ($status == '')
					{
						if (count($import_files) <= 0)
						{
							$status = 'error - no files uploaded';
						}
						else if ($this->files->import ($login['id'], $projectid, $post_new_name, $post_new_tag, $post_new_description, $import_files, $this->upload) === FALSE)
						{
							$status = 'error - ' . $this->files->getErrorMessage();
						}
						else
						{
							$status = 'ok';
						}
					}
				}
			}
		}

		print $status;
	}

	function xhr_add_file ($projectid = '', $name = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('FileModel', 'files');
		$this->load->library ('upload');

		$login = $this->login->getUser ();
		$revision_saved = -1;

		if ($login['id'] == '')
		{
			$status = 'error - anonymous user';
		}
		else
		{
			$name = $this->converter->HexToAscii ($name);

			$project = $this->projects->get ($projectid);
			if ($project === FALSE)
			{
				$status = "error - failed to get the project {$projectid}";
			}
			else if ($project === NULL)
			{
				$status = "error - no such project {$projectid}";
			}
			else if (!$login['sysadmin?'] && 
			         $this->projects->projectHasMember($projectid, $login['id']) === FALSE)
			{
				$status = "error - not a member {$login['id']}";
			}
			else
			{
				$post_add_file_count = $this->input->post('file_add_file_count');
				if ($post_add_file_count === FALSE || $post_add_file_count <= 0) $post_add_file_count = 0;

				$status = '';
				$add_files = array ();
				for ($i = 0; $i < $post_add_file_count; $i++)
				{
					$fid = "file_add_file_{$i}";
					if (array_key_exists($fid, $_FILES) && $_FILES[$fid]['name'] != '')
					{
						$d = $this->input->post("file_add_file_desc_{$i}");
						if ($d === FALSE || ($d = trim($d)) == '')
						{
							$status = "error - no short description for {$_FILES[$fid]['name']}";
							break;
						}

						if (strpos($_FILES[$fid]['name'], ':') !== FALSE ||
							strpos($_FILES[$fid]['name'], '/') !== FALSE)
						{
							// prevents these letters for wiki creole
							$status = "error - colon or slash not allowed - {$_FILES[$fid]['name']}";
							break;
						}

						array_push ($add_files, array ('fid' => $fid, 'name' => $_FILES[$fid]['name'], 'desc' => $d));
					}
				}

				if ($status == '')
				{
					if (count($add_files) <= 0)
					{
						$status = 'error - no files uploaded';
					}
					else if ($this->files->addFiles ($login['id'], $projectid, $name, $add_files, $this->upload) === FALSE)
					{
						$status = 'error - ' . $this->files->getErrorMessage();
					}
					else
					{
						$status = 'ok';
					}
				}
			}
		}

		print $status;
	}

	function xhr_edit_file ($projectid = '', $name = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('FileModel', 'files');

		$login = $this->login->getUser ();
		$revision_saved = -1;

		if ($login['id'] == '')
		{
			$status = 'error - anonymous user';
		}
		else
		{
			$name = $this->converter->HexToAscii ($name);

			$project = $this->projects->get ($projectid);
			if ($project === FALSE)
			{
				$status = "error - failed to get the project {$projectid}";
			}
			else if ($project === NULL)
			{
				$status = "error - no such project {$projectid}";
			}
			else if (!$login['sysadmin?'] && 
			         $this->projects->projectHasMember($projectid, $login['id']) === FALSE)
			{
				$status = "error - not a member {$login['id']}";
			}
			else
			{
				$post_edit_file_count = $this->input->post('file_edit_file_count');
				if ($post_edit_file_count === FALSE || $post_edit_file_count <= 0) $post_edit_file_count = 0;

				$status = '';
				$edit_files = array ();
				for ($i = 0; $i < $post_edit_file_count; $i++)
				{
					$n = $this->input->post("file_edit_file_name_{$i}");
					$k = $this->input->post("file_edit_file_kill_{$i}");
					$d = $this->input->post("file_edit_file_desc_{$i}");

					if ($n != '')
					{
						if ($k == 'yes')
						{
							array_push ($edit_files, array ('name' => $n, 'kill' => $k));
						}
						else if ($d !== FALSE)
						{
							if (($d = trim($d)) == '')
							{
								$status = "error - no short description for {$n}";
								break;
							}

							array_push ($edit_files, array ('name' => $n, 'desc' => $d));
						}
					}
				}

				if ($status == '')
				{
					if (count($edit_files) <= 0)
					{
						//$status = 'error - no input avaialble';
						$status = 'ok';
					}
					else if ($this->files->editFiles ($login['id'], $projectid, $name, $edit_files) === FALSE)
					{
						$status = 'error - ' . $this->files->getErrorMessage();
					}
					else
					{
						$status = 'ok';
					}
				}
			}
		}

		print $status;
	}

	function xhr_delete ($projectid = '', $name = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('FileModel', 'files');

		$login = $this->login->getUser ();

		if ($login['id'] == '')
		{
			$status = 'error - anonymous user';
		}
		else
		{
			$name = $this->converter->HexToAscii ($name);

			$project = $this->projects->get ($projectid);
			if ($project === FALSE)
			{
				$status = "error - failed to get the project {$projectid}";
			}
			else if ($project === NULL)
			{
				$status = "error - no such project {$projectid}";
			}
			else if (!$login['sysadmin?'] && 
			         $this->projects->projectHasMember($projectid, $login['id']) === FALSE)
			{
				$status = "error - not a member {$login['id']}";
			}
			else
			{
				$post_delete_confirm = $this->input->post('file_delete_confirm');
				
				if ($post_delete_confirm !== FALSE && $post_delete_confirm == 'Y')
				{
					if ($this->files->delete ($login['id'], $projectid, $name) === FALSE)
					{
						$status = 'error - ' . $this->files->getErrorMessage();
					}
					else
					{
						$status = 'ok';
					}
				}
				else
				{
					$status = 'error - not confirmed';
				}
			}
		}

		print $status;
	}


}

?>
