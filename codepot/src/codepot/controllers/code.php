<?php

class Code extends Controller
{
	var $VIEW_ERROR = 'error';
	var $VIEW_FOLDER = 'code_folder';
	var $VIEW_FILE = 'code_file';
	var $VIEW_BLAME = 'code_blame';
	var $VIEW_HISTORY = 'code_history';
	var $VIEW_REVISION = 'code_revision';
	var $VIEW_DIFF = 'code_diff';
	var $VIEW_FETCH = 'code_fetch';
	var $VIEW_SEARCH = 'code_search';

	function Code ()
	{
		parent::Controller ();
		$this->load->helper ('url');
		$this->load->helper ('form');
		$this->load->library ('Converter', 'converter');
		$this->load->model (CODEPOT_LOGIN_MODEL, 'login');

		$this->load->library ('Language', 'lang');
		$this->lang->load ('common', CODEPOT_LANG); 
		$this->lang->load ('code', CODEPOT_LANG); 
	}

	function home ($projectid = '', $subdir = '', $rev = SVN_REVISION_HEAD)
	{
		return $this->file ($projectid, $subdir, $rev);
	}

	function file ($projectid = '', $path = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
	
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));
		$data['login'] = $login;

		$path = $this->converter->HexToAscii ($path);
		if ($path == '.') $path = ''; /* treat a period specially */
		$path = $this->_normalize_path ($path);

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

			$file = $this->subversion->getFile ($projectid, $path, $rev);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'Failed to get file';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else if ($file['type'] == 'file')
			{
				$head_rev = $this->subversion->getHeadRev ($projectid, $path, $rev);
				if ($head_rev === FALSE)
				{
					$data['project'] = $project;
					$data['message'] = 'Failed to get head revision';
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else
				{
					$file['head_rev'] = $head_rev;
					$file['prev_rev'] = $this->subversion->getPrevRev (
						$projectid, $path, $file['created_rev']);
					$file['next_rev'] = $this->subversion->getNextRev (
						$projectid, $path, $file['created_rev']);

					$data['project'] = $project;
					$data['headpath'] = $path;
					$data['file'] = $file; 
					$data['revision'] = $rev;
					$this->load->view ($this->VIEW_FILE, $data);
				}
			}
			else
			{
				$data['project'] = $project;
				$data['headpath'] = $path;
				$data['file'] = $file;

				$data['revision'] = $rev;
				$data['prev_revision'] =
					$this->subversion->getPrevRev ($projectid, $path, $rev);
				$data['next_revision'] =
					$this->subversion->getNextRev ($projectid, $path, $rev);

				$this->load->view ($this->VIEW_FOLDER, $data);
			}
		}
	}

	function blame ($projectid = '', $path = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
	
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));
		$data['login'] = $login;

		$path = $this->converter->HexToAscii ($path);
		if ($path == '.') $path = ''; /* treat a period specially */
		$path = $this->_normalize_path ($path);

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

			$file = $this->subversion->getBlame ($projectid, $path, $rev);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'Failed to get file content';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$head_rev = $this->subversion->getHeadRev ($projectid, $path, $rev);
				if ($head_rev === FALSE)
				{
					$data['project'] = $project;
					$data['message'] = 'Failed to get head revision';
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else
				{
					$file['head_rev'] = $head_rev;
					$file['prev_rev'] = $this->subversion->getPrevRev (
						$projectid, $path, $file['created_rev']);
					$file['next_rev'] = $this->subversion->getNextRev (
						$projectid, $path, $file['created_rev']);

					$data['project'] = $project;
					$data['headpath'] = $path;

					$data['file'] = $file;
					$data['revision'] = $rev;
					$this->load->view ($this->VIEW_BLAME, $data);
				}
			}
		}
	}

	function history ($projectid = '', $path = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
	
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));
		$data['login'] = $login;

		$path = $this->converter->HexToAscii ($path);
		if ($path == '.') $path = ''; /* treat a period specially */
		$path = $this->_normalize_path ($path);

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

			$file = $this->subversion->getHistory ($projectid, $path, $rev);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'Failed to get log content';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$data['project'] = $project;
				$data['fullpath'] = $path;
				$data['file'] = $file;

				$data['revision'] = $rev;
				$data['prev_revision'] =
					$this->subversion->getPrevRev ($projectid, $path, $rev);
				$data['next_revision'] =
					$this->subversion->getNextRev ($projectid, $path, $rev);

				$this->load->view ($this->VIEW_HISTORY, $data);
			}
		}
	}

	function revision ($projectid = '', $path = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');

	
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));
		$data['login'] = $login;

		$path = $this->converter->HexToAscii ($path);
		if ($path == '.') $path = ''; /* treat a period specially */
		$path = $this->_normalize_path ($path);

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

			$data['edit_error_message'] = '';
			if ($login['id'] != '' && 
			    $login['id'] == $this->subversion->getRevProp($projectid, $rev, 'svn:author'))
			{
				// the current user must be the author of the revision to be able to 
				// change the log message.
				$this->load->helper ('form');
				$this->load->library ('form_validation');

				$this->form_validation->set_rules ('edit_log_message', 'Message', 'required|min_length[2]');
				$this->form_validation->set_error_delimiters('<span class="form_field_error">','</span>');

				if ($this->input->post('edit_log_message'))
				{
					$logmsg =  $this->input->post('edit_log_message');
					if ($this->form_validation->run())
					{
						if ($logmsg != $this->subversion->getRevProp ($projectid, $rev, 'svn:log'))
						{
							$actual_rev = $this->subversion->setRevProp (
								$projectid, $rev, 'svn:log', $logmsg, $login['id']);
							if ($actual_rev === FALSE)
							{
								$data['edit_error_message'] = 'Cannot change revision log message';
							}
						}
					}
					else
					{
						$data['edit_error_message'] = 'Invalid revision log message';
					}
				}
			}

			$file = $this->subversion->getRevHistory ($projectid, $path, $rev);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'Failed to get log content';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$data['project'] = $project;
				$data['headpath'] = $path;
				$data['file'] = $file;

				$data['revision'] = $rev;
				$data['prev_revision'] =
					$this->subversion->getPrevRev ($projectid, $path, $rev);
				$data['next_revision'] =
					$this->subversion->getNextRev ($projectid, $path, $rev);

				$this->load->view ($this->VIEW_REVISION, $data);
			}
		}
	}

	function _do_diff ($projectid = '', $path = '', $rev1 = SVN_REVISION_HEAD, $rev2 = SVN_REVISION_HEAD, $full = FALSE)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));
		$data['login'] = $login;

		$path = $this->converter->HexToAscii ($path);
		if ($path == '.') $path = ''; /* treat a period specially */
		$path = $this->_normalize_path ($path);

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

			$file = $this->subversion->getDiff ($projectid, $path, $rev1, $rev2, $full);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'Failed to get diff';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$file['prev_rev'] =
					$this->subversion->getPrevRev ($projectid, $path, $file['created_rev']);	
				$file['next_rev'] =
					$this->subversion->getNextRev ($projectid, $path, $file['created_rev']);	
				$file['against']['prev_rev'] = 
					$this->subversion->getPrevRev ($projectid, $path, $file['against']['created_rev']);
				$file['against']['next_rev'] = 
					$this->subversion->getNextRev ($projectid, $path, $file['against']['created_rev']);

				$data['project'] = $project;
				//$data['folder'] = substr ($path, 0, strrpos($path, '/'));
				$data['headpath'] = $path;
				$data['revision1'] = $rev1;
				$data['revision2'] = $rev2;
				$data['file'] = $file;
				$this->load->view ($this->VIEW_DIFF, $data);
			}
		}
	}

	function diff ($projectid = '', $path = '', $rev1 = SVN_REVISION_HEAD, $rev2 = SVN_REVISION_HEAD)
	{
		return $this->_do_diff ($projectid, $path, $rev1, $rev2, FALSE);
	}

	function fulldiff ($projectid = '', $path = '', $rev1 = SVN_REVISION_HEAD, $rev2 = SVN_REVISION_HEAD)
	{
		return $this->_do_diff ($projectid, $path, $rev1, $rev2, TRUE);
	}

	function fetch ($projectid = '', $path = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
	
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));
		$data['login'] = $login;

		$path = $this->converter->HexToAscii ($path);
		if ($path == '.') $path = ''; /* treat a period specially */
		$path = $this->_normalize_path ($path);

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

			$file = $this->subversion->getFile ($projectid, $path, $rev);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'Failed to get file';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else if ($file['type'] == 'file')
			{
				header ('Content-Description: File Transfer');
				header ('Content-Type: application/octet-stream');
				header ('Content-Disposition: attachment; filename='. basename($path));
				header ('Content-Transfer-Encoding: binary');
				header ('Content-Length: ' . strlen($file['content']));
				flush ();

				print $file['content'];
				flush ();
			}
			else
			{
				$data['project'] = $project;
				$data['headpath'] = $path;
				$data['file'] = $file;

				$data['revision'] = $rev;
				$data['prev_revision'] =
					$this->subversion->getPrevRev ($projectid, $path, $rev);
				$data['next_revision'] =
					$this->subversion->getNextRev ($projectid, $path, $rev);

				$this->load->view ($this->VIEW_FOLDER, $data);
			}
		}
	}

	function _search_code ($project, $login)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');

		$data['login'] = $login;
		$data['message'] = '';

		$this->form_validation->set_rules ('search_pattern', 'pattern', 'required');
		$this->form_validation->set_rules ('search_folder', 'folder', '');
		$this->form_validation->set_rules ('search_revision', 'revision', 'numeric');
		$this->form_validation->set_error_delimiters('<span class="form_field_error">','</span>');

		if ($this->input->post('search_pattern'))
		{
			$pattern =  $this->input->post('search_pattern');
			$path = $this->input->post('search_folder');
			$path = $this->_normalize_path ($path);
			$rev = $this->input->post('search_revision');

			$file = $this->subversion->getFile ($project->id, $path, $rev);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = "Failed to get file - %path";
				$this->load->view ($this->VIEW_ERROR, $data);
			}

			if ($this->form_validation->run())
			{
				$data['project'] = $project;
				$data['headpath'] = $path;
				$data['pattern'] = $pattern;
				$data['file'] = $file;

				$data['revision'] = $rev;
				$data['prev_revision'] =
					$this->subversion->getPrevRev ($project->id, $path, $rev);
				$data['next_revision'] =
					$this->subversion->getNextRev ($project->id, $path, $rev);

				$this->load->view ($this->VIEW_SEARCH, $data);
			}
			else
			{
				// TODO: arrange to display an error message...
				$data['project'] = $project;
				$data['headpath'] = $path;
				$data['file'] = $file;

				$data['revision'] = $rev;
				$data['prev_revision'] =
					$this->subversion->getPrevRev ($project->id, $path, $rev);
				$data['next_revision'] =
					$this->subversion->getNextRev ($project->id, $path, $rev);
				$this->load->view ($this->VIEW_FOLDER, $data);
			}
		}
		else
		{
			$data['project'] = $project;
			$data['message'] = 'Failed to search';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
	}

	function search ($projectid = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
	
		$login = $this->login->getUser ();
		if ((CODEPOT_SIGNIN_COMPULSORY || CODEPOT_SIGNIN_FOR_CODE_SEARCH) && $login['id'] == '')
			redirect ("main/signin/" . $this->converter->AsciiTohex(current_url()));

		$project = $this->projects->get ($projectid);
		if ($project === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if ($project === NULL)
		{
			$data['login'] = $login;
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

			$this->_search_code ($project, $login);
		}
	}

	function _normalize_path ($path)
	{
		$path = preg_replace('/[\/]+/', '/', $path);
		if ($path == '/') $path = '';
		return $path;
	}

}
