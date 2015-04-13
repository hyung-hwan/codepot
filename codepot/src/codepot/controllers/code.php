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

					$file['created_tag'] = $this->subversion->getRevProp ($projectid, $file['created_rev'], CODEPOT_SVN_TAG_PROPERTY);
					if ($file['created_tag'] === FALSE) $file['created_tag'] = '';

					$file['head_tag'] = $this->subversion->getRevProp ($projectid, $file['head_rev'], CODEPOT_SVN_TAG_PROPERTY);
					if ($file['head_tag'] === FALSE) $file['head_tag'] = '';


					$data['project'] = $project;
					$data['headpath'] = $path;
					$data['file'] = $file; 
					$data['revision'] = $rev;


					$this->load->view ($this->VIEW_FILE, $data);
				}
			}
			else
			{
				$file['created_tag'] = $this->subversion->getRevProp ($projectid, $file['created_rev'], CODEPOT_SVN_TAG_PROPERTY);
				if ($file['created_tag'] === FALSE) $file['created_tag'] = '';

				$data['project'] = $project;
				$data['headpath'] = $path;
				$data['file'] = $file;

				$data['revision'] = $rev;
				$data['prev_revision'] =
					$this->subversion->getPrevRev ($projectid, $path, $rev);
				$data['next_revision'] =
					$this->subversion->getNextRev ($projectid, $path, $rev);

				$data['readme_text'] = '';
				$data['readme_file'] = '';
				foreach (explode(',', CODEPOT_CODE_FOLDER_README) as $rf)
				{
					$rf = trim($rf);
					if (strlen($rf) > 0)
					{
						$readme = $this->subversion->getFile ($projectid, $path . '/' . $rf, $rev);
						if ($readme !== FALSE)
						{
							$data['readme_text'] = $readme['content'];
							$data['readme_file'] = $rf;
							break;
						}
					}
				}
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

					$file['created_tag'] = $this->subversion->getRevProp ($projectid, $file['created_rev'], CODEPOT_SVN_TAG_PROPERTY);
					if ($file['created_tag'] === FALSE) $file['created_tag'] = '';

					$file['head_tag'] = $this->subversion->getRevProp ($projectid, $file['head_rev'], CODEPOT_SVN_TAG_PROPERTY);
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
				if (array_key_exists('history', $file))
				{
					// Inject the codepot defined tag.
					foreach ($file['history'] as &$h)
					{
						if (array_key_exists('rev', $h))
						{
							$h['tag'] = $this->subversion->getRevProp ($projectid, $h['rev'], CODEPOT_SVN_TAG_PROPERTY);
							if ($h['tag'] === FALSE) $h['tag'] = '';
						}
						else $h['tag'] = '';
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
		$this->load->model ('CodeReviewModel', 'code_review');
	
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

			$data['popup_error_message'] = '';
			if ($login['id'] != '')
			{
				$tag = $this->input->post('tag_revision');
				if ($tag !== FALSE)
				{
					$tag = trim($tag);
					if (empty($tag)) 
					{
						// delete the tag if the value is empty
						$affected_rev = $this->subversion->killRevProp (
							$projectid, $rev, CODEPOT_SVN_TAG_PROPERTY, $login['id']);
					}
					else
					{
						$affected_rev = $this->subversion->setRevProp (
							$projectid, $rev, CODEPOT_SVN_TAG_PROPERTY, $tag, $login['id']);
					}
					if ($affected_rev === FALSE)
					{
						$data['popup_error_message'] = 'Cannot tag revision';
					}
					else 
					{
						$this->form_validation->_field_data = array();
					}
				}
				else if ($login['id'] == $this->subversion->getRevProp($projectid, $rev, 'svn:author') &&
				         $this->input->post('edit_log_message'))
				{
					// the current user must be the author of the revision to be able to 
					// change the log message.
					$this->load->helper ('form');
					$this->load->library ('form_validation');
	
					$this->form_validation->set_rules ('edit_log_message', 'Message', 'required|min_length[2]');
					$this->form_validation->set_error_delimiters('<span class="form_field_error">','</span>');
	
					if ($this->form_validation->run())
					{
						$logmsg = $this->input->post('edit_log_message');
						if ($logmsg != $this->subversion->getRevProp ($projectid, $rev, 'svn:log'))
						{
							$affected_rev = $this->subversion->setRevProp (
								$projectid, $rev, 'svn:log', $logmsg, $login['id']);
							if ($affected_rev === FALSE)
							{
								$data['popup_error_message'] = 'Cannot change revision log message';
							}
							else 
							{
								$this->form_validation->_field_data = array();
							}
						}
					}
					else
					{
						$data['popup_error_message'] = 'Invalid revision log message';
					}
				}
				else if ($this->input->post('new_review_comment'))
				{
					$this->load->helper ('form');
					$this->load->library ('form_validation');
	
					$this->form_validation->set_rules ('new_review_comment', $this->lang->line('Comment'), 'required|min_length[10]');
					$this->form_validation->set_error_delimiters('<span class="form_field_error">','</span>');
	
					if ($this->form_validation->run())
					{
						$review_comment = $this->input->post('new_review_comment');
						if ($this->code_review->insertReview ($projectid, $rev, $login['id'], $review_comment) === FALSE)
						{
							$data['popup_error_message'] = 'Cannot add code review comment';
						}
						else
						{
							// this is a hack to clear form data upon success
							$this->form_validation->_field_data = array();
						}
					}
					else
					{
						$data['popup_error_message'] = 'Invalid review comment';
					}
				}
				else if ($this->input->post('edit_review_comment_no'))
				{
					$this->load->helper ('form');
					$this->load->library ('form_validation');

					// get the comment number without validation.
					$comment_no = $this->input->post('edit_review_comment_no');
					if (is_numeric($comment_no))
					{
						$comment_field_name = "edit_review_comment_{$comment_no}";
						$this->form_validation->set_rules ($comment_field_name, $this->lang->line('Comment'), 'required|min_length[10]');
						$this->form_validation->set_error_delimiters('<span class="form_field_error">','</span>');
	
						if ($this->form_validation->run())
						{
							//
							// TODO: should let sysadmin? to change comments???
							//
							$review_comment = $this->input->post($comment_field_name);
							if ($this->code_review->updateReview ($projectid, $rev, (integer)$comment_no, $login['id'], $review_comment, TRUE) === FALSE)
							{
								$data['popup_error_message'] = 'Cannot edit code review comment';
							}
							else
							{
								// this is a hack to clear form data upon success
								$this->form_validation->_field_data = array();
							}
						}
						else
						{
							$data['popup_error_message'] = 'Invalid review comment';
						}
					}
					else
					{
						$data['popup_error_message'] = 'Invalid review comment number';
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
				$reviews = $this->code_review->getReviews ($projectid, $rev);
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
	
					$data['revision'] = $rev;
					$data['prev_revision'] = $prev_revision;
					$data['next_revision'] = $this->subversion->getNextRev ($projectid, $path, $rev);

					$this->load->view ($this->VIEW_REVISION, $data);
				}
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

				$data['readme_text'] = '';
				$data['readme_file'] = '';
				foreach (explode(',', CODEPOT_CODE_FOLDER_README) as $rf)
				{
					$rf = trim($rf);
					if (strlen($rf) > 0)
					{
						$readme = $this->subversion->getFile ($projectid, $path . '/' . $rf, $rev);
						if ($readme !== FALSE)
						{
							$data['readme_text'] = $readme['content'];
							$data['readme_file'] = $rf;
							break;
						}
					}
				}
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

				$data['readme_text'] = '';
				$data['readme_file'] = '';
				foreach (explode(',', CODEPOT_CODE_FOLDER_README) as $rf)
				{
					$rf = trim($rf);
					if (strlen($rf) > 0)
					{
						$readme = $this->subversion->getFile ($projectid, $path . '/' . $rf, $rev);
						if ($readme !== FALSE)
						{
							$data['readme_text'] = $readme['content'];
							$data['readme_file'] = $rf;
							break;
						}
					}
				}
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

	function graph ($type = '', $projectid = '', $path = '', $rev = SVN_REVISION_HEAD)
	{
		$this->load->model ('ProjectModel', 'projects');

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
		{
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found'); 
			return;
		}

		$project = $this->projects->get ($projectid);
		if ($project === FALSE || ($project->public !== 'Y' && $login['id'] == ''))
		{
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found'); 
			return;
		}

		$this->load->model ('SubversionModel', 'subversion');

		$path = $this->converter->HexToAscii ($path);
		if ($path == '.') $path = ''; /* treat a period specially */
		$path = $this->_normalize_path ($path);


		if ($type == 'cloc-file')
		{
			// number of lines in a single file

			$file = $this->subversion->getFile ($projectid, $path, $rev);
			if ($file === FALSE)
			{
				header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error'); 
				return;
			}

			if ($file['type'] != 'file')
			{
				header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error'); 
				return;
			}

			// pass __FILE__ as the first argument so that tempnam creates a name
			// in the system directory. __FILE__ can never be a valid directory.
			$tfname = @tempnam(__FILE__, 'codepot-cloc-'); 
			if ($tfname === FALSE)
			{
				header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error'); 
				return;
			}

			$actual_tfname = $tfname . '.' . pathinfo ($file['name'], PATHINFO_EXTENSION);
			@file_put_contents ($actual_tfname, $file['content']);

			$cloc_cmd = sprintf ('%s --quiet --csv --csv-delimiter=":" %s', CODEPOT_CLOC_COMMAND_PATH, $actual_tfname);
			$cloc = @popen ($cloc_cmd, 'r');
			if ($cloc === FALSE)
			{
				@unlink ($tfname);
				@unlink ($actual_tfname);
				header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error'); 
				return;
			}

			$line_count = 0;
			$counter = FALSE;
			while (!feof($cloc))
			{
				$line = @fgets ($cloc);
				if ($line === FALSE) break;

				if ($line_count == 2)
				{
					$counter = explode (':', $line);
				}
				$line_count++;
			} 

			@pclose ($cloc);
			@unlink ($tfname);
			@unlink ($actual_tfname);

			if ($counter === FALSE)
			{
				$stats = array (
					'no-data' => 0
				);
				$title = $file['name'];
			}
			else
			{
				$stats = array (
					'blank' => (integer)$counter[2],
					'comment' => (integer)$counter[3],
					'code' => (integer)$counter[4],
					'total' => (integer)$counter[2] + (integer)$counter[3] + (integer)$counter[4]
				);

				$title = $file['name'] . ' (' . $counter[1] . ')';
			}

			$this->load->library ('PHPGraphLib', array ('width' => 280, 'height' => 200), 'graph');
			$this->graph->addData($stats);
			$this->graph->setTitle($title);
			$this->graph->setDataPoints(TRUE);
			$this->graph->setDataValues(TRUE);
			$this->graph->setBars(TRUE);
			$this->graph->setXValuesHorizontal(TRUE);
			$this->graph->setYValues (FALSE);
			$this->graph->createGraph();
		}
		else if ($type == 'commits-per-month')
		{
			$total_commits = 0;
			$average_commits = 0;
			$total_months = 0;

			$file = $this->subversion->getHistory ($projectid, $path, SVN_REVISION_HEAD);
			if ($file === FALSE)
			{
				//header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error'); 
				//return;
				$stats = array ('no-data' => 0);
			}
			else 
			{
	
				$history = $file['history'];
				$history_count = count($history);
	
				$stats = array();
				for ($i = 0; $i < $history_count; $i++)
				{
					$h = $history[$i];
					if (array_key_exists ('date', $h)) 
					{
						$date = substr($h['date'], 0, 7);
						if (array_key_exists ($date, $stats))
							$stats[$date]++;
						else 
							$stats[$date] = 1;
					}
				}
	
	
				ksort ($stats);
				$stats_count = count($stats);
				$idx = 1;
				foreach ($stats as $k => $v)
				{
					if ($idx == 1) 
					{
						$min_year = substr($k, 0, 4);
						$min_month = substr($k, 5, 2);
					}
	
					if ($idx == $stats_count) 
					{
						$max_year = substr($k, 0, 4);
						$max_month = substr($k, 5, 2);
					}
	

					$idx++;	
					$total_commits += $v;
				}	
	
				$total_months  = 0; 
				for ($year = $min_year; $year <= $max_year; $year++)
				{
					$month = ($year == $min_year)? $min_month: 1;
					$month_end = ($year == $max_year)? $max_month: 12;
	
					while ($month <= $month_end)
					{
						$date = sprintf ("%04d-%02d", $year, $month);
	
						if (!array_key_exists ($date, $stats)) 
						{
							// fill the holes
							$stats[$date] = 0;
						}

						$month++;
						$total_months++;
					}
				}

				if ($total_months > 0) $average_commits = $total_commits / $total_months;
			}

			ksort ($stats);
			$stats_count = count($stats);

			$graph_width = $stats_count * 8;
			if ($graph_width < 400) $graph_width = 400;
			$this->load->library ('PHPGraphLib', array ('width' => $graph_width, 'height' => 180), 'graph');
			$this->graph->addData($stats);
			$this->graph->setTitle("Commits per month ({$total_commits}/{$total_months})");
			$this->graph->setDataPoints(FALSE);
			$this->graph->setDataValues(FALSE);
			$this->graph->setLine(FALSE);
			$this->graph->setLineColor("red");
			$this->graph->setBars(TRUE);
			$this->graph->setBarOutline (TRUE);
			$this->graph->setBarColor ("#EEEEEE");
			$this->graph->setBarOutlineColor ("#AAAAAA");

			$this->graph->setXValues(TRUE);
			$this->graph->setXValuesHorizontal(TRUE);
			if ($stats_count <= 1)
			{
					$this->graph->setBarSpace(TRUE);
					//$this->graph->setDataPoints(TRUE);
					//$this->graph->setDataPointColor("red");
			}
			else
			{
					$this->graph->setBarSpace(FALSE);

					if ($stats_count <= 8)
					{
						$this->graph->setXValuesInterval(1);
					}
					else if ($stats_count <= 16)
					{
						$this->graph->setXValuesInterval(2);
					}
					else
					{
						$this->graph->setXValuesInterval(11);
					}
			}
			//$this->graph->setGrid(FALSE);
			$this->graph->setGridVertical(FALSE);
			$this->graph->setGridHorizontal(TRUE);
			if ($total_months > 0) $this->graph->setGoalLine ($average_commits, "red", "solid");
			$this->graph->createGraph();
		}
		else if ($type == 'commit-share-by-users')
		{
			// revision is ignored
			$file = $this->subversion->getHistory ($projectid, $path, SVN_REVISION_HEAD);
			if ($file === FALSE)
			{
				header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error'); 
				return;
			}

			$history = $file['history'];
			$history_count = count($history);

			$stats = array();
			for ($i = 0; $i < $history_count; $i++)
			{
				$h = $history[$i];
				$author = (array_key_exists ('author', $h))? $h['author']: '?';

				if (array_key_exists ($author, $stats))
					$stats[$author]++;
				else 
					$stats[$author] = 1;
			}

			$this->load->library ('PHPGraphLibPie', array ('width' => 400, 'height' => 300), 'graph');
			$this->graph->addData($stats);
			$this->graph->setTitle('Commit share by users');
			$this->graph->setLabelTextColor('50,50,50');
			$this->graph->setLegendTextColor('50,50,50');
			$this->graph->createGraph();
		}
		else /* if ($type == 'commits-by-users') */
		{
			// revision is ignored
			$file = $this->subversion->getHistory ($projectid, $path, SVN_REVISION_HEAD);
			if ($file === FALSE)
			{
				header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error'); 
				return;
			}

			$history = $file['history'];
			$history_count = count($history);

			$stats = array();
			for ($i = 0; $i < $history_count; $i++)
			{
				$h = $history[$i];
				$author = (array_key_exists ('author', $h))? $h['author']: '?';

				if (array_key_exists ($author, $stats))
					$stats[$author]++;
				else 
					$stats[$author] = 1;
			}

			$this->load->library ('PHPGraphLib', array ('width' => 400, 'height' => 300), 'graph');
			$this->graph->addData($stats);
			$this->graph->setTitle('Commits by users');
			$this->graph->setDataPoints(TRUE);
			$this->graph->setDataValues(TRUE);
			//$this->graph->setLine(TRUE);
			$this->graph->setBars(TRUE);
			//$this->graph->setXValuesHorizontal(TRUE);
			$this->graph->createGraph();
		}
	}

}
