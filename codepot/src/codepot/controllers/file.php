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

			$file = $this->files->fetch_file ($login['id'], $project, $name);
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

	function _edit_file ($projectid, $name, $mode)
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
				'file_projectid', 'project ID', 'required|alpha_dash|max_length[32]');
			$this->form_validation->set_rules (
				'file_tag', 'tag', 'required|max_length[50]');
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
							redirect ("file/show/{$project->id}/" .
								$this->converter->AsciiToHex($file->name));
						}
					}
					else
					{
						$data['message'] = 'NOT SUPPORTED ANYMORE';
						$data['file'] = $file;
						$this->load->view ($this->VIEW_EDIT, $data);
						/*
						$fname = $_FILES['file_name']['name'];

						if (strpos ($fname, ':') !== FALSE)
						{
							$data['message'] = $this->lang->line ('FILE_MSG_NAME_NO_COLON');
							$data['file'] = $file;
							$this->load->view ($this->VIEW_EDIT, $data);
							return;
						}

						$ext = substr ($fname, strrpos ($fname, '.') + 1);

						// delete all \" instances ... 
						$_FILES['file_name']['type'] = 
							str_replace('\"', '', $_FILES['file_name']['type']);
						// delete all \\ instances ...  
						$_FILES['file_name']['type'] = 
							str_replace('\\', '', $_FILES['file_name']['type']);

						//$config['allowed_types'] = $ext;
						$config['allowed_types'] = '*';
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

							$md5sum = @md5_file ($upload['full_path']);
							if ($md5sum === FALSE)
							{
								@unlink ($upload['full_path']);
								$data['message'] = "CANNOT GET MD5SUM - {$file->name}";
								$data['file'] = $file;
								$this->load->view ($this->VIEW_EDIT, $data);
							}
							else
							{
								$file->md5sum = $md5sum;

								if ($this->files->create ($login['id'], $file) === FALSE)
								{
									@unlink (CODEPOT_FILE_DIR . "/{$file->encname}");
									$data['message'] = 'DATABASE ERROR';
									$data['file'] = $file;
									$this->load->view ($this->VIEW_EDIT, $data);
								}
								else 
								{
									redirect ("file/show/{$project->id}/" .
										$this->converter->AsciiToHex($file->name));
								}
							}
						} */
					}
				}
				else
				{
					if ($mode == 'update') $file->name = $name;

					$data['message'] = $this->lang->line('MSG_FORM_INPUT_INCOMPLETE');
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

	/*
	function create ($projectid = '', $name = '')
	{
		return $this->_edit_file ($projectid, $name, "create");
	}*/

	function update ($projectid = '', $name = '')
	{
		return $this->_edit_file ($projectid, $name, "update");
	}

	function delete ($projectid = '', $name = '')
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
					$data['message'] = $this->lang->line('MSG_FORM_INPUT_INCOMPLETE');
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
					$data['message'] = sprintf 
						($this->lang->line('FILE_MSG_NO_SUCH_FILE'), $name);
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

	function xhr_import ($projectid = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('FileModel', 'files');
		$this->load->library ('upload');

		$login = $this->login->getUser ();
		$revision_saved = -1;

		if ($login['id'] == '')
		{
			$status = 'signin';
		}
		else
		{
			$project = $this->projects->get ($projectid);
			if ($project === FALSE)
			{
				$status = "dberr - failed to get the project {$projectid}";
			}
			else if ($project === NULL)
			{
				$status = "noent - no such project {$projectid}";
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
				else if ($post_new_description === FALSE || ($post_new_description = $post_new_description) == '')
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

							if (strpos($_FILES[$fid]['name'], ':') !== FALSE)
							{
								/* for wiki */
								$status = "error - colon not allowed - {$_FILES[$fid]['name']}";
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
						else if ($this->files->import ($login['id'], $projectid, $post_new_tag, $post_new_name, $post_new_description, $import_files, $this->upload) === FALSE)
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
	}

}

?>
