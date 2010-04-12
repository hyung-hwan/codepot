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

	function file ($projectid, $path = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
	
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ('main/signin');
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
			$file = $this->subversion->getFile ($projectid, $path, $rev);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'Failed to get file';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else if ($file['type'] == 'file')
			{
				$head_rev = $this->subversion->getHeadRev ($projectid, $path);
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

	function blame ($projectid, $path, $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
	
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ('main/signin');
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
			$file = $this->subversion->getBlame ($projectid, $path, $rev);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'Failed to get file content';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$head_rev = $this->subversion->getHeadRev ($projectid, $path);
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

	function history ($projectid, $path = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
	
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ('main/signin');
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

	function revision ($projectid, $path, $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
	
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ('main/signin');
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

	function diff ($projectid, $path, $rev1 = SVN_REVISION_HEAD, $rev2 = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ('main/signin');
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
			$file = $this->subversion->getDiff ($projectid, $path, $rev1, $rev2);
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

	function _normalize_path ($path)
	{
		$path = preg_replace('/[\/]+/', '/', $path);
		if ($path == '/') $path = '';
		return $path;
	}

}
