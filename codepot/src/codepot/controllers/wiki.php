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
		$this->load->library ('WikiHelper', 'wikihelper');
		$this->load->model (CODEPOT_LOGIN_MODEL, 'login');

		$this->load->library ('Language', 'lang');
		$this->lang->load ('common', CODEPOT_LANG);
		$this->lang->load ('wiki', CODEPOT_LANG);
	}

	function home ($projectid = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('WikiModel', 'wikis');
	
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

			$wikis = $this->wikis->getAll ($login['id'], $project);
			if ($wikis === FALSE)
			{
				$data['project'] = $project;
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

	function _show_wiki ($projectid, $name, $create)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('WikiModel', 'wikis');

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));
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
			$link = $this->wikihelper->parseLink (
				$name, $projectid, $this->converter);
			if ($link === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = "INVALID LINK - {$name}";
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else if ($link !== NULL)
			{
				// redirect to  a special link like __WIKI__:projectid:wikiname
				redirect ($link);
			}
			else
			{
				$wiki = $this->wikis->get ($login['id'], $project, $name);
				if ($wiki === FALSE)
				{
					$data['project'] = $project;
					$data['message'] = 'DATABASE ERROR';
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else if ($wiki === NULL)
				{
					if ($create)
					{
						// Redirecting to the 'new' page is determined by the project membership
						// when the wiki page is not found.
						$create = ($login['sysadmin?'] ||
						           $this->projects->projectHasMember($project->id, $login['id']));
					}

					if ($create)
					{
						redirect ("wiki/create/{$projectid}/" . 
							$this->converter->AsciiToHex($name));
					}
					else
					{
						$data['project'] = $project;
						$data['message'] = sprintf (
							$this->lang->line('WIKI_MSG_NO_SUCH_PAGE'), $name);
						$this->load->view ($this->VIEW_ERROR, $data);
					}
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

	function show ($projectid = '' , $name = '')
	{
		$this->_show_wiki ($projectid, $name, TRUE);
	}

	function show_r ($projectid = '' , $name = '')
	{
		$this->_show_wiki ($projectid, $name, FALSE);
	}

	function attachment0 ($projectid = '', $target = '')
	{
		//$target => projectid:wikiname:attachment

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));

		if ($target == '')
		{
			$data['login'] = $login;
			$data['message'] = 'INVALID PARAMETERS';
			$this->load->view ($this->VIEW_ERROR, $data);
			return;
		}

		$target = $this->converter->HexToAscii ($target);
		$part = explode (':', $target);
		if (count($part) == 3)
		{
			if ($part[0] == '') $part[0] = $projectid;	
			$this->_handle_attachment ($login, $part[0], $part[1], $part[2]);
		}
	}

	function attachment ($projectid = '', $wikiname = '', $name = '')
	{
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));

		if ($wikiname == '' || $name == '')
		{
			$data['login'] = $login;
			$data['message'] = 'INVALID PARAMETERS';
			$this->load->view ($this->VIEW_ERROR, $data);
			return;
		}

		$wikiname = $this->converter->HexToAscii ($wikiname);
		$name = $this->converter->HexToAscii ($name);

		$part = explode (':', $name);
		if (count($part) == 3)
		{
			if ($part[0] != '') $projectid = $part[0];
			if ($part[1] != '') $wikiname = $part[1];
			if ($part[2] != '') $name = $part[2];
		}

		$this->_handle_attachment ($login, $projectid, $wikiname, $name);
	}

	function _handle_attachment ($login, $projectid, $wikiname, $name)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('WikiModel', 'wikis');

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
			$att = $this->wikis->getAttachment ($login['id'], $project, $wikiname, $name);
			if ($att === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else if ($att === NULL)
			{
				$data['project'] = $project;
				$data['message'] = sprintf (
					$this->lang->line('WIKI_MSG_NO_SUCH_ATTACHMENT'), $name);
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$path = CODEPOT_ATTACHMENT_DIR . "/{$att->encname}";

				$stat = @stat($path);
				if ($stat === FALSE)
				{
					$data['project'] = $project;
					$data['message'] = sprintf (
						$this->lang->line('WIKI_MSG_FAILED_TO_READ_ATTACHMENT'), $name);
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

				header ('Content-Type: ' . mime_content_type($path));
				header ('Content-Length: ' . $stat['size']);
				header ('Content-Disposition: inline; filename=' . $name);
				flush ();

				$x = @readfile($path);
				if ($x === FALSE)
				{
					$data['project'] = $project;
					$data['message'] = sprintf (
						$this->lang->line('WIKI_MSG_FAILED_TO_READ_ATTACHMENT'), $name);
					$this->load->view ($this->VIEW_ERROR, $data);
				}
			}
		}
	}

	function _edit_wiki ($projectid, $name, $mode)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');
		$this->load->library ('upload');

		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('WikiModel', 'wikis');

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
				$wiki->attachments = array();
				$wiki->delete_attachments = array();

				if ($this->form_validation->run())
				{
					$delatts = $this->input->post('wiki_delete_attachment');
					if (!empty($delatts))
					{
						foreach ($delatts as $att)
						{
							$atpos = strpos ($att, '@');	
							if ($atpos === FALSE) continue;
	
							$attinfo['name'] = $this->converter->HexToAscii(
								substr ($att, 0, $atpos));
							$attinfo['encname'] = $this->converter->HexToAscii(
								substr ($att, $atpos + 1));

							array_push (
								$wiki->delete_attachments, 
								(object)$attinfo
							);
						}
					}
	
					$atts = $this->wikis->getAttachments (
						$login['id'], $project, $wiki->name);
					if ($atts === FALSE)
					{
						$data['wiki'] = $wiki;
						$data['message'] = 'DATABASE ERROR';
						$this->load->view ($this->VIEW_EDIT, $data);	
						return;
					}
					$wiki->attachments = $atts;

					if (strpos ($wiki->name, ':') !== FALSE)
					{
						$data['message'] = $this->lang->line('WIKI_MSG_NAME_NO_COLON');
						$data['wiki'] = $wiki;
						$this->load->view ($this->VIEW_EDIT, $data);	
						return;
					}

					if ($this->wikihelper->_is_reserved ($wiki->name, FALSE))
					{
						$data['message'] = sprintf (
							$this->lang->line('WIKI_MSG_RESERVED_WIKI_NAME'), 
							$wiki->name
						);
						$data['wiki'] = $wiki;
						$this->load->view ($this->VIEW_EDIT, $data);
					}
					else
					{
						list($ret,$extra) = 
							$this->_upload_attachments ('wiki_new_attachment');
						if ($ret === FALSE)
						{
							$data['wiki'] = $wiki;
							$data['message'] = $extra;
							$this->load->view ($this->VIEW_EDIT, $data);
							return;
						}

						$wiki->new_attachments = $extra;

						$result = ($mode == 'update')?
							$this->wikis->update ($login['id'], $wiki):
							$this->wikis->create ($login['id'], $wiki);

						if ($result === FALSE)
						{
							foreach ($extra as $att) 
								@unlink ($att['fullencpath']);

							$data['message'] = 'DATABASE ERROR';
							$data['wiki'] = $wiki;
							$this->load->view ($this->VIEW_EDIT, $data);	
						}
						else
						{
							// delete attachments after database operation
							// as 'delete' is not easy to restore.
							foreach ($wiki->delete_attachments as $att)
								@unlink (CODEPOT_ATTACHMENT_DIR . "/{$att->encname}");

							redirect ("wiki/show/{$project->id}/" . 
								$this->converter->AsciiToHex($wiki->name));
						}
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
						$data['message'] = 
							$this->lang->line('WIKI_MSG_NO_SUCH_PAGE') . 
							" - {$name}";
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

	function create ($projectid = '', $name = '')
	{
		return $this->_edit_wiki ($projectid, $name, 'create');
	}

	function update ($projectid = '', $name = '')
	{
		return $this->_edit_wiki ($projectid, $name, 'update');
	}

	function delete ($projectid = '', $name = '')
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('WikiModel', 'wikis');

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
		else if ($this->wikihelper->_is_reserved ($name, FALSE))
		{
			$data['project'] = $project;
			$data['message'] = sprintf (
				$this->lang->line('WIKI_MSG_RESERVED_WIKI_NAME'), $name);
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$data['message'] = '';
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
							redirect ("wiki/home/{$project->id}");
						}
					}
					else 
					{
						redirect ("wiki/show/{$project->id}/" . 
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
					$data['message'] = sprintf (
						$this->lang->line('WIKI_MSG_NO_SUCH_PAGE'), $name);
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

	function _upload_attachments ($id)
	{
		$attno = 0;
		$count = count($_FILES);

		$attachments = array ();

		for ($i = 0; $i < $count; $i++)
		{	
			$field_name = "{$id}_{$i}";

			if (array_key_exists($field_name, $_FILES) &&
			    $_FILES[$field_name]['name'] != '')
			{
				$fname = $_FILES[$field_name]['name'];
				if (strpos ($fname, ':') !== FALSE)
				{
					while ($attno > 0)
						@unlink ($attachments[$attno--]['fullencpath']);
					return array(FALSE,$this->lang->line('WIKI_MSG_ATTACHMENT_NAME_NO_COLON'));
				}

				$ext = substr ($fname, strrpos ($fname, '.') + 1);

				// delete all \" instances ...
				$_FILES[$field_name]['type'] =
					str_replace('\"', '', $_FILES[$field_name]['type']);
				// delete all \\ instances ...
				$_FILES[$field_name]['type'] =
					str_replace('\\', '', $_FILES[$field_name]['type']);

				//$config['allowed_types'] = $ext;
				$config['allowed_types'] = '*';
				$config['upload_path'] = CODEPOT_ATTACHMENT_DIR;
				$config['max_size'] = CODEPOT_MAX_UPLOAD_SIZE;
				$config['encrypt_name'] = TRUE;

				$this->upload->initialize ($config);
	
				if (!$this->upload->do_upload ($field_name))
				{
					while ($attno > 0)
						@unlink ($attachments[$attno--]['fullencpath']);
					return array(FALSE,$this->upload->display_errors('',''));
				}

				$upload = $this->upload->data ();

				$attachments[$attno++] = array (
					'name' => $fname,
					'encname' => $upload['file_name'], 
					'fullencpath' => $upload['full_path']);
			}
		}

		return array(TRUE,$attachments);
	}
}
