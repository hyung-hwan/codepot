<?php

class Graph extends CI_Controller
{
	var $VIEW_ERROR = 'error';
	var $VIEW_MAIN = 'graph_main';

	function __construct ()
	{
		parent::__construct ();
		$this->load->helper ('url');
		$this->load->helper ('form');
		$this->load->library ('Converter', 'converter');
		$this->load->model (CODEPOT_LOGIN_MODEL, 'login');

		$this->lang->load ('common', CODEPOT_LANG); 
		$this->lang->load ('code', CODEPOT_LANG); 
	}

	function home ($projectid = '')
	{
		return $this->main ($projectid);
	}

	function _normalize_path ($path)
	{
		$path = preg_replace('/[\/]+/', '/', $path);
		if ($path == '/') $path = '';
		return $path;
	}

	function main ($projectid = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');

		$login = $this->login->getUser();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect (CODEPOT_SIGNIN_REDIR_PATH . $this->converter->AsciiTohex(current_url()));
		$data['login'] = $login;

		//$path = $this->converter->HexToAscii($path);
		//if ($path == '.') $path = ''; /* treat a period specially */
		//$path = $this->_normalize_path ($path);

		$project = $this->projects->get($projectid);
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

			$data['project'] = $project;
			$this->load->view ($this->VIEW_MAIN, $data);
		}
	}

	function enjson_code_history ($projectid = '', $path = '')
	{
		$this->load->model ('ProjectModel', 'projects');

		$login = $this->login->getUser();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
		{
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found'); 
			return;
		}

		$project = $this->projects->get($projectid);
		if ($project === FALSE || ($project->public !== 'Y' && $login['id'] == ''))
		{
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found'); 
			return;
		}

		$this->load->model ('SubversionModel', 'subversion');

		$path = $this->converter->HexToAscii($path);
		if ($path == '.') $path = ''; /* treat a period specially */
		$path = $this->_normalize_path ($path);

		$file = $this->subversion->getHistory ($projectid, $path, SVN_REVISION_HEAD);
		if ($file === FALSE)
		{
			$history = array();
		}
		else
		{
			$history = $file['history'];
			$count = count($history);
			for ($i = 0; $i < $count; $i++)
			{
				unset ($history[$i]['msg']);
				unset ($history[$i]['paths']);
			}
		}

		print codepot_json_encode($history);
	}

	function enjson_loc_by_lang ($projectid = '', $path = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');

		$login = $this->login->getUser();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
		{
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found'); 
			return;
		}

		$project = $this->projects->get($projectid);
		if ($project === FALSE || ($project->public !== 'Y' && $login['id'] == ''))
		{
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found'); 
			return;
		}

		$this->load->model ('SubversionModel', 'subversion');

		$path = $this->converter->HexToAscii($path);
		if ($path == '.') $path = ''; /* treat a period specially */
		$path = $this->_normalize_path ($path);

		$cloc = $this->subversion->clocRevByLang($projectid, $path, $rev);
		print codepot_json_encode($cloc);
	}

	function enjson_loc_by_file ($projectid = '', $path = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');

		$login = $this->login->getUser();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
		{
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found'); 
			return;
		}

		$project = $this->projects->get($projectid);
		if ($project === FALSE || ($project->public !== 'Y' && $login['id'] == ''))
		{
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found'); 
			return;
		}

		$this->load->model ('SubversionModel', 'subversion');

		$path = $this->converter->HexToAscii($path);
		if ($path == '.') $path = ''; /* treat a period specially */
		$path = $this->_normalize_path ($path);

		$cloc = $this->subversion->clocRevByFile($projectid, $path, $rev);
		print codepot_json_encode($cloc);
	}

	function enjson_revision_graph ($projectid = '', $path = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');

		$login = $this->login->getUser();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
		{
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found'); 
			return;
		}

		$project = $this->projects->get($projectid);
		if ($project === FALSE || ($project->public !== 'Y' && $login['id'] == ''))
		{
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found'); 
			return;
		}

		$this->load->model ('SubversionModel', 'subversion');

		$path = $this->converter->HexToAscii($path);
		if ($path == '.') $path = ''; /* treat a period specially */
		$path = $this->_normalize_path ($path);

		$rg = $this->subversion->revisionGraph($projectid, $path, $rev);
		print codepot_json_encode($rg);
	}

	function enjson_project_members ($filter = '')
	{
		$this->load->model ('ProjectModel', 'projects');

		$login = $this->login->getUser();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
		{
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found'); 
			return;
		}

		$filter = $this->converter->HexToAscii($filter);
		$rel = $this->projects->getProjectMembers($filter);
		print codepot_json_encode($rel);
	}
}
