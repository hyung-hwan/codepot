<?php

class Wiki extends Controller
{
	var $VIEW_ERROR = 'error';
	var $VIEW_HOME = 'wiki_home';
	var $VIEW_SHOW = 'wiki_show';
	var $VIEW_EDIT = 'wiki_edit';
	var $VIEW_EDITX = 'wiki_editx';
	var $VIEW_DELETE = 'wiki_delete';

	function __construct ()
	{
		parent::__construct ();

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
			redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));
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
				redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));
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

	private function _show_wiki ($projectid, $name, $create)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('WikiModel', 'wikis');

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));
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
			if ($project->public !== 'Y' && $login['id'] == '')
			{
				// non-public projects require sign-in.
				redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));
			}

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
				// redirect to  a special link like __WIKI__:projectid:wikiname, #R1234, #I999
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
						$data['message'] = $this->lang->line('WIKI_MSG_NO_SUCH_PAGE') . " - {$name}";
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
		$this->_show_wiki ($projectid, $name, CODEPOT_CREATE_MISSING_WIKI);
	}

	function show_r ($projectid = '' , $name = '')
	{
		$this->_show_wiki ($projectid, $name, FALSE);
	}

	private function _edit_wiki ($projectid, $name, $mode, $view_edit)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');
		$this->load->library ('upload');

		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('WikiModel', 'wikis');

		$login = $this->login->getUser ();
		if ($login['id'] == '') 
			redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));
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
			if ($mode == 'update')
			{
				$this->form_validation->set_rules (
					'wiki_original_name', 'original name', 'required|max_length[255]');
			}
			$this->form_validation->set_rules (
				'wiki_projectid', 'project ID', 'required|alpha_dash|max_length[32]');
			$this->form_validation->set_rules (
				'wiki_name', 'name', 'required|max_length[255]');
			$this->form_validation->set_rules (
				'wiki_text', 'text', 'required');
			$this->form_validation->set_rules (
				'wiki_columns', 'columns', 'required|integer|min_length[1]|max_length[1]|greater_than[0]|less_than[10]');
			$this->form_validation->set_error_delimiters (
				'<span class="form_field_error">','</span>');

			$data['mode'] = $mode;
			$data['message'] = '';
			$data['project'] = $project;

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
					$data['message'] = $this->lang->line('WIKI_MSG_NO_SUCH_PAGE') . " - {$name}";
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else
				{
					$data['wiki'] = $wiki;
					$this->load->view ($view_edit, $data);
				}
			}
			else
			{
				$wiki = new stdClass();
				$wiki->projectid = $projectid;
				$wiki->name = $name;
				$wiki->text = '';
				$wiki->columns = '1';
				$wiki->attachments = array();

				$data['wiki'] = $wiki;
				$this->load->view ($view_edit, $data);
			}
		}
	}

	function create ($projectid = '', $name = '')
	{
		return $this->_edit_wiki ($projectid, $name, 'create', $this->VIEW_EDIT);
	}

	function update ($projectid = '', $name = '')
	{
		return $this->_edit_wiki ($projectid, $name, 'update', $this->VIEW_EDIT);
	}

	function createx ($projectid = '', $name = '')
	{
		return $this->_edit_wiki ($projectid, $name, 'create', $this->VIEW_EDITX);
	}

	function updatex ($projectid = '', $name = '')
	{
		return $this->_edit_wiki ($projectid, $name, 'update', $this->VIEW_EDITX);
	}

	///////////////////////////////////////////////////////////////////////
	// Handling of attached files share the (almost) same code 
	// between issue.php and wiki.php. It would be way better
	// to put the common code into a parent class and use inheritance.
	// Makre sure to apply changes to both files if any.

	private function _handle_wiki_attachment ($login, $projectid, $wikiname, $name)
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
			if ($project->public !== 'Y' && $login['id'] == '')
			{
				// non-public projects require sign-in.
				redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));
			}

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
					$this->lang->line('MSG_NO_SUCH_FILE'), $name);
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
						$this->lang->line('MSG_FAILED_TO_READ_FILE'), $name);
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

				codepot_readfile ($path, $name, mime_content_type($path), 'inline');
			}
		}
	}

	private function _handle_issue_file ($login, $projectid, $issueid, $filename)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('IssueModel', 'issues');

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
				redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));
			}

			$att = $this->issues->getFile ($login['id'], $project, $issueid, $filename);
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
					$this->lang->line('MSG_NO_SUCH_FILE'), $filename);
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$path = CODEPOT_ISSUE_FILE_DIR . "/{$att->encname}";

				$stat = @stat($path);
				if ($stat === FALSE)
				{
					$data['project'] = $project;
					$data['message'] = sprintf (
						$this->lang->line('MSG_FAILED_TO_READ_FILE'), $filename);
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

				codepot_readfile ($path, $filename, mime_content_type($path), 'inline');
			}
		}
	}

	function attachment0 ($projectid = '', $target = '')
	{
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));

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
			//$target => projectid:wikiname:attachment
			//$target => projectid:#I1:file
			if ($part[0] == '') $part[0] = $projectid;
			if ($part[1][0] == '#' && $part[1][1] == 'I')
			{
				$issueid = substr ($part[1],2);
				$this->_handle_issue_file ($login, $part[0], $issueid, $part[2]);
			}
			else
			{
				$this->_handle_wiki_attachment ($login, $part[0], $part[1], $part[2]);
			}
		}
		else if (count($part) == 2)
		{
			//$target => wikiname:attachment
			//$target => #I1:file
			if ($part[0][0] == '#' && $part[0][1] == 'I')
			{
				$issueid = substr ($part[0],2);
				$this->_handle_issue_file ($login, $projectid, $issueid, $part[1]);
			}
			else
			{
				$this->_handle_wiki_attachment ($login, $projectid, $part[0], $part[1]);
			}
		}
	}

	function attachment ($projectid = '', $wikiname = '', $filename = '')
	{
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));

		if ($wikiname == '' || $filename == '')
		{
			$data['login'] = $login;
			$data['message'] = 'INVALID PARAMETERS';
			$this->load->view ($this->VIEW_ERROR, $data);
			return;
		}

		$wikiname = $this->converter->HexToAscii ($wikiname);
		$filename = $this->converter->HexToAscii ($filename);

		$part = explode (':', $filename);
		if (count($part) == 3)
		{
			if ($part[0] != '') $projectid = $part[0];
			if ($part[1] != '') 
			{
				if ($part[1][0] == '#' && $part[1][1] == 'I')
				{
					$issueid = substr ($part[1],2);
					$wikiname = '';
				}
				else
				{
					$wikiname = $part[1];
					$issueid = '';
				}
			}
			if ($part[2] != '') $filename = $part[2];
		}
		else if (count($part) == 2)
		{
			//$target => wikiname:attachment
			//$target => #I1:file
			if ($part[0] != '')
			{
				if ($part[0][0] == '#' && $part[0][1] == 'I')
				{
					$issueid = substr ($part[0],2);
					$wikiname = '';
				}
				else
				{
					$wikiname = $part[0];
					$issueid = '';
				}
			}
			if ($part[1] != '') $filename = $part[1];
		}

		if ($wikiname != '')
			$this->_handle_wiki_attachment ($login, $projectid, $wikiname, $filename);
		else
			$this->_handle_issue_file ($login, $projectid, $issueid, $filename);
	}


	///////////////////////////////////////////////////////////////////

	function xhr_edit ($projectid = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('WikiModel', 'wikis');
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
				$wiki = new stdClass();
				$wiki->projectid = $projectid;
				$wiki->name = $this->input->post('wiki_name');
				$wiki->text = $this->input->post('wiki_text');
				$wiki->doctype = $this->input->post('wiki_doctype');

				$wiki->original_name = $this->input->post('wiki_original_name');
				$wiki_file_count = $this->input->post('wiki_file_count');
				$wiki_kill_file_count = $this->input->post('wiki_kill_file_count');

				if ($wiki->name === FALSE || ($wiki->name = trim($wiki->name)) == '')
				{
					$status = 'error - empty name';
				}
				else if (strpbrk ($wiki->name, CODEPOT_DISALLOWED_LETTERS_IN_WIKINAME) !== FALSE)
				{
					$status = 'error - disallowed characters in name';
				}
				else if ($wiki->text === FALSE || ($wiki->text = trim($wiki->text)) == '')
				{
					$status = 'error - empty text';
				}
				else
				{
					if ($wiki_file_count === FALSE || $wiki_file_count <= 0) $wiki_file_count = 0;
					if ($wiki_kill_file_count === FALSE || $wiki_kill_file_count <= 0) $wiki_kill_file_count = 0;

					if ($wiki->original_name === FALSE) $wiki->original_name = '';
					else $wiki->original_name = trim($wiki->original_name);

					$status = '';
					$attached_files = array ();
					for ($i = 0; $i < $wiki_file_count; $i++)
					{
						$fid = "wiki_file_{$i}";
						if (array_key_exists($fid, $_FILES) && $_FILES[$fid]['name'] != '')
						{
							if (strpbrk($_FILES[$fid]['name'], CODEPOT_DISALLOWED_LETTERS_IN_FILENAME) !== FALSE)
							{
								// prevents these letters for wiki creole 
								$status = "error - disallowed character contained - {$_FILES[$fid]['name']}";
								break;
							}

							array_push ($attached_files, array ('fid' => $fid, 'name' => $_FILES[$fid]['name']));
						}
					}

					if ($status == '')
					{
						$kill_files = array();
						for ($i = 0; $i < $wiki_kill_file_count; $i++)
						{
							$n = $this->input->post("wiki_kill_file_name_{$i}");
							if ($n != '') array_push ($kill_files, $n);
						}
					}

					if ($status == '')
					{
						if ($this->wikis->editWithFiles ($login['id'], $wiki, $attached_files, $kill_files, $this->upload) === FALSE)
						{
							$status = 'error - ' . $this->wikis->getErrorMessage();
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

	function xhr_delete ($projectid = '', $wikiname = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('WikiModel', 'wikis');

		$login = $this->login->getUser ();

		if ($login['id'] == '')
		{
			$status = 'error - anonymous user';
		}
		else
		{
			$wikiname = $this->converter->HexToAscii ($wikiname);

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
				$post_delete_confirm = $this->input->post('wiki_delete_confirm');

				if ($post_delete_confirm !== FALSE && $post_delete_confirm == 'Y')
				{
					$wiki = new stdClass();
					$wiki->projectid = $projectid;
					$wiki->name = $wikiname;
					if ($this->wikis->delete ($login['id'], $wiki) === FALSE)
					{
						$status = 'error - ' . $this->wikis->getErrorMessage();
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
