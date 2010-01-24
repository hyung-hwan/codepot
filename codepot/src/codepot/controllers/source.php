<?php

class Source extends Controller
{
	var $VIEW_ERROR = 'error';
	var $VIEW_FOLDER = 'source_folder';
	var $VIEW_FILE = 'source_file';
	var $VIEW_BLAME = 'source_blame';
	var $VIEW_HISTORY = 'source_history';

	function Source ()
	{
		parent::Controller ();
		$this->load->helper ('url');
		$this->load->helper ('form');
		$this->load->library ('Converter', 'converter');
		$this->load->model (CODEPOT_LOGIN_MODEL, 'login');

		$this->load->library ('Language', 'lang');
		$this->lang->load ('common', CODEPOT_LANG);
	}

	function home ($projectid = '', $subdir = '', $rev = SVN_REVISION_HEAD)
	{
		return $this->folder ($projectid, $subdir, $rev);
	}

	function folder ($projectid = '', $path = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
	
		$loginid = $this->login->getUserid ();
		if (CODEPOT_ALWAYS_REQUIRE_SIGNIN && $loginid == '')
			redirect ('main/signin');
		$data['loginid'] = $loginid;

		$path = $this->converter->HexToAscii ($path);

		$project = $this->projects->get ($projectid);
		if ($project === FALSE)
		{
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
                }
		else if ($project === NULL)
		{
			$data['message'] = "NO SUCH PROJECT - $projectid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$files = $this->subversion->getList ($projectid, $path, $rev);
			if ($files === FALSE)
			{
				$data['message'] = 'Failed to get file list';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$data['project'] = $project;
				$data['folder'] = $path;
				$data['files'] = $files;
				$data['revision'] = $rev;
				$this->load->view ($this->VIEW_FOLDER, $data);
			}
		}
	}

	function file ($projectid, $path, $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
	
		$loginid = $this->login->getUserid ();
		if (CODEPOT_ALWAYS_REQUIRE_SIGNIN && $loginid == '')
			redirect ('main/signin');
		$data['loginid'] = $loginid;

		$path = $this->converter->HexToAscii ($path);

		$project = $this->projects->get ($projectid);
		if ($project === FALSE)
		{
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
                }
		else if ($project === NULL)
		{
			$data['message'] = "NO SUCH PROJECT - $projectid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$file = $this->subversion->getFile ($projectid, $path, $rev);
			if ($file === FALSE)
			{
				$files = $this->subversion->getList ($projectid, $path, $rev);
				if ($files === FALSE)
				{
					$data['message'] = 'Failed to get file';
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else
				{
					$data['project'] = $project;
					$data['folder'] = $path;
					$data['files'] = $files;
					$data['revision'] = $rev;
					$this->load->view ($this->VIEW_FOLDER, $data);
				}
			}
			else
			{
				$data['project'] = $project;
				$data['folder'] = substr ($path, 0, strrpos($path, '/'));
				$data['file'] = $file;
				$data['revision'] = $rev;
				$this->load->view ($this->VIEW_FILE, $data);
			}
		}
	}

	function blame ($projectid, $path, $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
	
		$loginid = $this->login->getUserid ();
		if (CODEPOT_ALWAYS_REQUIRE_SIGNIN && $loginid == '')
			redirect ('main/signin');
		$data['loginid'] = $loginid;

		$path = $this->converter->HexToAscii ($path);

		$project = $this->projects->get ($projectid);
		if ($project === FALSE)
		{
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
                }
		else if ($project === NULL)
		{
			$data['message'] = "NO SUCH PROJECT - $projectid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$file = $this->subversion->getBlame ($projectid, $path, $rev);
			if ($file === FALSE)
			{
				$data['message'] = 'Failed to get file content';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$data['project'] = $project;
				$data['folder'] = substr ($path, 0, strrpos($path, '/'));
				$data['file'] = $file;
				$data['revision'] = $rev;
				$this->load->view ($this->VIEW_BLAME, $data);
			}
		}
	}

	function history ($type, $projectid, $path = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
	
		$loginid = $this->login->getUserid ();
		if (CODEPOT_ALWAYS_REQUIRE_SIGNIN && $loginid == '')
			redirect ('main/signin');
		$data['loginid'] = $loginid;

		$path = $this->converter->HexToAscii ($path);

		$project = $this->projects->get ($projectid);
		if ($project === FALSE)
		{
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
                }
		else if ($project === NULL)
		{
			$data['message'] = "NO SUCH PROJECT - $projectid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$file = $this->subversion->getHistory ($projectid, $path, $rev);
			if ($file === FALSE)
			{
				$data['message'] = 'Failed to get log content';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$data['type'] = $type;
				$data['project'] = $project;
				//$data['folder'] = substr ($path, 0, strrpos($path, '/'));
				$data['folder'] = $path;
				$data['file'] = $file;
				$data['revision'] = $rev;
				$this->load->view ($this->VIEW_HISTORY, $data);
			}
		}
	}

}
