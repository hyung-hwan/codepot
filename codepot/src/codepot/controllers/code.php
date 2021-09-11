<?php

class Code extends CI_Controller
{
	var $VIEW_ERROR = 'error';
	var $VIEW_FOLDER = 'code_folder';
	var $VIEW_FILE = 'code_file';
	var $VIEW_BLAME = 'code_blame';
	var $VIEW_EDIT = 'code_edit';
	var $VIEW_HISTORY = 'code_history';
	var $VIEW_REVISION = 'code_revision';
	var $VIEW_DIFF = 'code_diff';
	var $VIEW_FETCH = 'code_fetch';
	var $VIEW_SEARCH = 'code_search';

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

	private function _can_read ($pm, $projectid, $login)
	{
		$userid = $login['id'];

		if ($userid != '')
		{
			if ($login['sysadmin?']) return TRUE;
			if ($pm->projectHasMember($projectid, $userid)) return TRUE;
		}

		if ($pm->projectIsPublic($projectid)) 
		{
			if (strcasecmp(CODEPOT_CODE_READ_ACCESS, 'anonymous') == 0) 
			{
				return TRUE;
			}
			else if (strcasecmp(CODEPOT_CODE_READ_ACCESS, 'authenticated') == 0)
			{
				if ($userid != '') return TRUE;
			}
			else if (strcasecmp(CODEPOT_CODE_READ_ACCESS, 'authenticated-insider') == 0)
			{
				if ($userid != '' && $login['insider?']) return TRUE;
			}
			//else if (strcasecmp(CODEPOT_CODE_READ_ACCESS, 'member') == 0)
			//{
			//	if ($userid != '' && $pm->projectHasMember($projectid, $userid)) return TRUE;
			//}
		}

		return FALSE;
	}

	private function _can_write ($pm, $projectid, $login)
	{
		$userid = $login['id'];
		if ($userid != '')
		{
			if ($login['sysadmin?']) return TRUE;
			if ($pm->projectHasMember($projectid, $userid)) return TRUE;
		}

		return FALSE;
	}

	private function _redirect_to_signin ($conv, $login, $project = NULL)
	{
		$userid = $login['id'];
		if ($userid == '')
		{
			redirect (CODEPOT_SIGNIN_REDIR_PATH . $conv->AsciiTohex(current_url()));
		}
		else
		{
			$data['login'] = $login;
			$data['project'] = $project;
			$data['message'] = 'Disallowed';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
	}

	function home ($projectid = '', $subdir = '', $rev = SVN_REVISION_HEAD)
	{
		return $this->file ($projectid, $subdir, $rev);
	}

	function file ($projectid = '', $path = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
		$this->load->library ('upload');

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
		{
			$this->_redirect_to_signin($this->converter, $login);
			return;
		}

		$data['login'] = $login;

		$path = $this->converter->HexToAscii($path);
		if ($path == '.') $path = ''; // treat a period specially
		$path = $this->_normalize_path($path);

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
			//if ($project->public !== 'Y' && $login['id'] == '')
			if (!$this->_can_read($this->projects, $projectid, $login))
			{
				// non-public projects require sign-in.
				$this->_redirect_to_signin($this->converter, $login, $project);
				return;
			}

			$file = $this->subversion->getFile($projectid, $path, $rev);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'Failed to get file';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else 
			{
				if ($file['type'] == 'file')
				{
					$head_rev = $this->subversion->getHeadRev($projectid, $path, $rev);
					if ($head_rev === FALSE)
					{
						$data['project'] = $project;
						$data['message'] = 'Failed to get head revision';
						$this->load->view ($this->VIEW_ERROR, $data);
					}
					else
					{
						$file['head_rev'] = $head_rev;
						$file['prev_rev'] = $this->subversion->getPrevRev(
							$projectid, $path, $file['created_rev']);
						$file['next_rev'] = $this->subversion->getNextRev(
							$projectid, $path, $file['created_rev']);

						$file['created_tag'] = $this->subversion->getRevProp($projectid, $file['created_rev'], CODEPOT_SVN_TAG_PROPERTY);
						if ($file['created_tag'] === FALSE) $file['created_tag'] = '';

						$file['head_tag'] = $this->subversion->getRevProp($projectid, $file['head_rev'], CODEPOT_SVN_TAG_PROPERTY);
						if ($file['head_tag'] === FALSE) $file['head_tag'] = '';

						$data['project'] = $project;
						$data['headpath'] = $path;
						$data['file'] = $file; 
						$data['revision'] = $rev;

						$this->load->view($this->VIEW_FILE, $data);
					}
				}
				else
				{
					$file['created_tag'] = $this->subversion->getRevProp($projectid, $file['created_rev'], CODEPOT_SVN_TAG_PROPERTY);
					if ($file['created_tag'] === FALSE) $file['created_tag'] = '';

					foreach ($file['content'] as &$f)
					{
						$exe = $this->subversion->getProp(
							$projectid, $path . '/' . $f['name'], 
							$file['created_rev'], 'svn:executable');
						if ($exe !== FALSE && is_array($exe)) 
						{
							// the answer is like this
							// Array ( [file:///var/lib/codepot/svnrepo/sg/trunk/drbdfix/drbdfix.sh] => Array ( [svn:executable] => * ) )
							foreach ($exe as &$ex)
							{
								if (array_key_exists('svn:executable', $ex)) 
								{
									$f['executable'] = $ex['svn:executable'];
									break;
								}
							}
						}
					}

					$data['project'] = $project;
					$data['headpath'] = $path;
					$data['file'] = $file;

					$data['revision'] = $rev;
					$data['prev_revision'] =
						$this->subversion->getPrevRev($projectid, $path, $rev);
					$data['next_revision'] =
						$this->subversion->getNextRev($projectid, $path, $rev);

					$data['readme_text'] = '';
					$data['readme_file'] = '';
					foreach (explode(',', CODEPOT_CODE_FOLDER_README) as $rf)
					{
						$rf = trim($rf);
						if (strlen($rf) > 0)
						{
							$readme = $this->subversion->getFile($projectid, $path . '/' . $rf, $rev);
							if ($readme !== FALSE && $readme['type'] == 'file')
							{
								$data['readme_text'] = $readme['content'];
								$data['readme_file'] = $rf;
								break;
							}
						}
					}

					$data['wildcard_pattern'] = '*';
					$this->load->view ($this->VIEW_FOLDER, $data);
				}
			}
		}
	}

	function blame ($projectid = '', $path = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
	
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
		{
			$this->_redirect_to_signin($this->converter, $login);
			return;
		}
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
			//if ($project->public !== 'Y' && $login['id'] == '')
			if (!$this->_can_read ($this->projects, $projectid, $login))
			{
				// non-public projects require sign-in.
				$this->_redirect_to_signin($this->converter, $login, $project);
				return;
			}

			$file = $this->subversion->getBlame($projectid, $path, $rev);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'Failed to get file content';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$head_rev = $this->subversion->getHeadRev($projectid, $path, $rev);
				if ($head_rev === FALSE)
				{
					$data['project'] = $project;
					$data['message'] = 'Failed to get head revision';
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else
				{
					$file['head_rev'] = $head_rev;
					$file['prev_rev'] = $this->subversion->getPrevRev(
						$projectid, $path, $file['created_rev']);
					$file['next_rev'] = $this->subversion->getNextRev(
						$projectid, $path, $file['created_rev']);

					$file['created_tag'] = $this->subversion->getRevProp($projectid, $file['created_rev'], CODEPOT_SVN_TAG_PROPERTY);
					if ($file['created_tag'] === FALSE) $file['created_tag'] = '';

					$file['head_tag'] = $this->subversion->getRevProp($projectid, $file['head_rev'], CODEPOT_SVN_TAG_PROPERTY);
					if ($file['head_tag'] === FALSE) $file['head_tag'] = '';


					$data['project'] = $project;
					$data['headpath'] = $path;

					$data['file'] = $file;
					$data['revision'] = $rev;

					$this->load->view ($this->VIEW_BLAME, $data);
				}
			}
		}
	}

	private function _edit ($projectid = '', $path = '', $rev = SVN_REVISION_HEAD, $caller = 'file')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
	
		$login = $this->login->getUser();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
		{
			$this->_redirect_to_signin($this->converter, $login);
			return;
		}
		$data['login'] = $login;

		$path = $this->converter->HexToAscii($path);
		if ($path == '.') $path = ''; /* treat a period specially */
		$path = $this->_normalize_path($path);

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
			//if ($project->public !== 'Y' && $login['id'] == '')
			if (!$this->_can_read($this->projects, $projectid, $login))
			{
				// non-public projects require sign-in.
				$this->_redirect_to_signin($this->converter, $login, $project);
				return;
			}

			$file = $this->subversion->getFile($projectid, $path, $rev);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'Failed to get file';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else if ($file['type'] == 'file')
			{
				$head_rev = $this->subversion->getHeadRev($projectid, $path, $rev);
				if ($head_rev === FALSE)
				{
					$data['project'] = $project;
					$data['message'] = 'Failed to get head revision';
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else
				{
					$file['head_rev'] = $head_rev;
					$file['prev_rev'] = $this->subversion->getPrevRev(
						$projectid, $path, $file['created_rev']);
					$file['next_rev'] = $this->subversion->getNextRev(
						$projectid, $path, $file['created_rev']);

					$file['created_tag'] = $this->subversion->getRevProp($projectid, $file['created_rev'], CODEPOT_SVN_TAG_PROPERTY);
					if ($file['created_tag'] === FALSE) $file['created_tag'] = '';

					$file['head_tag'] = $this->subversion->getRevProp($projectid, $file['head_rev'], CODEPOT_SVN_TAG_PROPERTY);
					if ($file['head_tag'] === FALSE) $file['head_tag'] = '';


					$data['project'] = $project;
					$data['headpath'] = $path;
					$data['file'] = $file; 
					$data['revision'] = $rev;
					$data['caller'] = $caller;

					$this->load->view ($this->VIEW_EDIT, $data);
				}
			}
			else
			{
				// it's not a file, you can't edit a directory.
				$data['project'] = $project;
				$data['message'] = 'You cannot edit a directory.';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
		}
	}

	function edit ($projectid = '', $path = '', $rev = SVN_REVISION_HEAD)
	{
		return $this->_edit($projectid, $path, $rev, 'file');
	}

	function bledit ($projectid = '', $path = '', $rev = SVN_REVISION_HEAD)
	{
		return $this->_edit($projectid, $path, $rev, 'blame');
	}

	function xhr_import ($projectid = '', $path = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
		$this->load->library ('upload');
	
		$login = $this->login->getUser ();
		$revision_saved = -1;

		if ($login['id'] == '')
		{
			$status = 'error - anonymous user';
		}
		else
		{
			$path = $this->converter->HexToAscii ($path);
			if ($path == '.') $path = ''; /* treat a period specially */
			$path = $this->_normalize_path($path);

			$project = $this->projects->get($projectid);
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
				$post_new_message = $this->input->post('code_new_message');
				$post_max_item_no = $this->input->post('code_new_item_count');
				$post_unzip = $this->input->post('code_new_item_unzip');
				if ($post_new_message !== FALSE && $post_max_item_no !== FALSE)
				{
					$import_files = array ();
					for ($i = 0; $i < $post_max_item_no; $i++)
					{
						$d = $this->input->post("code_new_item_dir_{$i}");
						if (strlen($d) > 0) 
						{
							array_push ($import_files, array ('type' => 'dir', 'name' => $d));
						}

						$d = $this->input->post("code_new_item_empfile_{$i}");
						if (strlen($d) > 0) 
						{
							array_push ($import_files, array('type' => 'empfile', 'name' => $d));
						}

						$fid = "code_new_item_file_{$i}";
						if (array_key_exists($fid, $_FILES) && $_FILES[$fid]['name'] != '')
						{
							array_push ($import_files, array('type' => 'file', 'name' => $_FILES[$fid]['name'], 'fid' => $fid, 'unzip' => $post_unzip));
						}
					}

					if (count($import_files) > 0 && $this->subversion->importFiles($projectid, $path, $login['id'], $post_new_message, $import_files, $this->upload) === FALSE)
					{
						$status = 'error - ' . $this->subversion->getErrorMessage();
					}
					else
					{
						$status = 'ok';
					}
				}
				else
				{
					$status = 'error - invalid post data';
				}
			}
		}

		print $status;
	}

	function xhr_delete ($projectid = '', $path = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');

		$login = $this->login->getUser();
		$revision_saved = -1;

		if ($login['id'] == '')
		{
			$status = 'error - anonymous user';
		}
		else
		{
			$path = $this->converter->HexToAscii($path);
			if ($path == '.') $path = ''; /* treat a period specially */
			$path = $this->_normalize_path($path);

			$project = $this->projects->get($projectid);
			if ($project === FALSE)
			{
				$status = "error - failed to get the project {$projectid}";
			}
			else if ($project === NULL)
			{
				$status = "error - no such project {$projectid}";
			}
			//else if (!$login['sysadmin?'] && 
			//        $this->projects->projectHasMember($projectid, $login['id']) === FALSE)
			else if (!$this->_can_write($this->projects, $projectid, $login))
			{
				$status = "error - disallowed";
			}
			else
			{
				$post_delete_message = $this->input->post('code_delete_message');
				$post_delete_file_count = $this->input->post('code_delete_file_count');
				if ($post_delete_message !== FALSE && $post_delete_file_count !== FALSE)
				{
					$delete_files = array ();
					for ($i = 0; $i < $post_delete_file_count; $i++)
					{
						$d = $this->input->post("code_delete_file_$i");

						if (strlen($d) > 0) 
						{
							array_push ($delete_files, $d);
						}
					}

					if (count($delete_files) > 0 && $this->subversion->deleteFiles($projectid, $path, $login['id'], $post_delete_message, $delete_files) === FALSE)
					{
						$status = 'error - ' . $this->subversion->getErrorMessage();
					}
					else
					{
						$status = 'ok';
					}
				}
				else
				{
					$status = 'error - invalid post data';
				}
			}
		}

		print $status;
	}

	function xhr_rename ($projectid = '', $path = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');

		$login = $this->login->getUser ();
		$revision_saved = -1;

		if ($login['id'] == '')
		{
			$status = 'error - anonymous user';
		}
		else
		{
			$path = $this->converter->HexToAscii($path);
			if ($path == '.') $path = ''; /* treat a period specially */
			$path = $this->_normalize_path($path);

			$project = $this->projects->get($projectid);
			if ($project === FALSE)
			{
				$status = "error - failed to get the project {$projectid}";
			}
			else if ($project === NULL)
			{
				$status = "error - no such project {$projectid}";
			}
			//else if (!$login['sysadmin?'] && 
			//         $this->projects->projectHasMember($projectid, $login['id']) === FALSE)
			else if (!$this->_can_write($this->projects, $projectid, $login))
			{
				$status = "error - disallowed";
			}
			else
			{
				$post_rename_message = $this->input->post('code_rename_message');
				$post_rename_file_count = $this->input->post('code_rename_file_count');
				if ($post_rename_message !== FALSE && $post_rename_file_count !== FALSE)
				{
					$rename_files = array();
					for ($i = 0; $i < $post_rename_file_count; $i++)
					{
						$d1 = $this->input->post("code_rename_file_old_$i");
						$d2 = $this->input->post("code_rename_file_new_$i");
						if (strlen($d1) > 0 && strlen($d2) > 0) 
						{
							array_push ($rename_files, array($d1, $d2));
						}
					}

					if (count($rename_files) > 0 && $this->subversion->renameFiles($projectid, $path, $login['id'], $post_rename_message, $rename_files) === FALSE)
					{
						$status = 'error - ' . $this->subversion->getErrorMessage();
					}
					else
					{
						$status = 'ok';
					}
				}
				else
				{
					$status = 'error - invalid post data';
				}
			}
		}

		print $status;
	}

	function xhr_gettagrev ($projectid = '', $tag = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');

		$login = $this->login->getUser();

		$project = $this->projects->get($projectid);
		if ($project === FALSE)
		{
			$status = "error - failed to get the project {$projectid}";
		}
		else if ($project === NULL)
		{
			$status = "error - no such project {$projectid}";
		}
		else
		{
			//if ($project->public !== 'Y' && $login['id'] == '')
			if (!$this->_can_read($this->projects, $projectid, $login))
			{
				// non-public projects require sign-in.
				$this->_redirect_to_signin($this->converter, $login, $project);
				return;
			}

			$tag = $this->converter->HexToAscii($tag);

			$rev = $this->subversion->findRevWithRevProp($projectid, CODEPOT_SVN_TAG_PROPERTY, $tag);
			if ($rev === FALSE)
			{
				$status = 'repoerr - ' . $this->subversion->getErrorMessage();
			}
			else if ($rev <= -1)
			{
				$status = 'noent';
			}
			else
			{
				$status = 'ok - ' . $rev;
			}
		}

		print $status;
	}

	function xhr_edit_revision_message ($projectid = '', $rev = SVN_REVISOIN_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');

		$login = $this->login->getUser();
		$revision_saved = -1;

		if ($login['id'] == '')
		{
			$status = 'error - anonymous user';
		}
		else
		{
			$project = $this->projects->get($projectid);
			if ($project === FALSE)
			{
				$status = "error - failed to get the project {$projectid}";
			}
			else if ($project === NULL)
			{
				$status = "error - no such project {$projectid}";
			}
			//else if (!$login['sysadmin?'] && 
			//         $this->projects->projectHasMember($projectid, $login['id']) === FALSE)
			else if (!$this->_can_write($this->projects, $projectid, $login))
			{
				$status = "error - disallowed";
			}
			else if ($login['id'] != $this->subversion->getRevProp($projectid, $rev, 'svn:author'))
			{
				$status = "error - not authored by {$login['id']}";
			}
			else
			{
				$logmsg = $this->input->post('code_edit_revision_message');
				if ($logmsg != $this->subversion->getRevProp ($projectid, $rev, 'svn:log'))
				{
					$affected_rev = $this->subversion->setRevProp(
						$projectid, $rev, 'svn:log', $logmsg, $login['id']);
					if ($affected_rev === FALSE)
					{
						$status = 'error - ' . $this->subversion->getErrorMessage();
					}
					else 
					{
						$status = 'ok';
					}
				}
				else
				{
					$status = 'ok';
				}
			}
		}

		print $status;
	}

	function xhr_edit_revision_tag ($projectid = '', $rev = SVN_REVISOIN_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');

		$login = $this->login->getUser();
		$revision_saved = -1;

		if ($login['id'] == '')
		{
			$status = 'error - anonymous user';
		}
		else
		{
			$project = $this->projects->get($projectid);
			if ($project === FALSE)
			{
				$status = "error - failed to get the project {$projectid}";
			}
			else if ($project === NULL)
			{
				$status = "error - no such project {$projectid}";
			}
			//else if (!$login['sysadmin?'] && 
			//         $this->projects->projectHasMember($projectid, $login['id']) === FALSE)
			else if (!$this->_can_write($this->projects, $projectid, $login))
			{
				$status = "error - disallowed";
			}
			//else if ($login['id'] != $this->subversion->getRevProp($projectid, $rev, 'svn:author'))
			//{
			//	$status = "error - not authored by {$login['id']}";
			//}
			else
			{
				$tag = $this->input->post('code_edit_revision_tag');
				$tag = ($tag === FALSE)? '': trim($tag);
				if (empty($tag)) 
				{
					// delete the tag if the value is empty
					$affected_rev = $this->subversion->killRevProp(
						$projectid, $rev, CODEPOT_SVN_TAG_PROPERTY, $login['id']);
				}
				else
				{
					$affected_rev = $this->subversion->setRevProp(
						$projectid, $rev, CODEPOT_SVN_TAG_PROPERTY, $tag, $login['id']);
				}

				if ($affected_rev === FALSE)
				{
					$status = 'error - ' . $this->subversion->getErrorMessage();
				}
				else
				{
					$status = 'ok';
				}
			}
		}

		print $status;
	}

	function xhr_new_review_comment ($projectid = '', $rev = SVN_REVISOIN_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
		$this->load->model ('CodeModel', 'code');

		$login = $this->login->getUser();
		$revision_saved = -1;

		if ($login['id'] == '')
		{
			$status = 'error - anonymous user';
		}
		else
		{
			$project = $this->projects->get($projectid);
			if ($project === FALSE)
			{
				$status = "error - failed to get the project {$projectid}";
			}
			else if ($project === NULL)
			{
				$status = "error - no such project {$projectid}";
			}
			//else if (!$login['sysadmin?'] && 
			//         $this->projects->projectHasMember($projectid, $login['id']) === FALSE)
			else if (!$this->_can_write($this->projects, $projectid, $login))
			{
				$status = "error - disallowed";
			}
			else
			{
				$review_url = $this->input->post('code_new_review_url');
				$review_comment = $this->input->post('code_new_review_comment');
				if ($review_comment === FALSE || ($review_comment = trim($review_comment)) == '')
				{
					$status = 'error - emtpy review comment';
				}
				else
				{
					$review_sno = $this->code->insertReview($projectid, $rev, $login['id'], $review_comment);
					if ($review_sno === FALSE)
					{
						$status = 'error - ' . $this->code->getErrorMessage();
					}
					else
					{
						$status = 'ok';

						if (CODEPOT_COMMIT_REVIEW_NOTIFICATION)
						{
							// TODO: message localization
							$email_subject =  sprintf(
								'New review message #%d for r%d by %s in %s', 
								$review_sno, $rev, $login['id'], $projectid
							);
							$email_message = $review_url . "\r\n" . $review_comment;
							$this->projects->emailMessageToMembers(
								$projectid, $this->login, $email_subject, $email_message
							);
						}
					}
				}
			}
		}

		print $status;
	}

	function xhr_edit_review_comment ($projectid = '', $rev = SVN_REVISOIN_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
		$this->load->model ('CodeModel', 'code');

		$login = $this->login->getUser();
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
			//else if (!$login['sysadmin?'] && 
			//         $this->projects->projectHasMember($projectid, $login['id']) === FALSE)
			else if (!$this->_can_write ($this->projects, $projectid, $login))
			{
				$status = "error - disallowed";
			}
			else
			{
				$review_no = $this->input->post('code_edit_review_no');
				$review_comment = $this->input->post('code_edit_review_comment');

				if ($review_no === FALSE || !is_numeric($review_no))
				{
					$status = 'error - wrong review number';
				}
				else if ($review_comment === FALSE || ($review_comment = trim($review_comment)) == '')
				{
					$status = 'error - empty review comment';
				}
				else
				{
					if ($this->code->updateReview ($projectid, $rev, (integer)$review_no, $login['id'], $review_comment, TRUE) === FALSE)
					{
						$status = 'error - ' . $this->code->getErrorMessage();
					}
					else
					{
						$status = 'ok';
						/*
						if (CODEPOT_COMMIT_REVIEW_NOTIFICATION)
						{
							// TODO: message localization
							$email_subject =  sprintf (
								'Edited review message #%d for r%d by %s in %s', 
								$review_sno, $rev, $login['id'], $projectid
							);
							$email_message = current_url() . "\r\n" . $review_comment;
							$this->projects->emailMessageToMembers (
								$projectid, $this->login, $email_subject, $email_message
							);
						}*/
					}
				}
			}
		}

		print $status;
	}


	function enjson_save ($projectid = '', $path = '')
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
	
		$login = $this->login->getUser ();
		$revision_saved = -1;

		if ($login['id'] == '')
		{
			$status = 'signin';
		}
		else if (($text = $this->input->post('text')) === FALSE)
		{
			$status = 'notext';
		}
		else if (($message = $this->input->post('message')) == FALSE)
		{
			$status = 'nomsg';
		}
		else
		{
			$path = $this->converter->HexToAscii ($path);
			if ($path == '.') $path = ''; /* treat a period specially */
			$path = $this->_normalize_path ($path);

			$project = $this->projects->get ($projectid);
			if ($project === FALSE)
			{
				$status = 'dberr';
			}
			else if ($project === NULL)
			{
				$status = 'noent';
			}
			else
			{
				if ($this->subversion->storeFile ($projectid, $path, $login['id'], $message, $text) === FALSE)
				{
					$status = 'repoerr - ' . $this->subversion->getErrorMessage();
				}
				else
				{
					$status = 'ok';
				}
			}
		}

		$result = array (
			'status' => $status
		);

		print codepot_json_encode ($result);
	}

	function history ($projectid = '', $path = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
		$this->load->model ('CodeModel', 'code');

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
		{
			$this->_redirect_to_signin($this->converter, $login);
			return;
		}
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
			//if ($project->public !== 'Y' && $login['id'] == '')
			if (!$this->_can_read ($this->projects, $projectid, $login))
			{
				// non-public projects require sign-in.
				$this->_redirect_to_signin($this->converter, $login, $project);
				return;
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

				if (array_key_exists('history', $file))
				{
					// Inject the codepot defined tag and review count
					foreach ($file['history'] as &$h)
					{
						if (array_key_exists('rev', $h))
						{
							$h['tag'] = $this->subversion->getRevProp ($projectid, $h['rev'], CODEPOT_SVN_TAG_PROPERTY);
							if ($h['tag'] === FALSE) $h['tag'] = '';

							$h['review_count'] = $this->code->countReviews ($projectid, $h['rev']);
							if ($h['review_count'] === FALSE) $h['review_count'] = 0;
						}
						else 
						{
							$h['tag'] = '';
							$h['review_count'] = 0;
						}
					}
				}

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
		$this->load->model ('CodeModel', 'code');

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
		{
			$this->_redirect_to_signin($this->converter, $login);
			return;
		}
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
		else if ($rev < 0)
		{
			$data['message'] = 'INVALID REVISION NUMBER';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			//if ($project->public !== 'Y' && $login['id'] == '')
			if (!$this->_can_read ($this->projects, $projectid, $login))
			{
				// non-public projects require sign-in.
				$this->_redirect_to_signin($this->converter, $login, $project);
				return;
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
				$r_rev = $rev;
				if ($r_rev < 0)
				{
					if (array_key_exists('history', $file))
					{
						$h = &$file['history'];
						if (array_key_exists('rev', $h)) $r_rev = $h['rev'];
					}
				}

				$related_issues = $this->code->getRelatedIssues ($projectid, $r_rev);
				if ($related_issues == FALSE) $related_issues = array();

				$reviews = $this->code->getReviews ($projectid, $r_rev);
				if ($reviews === FALSE)
				{
					$data['project'] = $project;
					$data['message'] = 'Failed to get code reviews';
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else
				{
					$prev_revision = $this->subversion->getPrevRev ($projectid, $path, $rev);

					if (array_key_exists('history', $file))
					{
						// Inject the codepot defined tag.
						$h = &$file['history'];
						if (array_key_exists('rev', $h))
						{
							$h['tag'] = $this->subversion->getRevProp ($projectid, $h['rev'], CODEPOT_SVN_TAG_PROPERTY);
							if ($h['tag'] === FALSE) $h['tag'] = '';
						}
						else $h['tag'] = '';

						foreach ($h['paths'] as &$chg)
						{
							if ($chg['action'] == 'A' || $chg['action'] == 'M' || $chg['action'] == 'R')
							{
								$props = $this->subversion->listProps ($projectid, $chg['path'], $h['rev']);
								if ($props === FALSE) $props = array ();
								else 
								{
									if (empty($props))
									{
										$props = array();
									}
									else
									{
										// get the first element in the associative array.
										foreach ($props as &$p) break; 
										$props = $p;
									}
								}

								$prev_props = $this->subversion->listProps ($projectid, $chg['path'], $prev_revision);
								if ($prev_props === FALSE) $prev_props = array ();
								else 
								{
									if (empty($prev_props))
									{
										$prev_props = array();
									}
									else
									{
										// get the first element in the associative array.
										foreach ($prev_props as &$p) break;
										$prev_props = $p;
									}
								}

								$chg['props'] = $props;
								$chg['prev_props'] = $prev_props;

								//print_r ($props);
								//print_r ($prev_props);
								//$common_props = array_intersect_assoc($props, $prev_props);
								//print_r (array_diff_assoc($props, $common_props)); // added
								//print_r (array_diff_assoc($prev_props, $common_props)); // deleted
							}
						}
					}

					$data['project'] = $project;
					$data['headpath'] = $path;
					$data['file'] = $file;
					$data['reviews'] = $reviews; 
					$data['related_issues'] = $related_issues;

					$data['revision'] = $rev;
					$data['prev_revision'] = $prev_revision;
					$data['next_revision'] = $this->subversion->getNextRev ($projectid, $path, $rev);

					$this->load->view ($this->VIEW_REVISION, $data);
				}
			}
		}
	}

	private function _do_diff ($projectid = '', $path = '', $rev1 = SVN_REVISION_HEAD, $rev2 = SVN_REVISION_HEAD, $full = FALSE)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
		{
			$this->_redirect_to_signin($this->converter, $login);
			return;
		}
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
			//if ($project->public !== 'Y' && $login['id'] == '')
			if (!$this->_can_read ($this->projects, $projectid, $login))
			{
				// non-public projects require sign-in.
				$this->_redirect_to_signin($this->converter, $login, $project);
				return;
			}

			$file = $this->subversion->getDiff ($projectid, $path, $rev1, $rev2, $full);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'Failed to get diff';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else if (($head_rev = $this->subversion->getHeadRev ($projectid, $path, $rev1)) === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = 'Failed to get head revision';
				$this->load->view ($this->VIEW_ERROR, $data);
			}
			else
			{
				$file['head_rev'] = $head_rev;
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
				$data['fullview'] = $full;
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

	protected function _clear_zip_residue ($filename)
	{
		$dir_name = $filename . '.d';
		$zip_name = $filename . '.zip';

		codepot_delete_files ($dir_name, TRUE);
		@unlink ($zip_name);
		@unlink ($filename);
	}

	function fetch ($projectid = '', $path = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');
	
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
		{
			$this->_redirect_to_signin($this->converter, $login);
			return;
		}
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
			//if ($project->public !== 'Y' && $login['id'] == '')
			if (!$this->_can_read ($this->projects, $projectid, $login))
			{
				// non-public projects require sign-in.
				$this->_redirect_to_signin($this->converter, $login, $project);
				return;
			}

			$file = $this->subversion->getFile ($projectid, $path, $rev);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = "Failed to get a file";
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
				$forced_name = $projectid . $file['fullpath'];
				$forced_name = str_replace ('/', '-', $forced_name);
				//$tag = $this->subversion->getRevProp (
					//	$projectid, $file['created_rev'], CODEPOT_SVN_TAG_PROPERTY);
				//if ($tag === FALSE) $tag = '';
				//if (!empty($tag)) 
				//{
				//	$forced_name = $forced_name . '-' . $tag;
				//}
				//else
				//{
					$forced_name = $forced_name . '-r' . $file['created_rev'];
				//}

				$filename = $this->subversion->zipSubdir ($projectid, $path, $rev, $forced_name);
				if ($filename === FALSE)
				{
					$data['project'] = $project;
					$data['message'] = "Failed to zip a directory";
					$this->load->view ($this->VIEW_ERROR, $data);
				}
				else
				{
					$dir_name = $filename . '.d';
					$zip_name = $filename . '.zip';

					$forced_zip_name = $forced_name . '.zip';

					// deleting residue files after @readfile() didn't
					// work reliably when file download has been
					// interrupted or cancelled. using the shutdown
					// hook seemed more reliable.
					@register_shutdown_function (array($this, '_clear_zip_residue'), $filename);

					header ('Content-Description: File Transfer');
					header ('Content-Type: application/zip');
					header ('Content-Disposition: attachment; filename='. $forced_zip_name);
					header ('Content-Transfer-Encoding: binary');
					header ('Content-Length: ' . filesize($zip_name));
					flush ();

					@readfile ($zip_name);
					// meaningless to show the error page after headers
					// have been sent event if readfile fails.

					exit (0); // it looks like the shutdown callback is not called without exit().
				}
			}
		}
	}

	private function _search_code ($project, $login)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');

		$data['login'] = $login;
		$data['message'] = '';

		$this->form_validation->set_rules ('search_string', 'pattern', 'required');
		$this->form_validation->set_rules ('search_folder', 'folder', '');
		$this->form_validation->set_rules ('search_revision', 'revision', 'numeric');
		// no rule for search_invertedly, search_case_insensitively, search_recursively, search_is_regex, search_in_name
		$this->form_validation->set_error_delimiters('<span class="form_field_error">','</span>');

		if ($this->input->post('search_string') !== FALSE)
		{
			$pattern =  $this->input->post('search_string');
			$path = $this->input->post('search_folder');
			$path = $this->_normalize_path ($path);
			$rev = $this->input->post('search_revision');
			$invertedly = $this->input->post('search_invertedly');
			$case_insensitively = $this->input->post('search_case_insensitively');
			$recursively = $this->input->post('search_recursively');
			$in_name = $this->input->post('search_in_name');
			$is_regex = $this->input->post('search_is_regex');
			$wildcard_pattern = $this->input->post('search_wildcard_pattern');

			$file = $this->subversion->getFile ($project->id, $path, $rev);
			if ($file === FALSE)
			{
				$data['project'] = $project;
				$data['message'] = "Failed to get file - $path";
				$this->load->view ($this->VIEW_ERROR, $data);
				return;
			}
			else if ($this->form_validation->run())
			{
				$data['project'] = $project;
				$data['headpath'] = $path;
				$data['pattern'] = $pattern;
				$data['invertedly'] = $invertedly;
				$data['case_insensitively'] = $case_insensitively;
				$data['recursively'] = $recursively;
				$data['in_name'] = $in_name;
				$data['is_regex'] = $is_regex;
				$data['wildcard_pattern'] = $wildcard_pattern;
				$data['file'] = $file;

				$data['revision'] = $rev;
				$data['prev_revision'] =
					$this->subversion->getPrevRev ($project->id, $path, $rev);
				$data['next_revision'] =
					$this->subversion->getNextRev ($project->id, $path, $rev);

				$this->load->view ($this->VIEW_SEARCH, $data);
				return;
			}
		}

		redirect ("code/file/" . $project->id);
	}

	function search ($projectid = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('SubversionModel', 'subversion');

		$login = $this->login->getUser ();
		if ((CODEPOT_SIGNIN_COMPULSORY || CODEPOT_SIGNIN_FOR_CODE_SEARCH) && $login['id'] == '')
		{
			$this->_redirect_to_signin($this->converter, $login);
			return;
		}

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
			//if ($project->public !== 'Y' && $login['id'] == '')
			if (!$this->_can_read ($this->projects, $projectid, $login))
			{
				// non-public projects require sign-in.
				$this->_redirect_to_signin($this->converter, $login, $project);
				return 0;
			}

			$this->_search_code ($project, $login);
		}
	}

	private function _normalize_path ($path)
	{
		$path = preg_replace('/[\/]+/', '/', $path);
		if ($path == '/') $path = '';
		return $path;
	}
}
