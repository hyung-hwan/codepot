<?php

$CI = &get_instance();
$CI->load->model('CodeRepoModel');

class SubversionModel extends CodeRepoModel
{
	function __construct ()
	{
		parent::__construct ();
	}

	private function _canonical_path($path) 
	{
		$canonical = preg_replace('|/\.?(?=/)|','',$path);
		while (($collapsed = preg_replace('|/[^/]+/\.\./|','/',$canonical,1)) !== $canonical) 
		{
			$canonical = $collapsed;
		}
		$canonical = preg_replace('|^/\.\./|','/',$canonical);

		if ($canonical != '/' && substr($canonical, -1) == '/') 
		{
			// if the last character is / and it's not the only character, remove it
			$canonical = substr($canonical, 0, -1);
		}
		return $canonical;
	}

	function getFile ($projectid, $path, $rev = SVN_REVISION_HEAD, $type_and_name_only = FALSE)
	{
		//$url = 'file://'.CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}";
		$orgurl = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");

		$workurl = ($path == '')? $orgurl: "{$orgurl}@"; // trailing @ for collision prevention
		$info = @svn_info($workurl, FALSE, $rev);
		if ($info === FALSE || $info === NULL ||  count($info) != 1) 
		{
			// If a URL is at the root of repository and the url type is file://
			// (e.g. file:///svnrepo/codepot/ where /svnrepo/codepot is a project root),
			// some versions of libsvn end up with an assertion failure like
			//  ... libvvn_subr/path.c:114: svn_path_join: Assertion `svn_path_is_canonical(base, pool)' failed. 
			// 
			// Since the root directory is guaranteed to exist at the head revision,
			// the information can be acquired without using a peg revision.
			// In this case, a normal operational revision is used to work around
			// the assertion failure. Other functions that has to deal with 
			// the root directory should implement this check to work around it.
			//
			if ($rev == SVN_REVISION_HEAD || $path == '') return FALSE;

			// rebuild the URL with a peg revision and retry it.
			$workurl = "{$orgurl}@{$rev}";
			$info = @svn_info($workurl, FALSE, $rev);
			if ($info === FALSE || $info === NULL || count($info) != 1)  return FALSE;
		}

		$info0 = &$info[0];
		if ($info0['kind'] == SVN_NODE_FILE) 
		{
			$lsinfo = @svn_ls ($workurl, $rev, FALSE, TRUE);
			if ($lsinfo === FALSE) return FALSE;

			if (array_key_exists ($info0['path'], $lsinfo) === FALSE) return FALSE;
			$fileinfo = $lsinfo[$info0['path']];

			if ($type_and_name_only)
			{
				$str = '';
				$fileinfo['properties'] = NULL;
				$fileinfo['logmsg'] = '';
			}
			else
			{
				$str = @svn_cat ($workurl, $rev);
				if ($str === FALSE) return FALSE;

				$log = @svn_log ($workurl, 
					$fileinfo['created_rev'], 
					$fileinfo['created_rev'],
					1, SVN_DISCOVER_CHANGED_PATHS);
				if ($log === FALSE) return FALSE;

				$prop = @svn_proplist ($workurl, FALSE, $rev);
				if ($prop === FALSE) return FALSE;

				//$fileinfo['properties'] = array_key_exists($orgurl, $prop)?  $prop[$orgurl]: NULL;
				$fileinfo['properties'] = NULL;
				foreach ($prop as $k => $v)
				{
					if ($k == $orgurl || $k == $workurl) 
					{
						$fileinfo['properties'] = $v;
						break;
					}
					else 
					{
						// it looks like the subversion module returns a URL-encoded
						// path when it contains a whitespace and the revision is given.
						// for example, "UOML SAMPLE.ODT" is returned as "UOML%20SAMPLE.ODT" 
						// when revision is specified. let's work around it.
						$decurl = urldecode($k);
						if ($decurl == $orgurl || $decurl == $workurl)  
						{
							$fileinfo['properties'] = $v;
							break;
						}
					}
				}

				$fileinfo['logmsg'] = (count($log) > 0)? $log[0]['msg']: '';
			}

			$fileinfo['fullpath'] = substr ($info0['url'], strlen($info0['repos']));
			$fileinfo['content'] = $str;
 
			return $fileinfo;
		}
		else if ($info0['kind'] == SVN_NODE_DIR) 
		{
			$list = @svn_ls ($workurl, $rev, FALSE, TRUE);
			if ($list === FALSE) return FALSE;

			$rev_key = 'last_changed_rev';
			if (array_key_exists($rev_key, $info0) === FALSE) $rev_key = 'revision';
			// assume either 'last_changed_rev' or 'revision' exists

			if ($type_and_name_only)
			{
				$fileinfo['properties'] = NULL;
				$fileinfo['logmsg'] = '';
			}
			else
			{
				if ($info0[$rev_key] <= 0) 
				{
					$log = array();
				}
				else
				{
					$log = @svn_log ($workurl, 
						$info0[$rev_key], $info0[$rev_key],
						1, SVN_DISCOVER_CHANGED_PATHS);
					if ($log === FALSE) return FALSE;
				}

				$prop = @svn_proplist ($workurl, FALSE, $rev);
				if ($prop === FALSE) return FALSE;

				//$fileinfo['properties'] = array_key_exists($orgurl, $prop)?  $prop[$orgurl]: NULL;
				$fileinfo['properties'] = NULL;
				foreach ($prop as $k => $v)
				{
					if ($k == $orgurl || $k == $workurl) 
					{
						$fileinfo['properties'] = $v;
						break;
					}
					else 
					{
						$decurl = urldecode($k);
						if ($decurl == $orgurl || $decurl == $workurl)  
						{
							$fileinfo['properties'] = $v;
							break;
						}
					}
				}

				$fileinfo['logmsg'] = (count($log) > 0)? $log[0]['msg']: '';
			}

			$fileinfo['fullpath'] = substr ($info0['url'], strlen($info0['repos']));
			$fileinfo['name'] =  $info0['path'];
			$fileinfo['type'] = 'dir';
			$fileinfo['size'] = 0;
			$fileinfo['created_rev'] = $info0[$rev_key];
			if (array_key_exists ('last_changed_author', $info0) === FALSE)
				$fileinfo['last_author'] = '';
			else
				$fileinfo['last_author'] = $info0['last_changed_author'];

			if (array_key_exists ('last_changed_date', $info0) === FALSE)
				$fileinfo['last_changed_date'] = '';
			else
				$fileinfo['last_changed_date'] = $info0['last_changed_date'];

			$fileinfo['content'] = $list;
			return $fileinfo;
		}

		return FALSE;
	}

	function getBlame ($projectid, $path, $rev = SVN_REVISION_HEAD)
	{
		//$url = 'file:///'.CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}";
		$orgurl = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");

		$workurl = ($path == '')? $orgurl: "{$orgurl}@"; // trailing @ for collision prevention
		$info = @svn_info($workurl, FALSE, $rev);
		if ($info === FALSE || $info === NULL || count($info) != 1) 
		{
			if ($rev == SVN_REVISION_HEAD || $path == '') return FALSE;
			$workurl = "{$orgurl}@{$rev}";
			$info = @svn_info($workurl, FALSE, $rev);
			if ($info === FALSE || $info === NULL || count($info) != 1)  return FALSE;
		}

		$info0 = $info[0];
		if ($info0['kind'] != SVN_NODE_FILE) return FALSE;

		$lsinfo = @svn_ls ($workurl, $rev, FALSE, TRUE);
		if ($lsinfo === FALSE) return FALSE;

		if (array_key_exists ($info0['path'], $lsinfo) === FALSE) return FALSE;
		$fileinfo = $lsinfo[$info0['path']];

		$str = @svn_blame ($workurl, $rev);
		if ($str === FALSE) return FALSE;

		$prop = @svn_proplist ($workurl, FALSE, $rev);
		if ($prop === FALSE) return FALSE;
		//$fileinfo['properties'] = array_key_exists($orgurl, $prop)?  $prop[$orgurl]: NULL;
		$fileinfo['properties'] = NULL;
		foreach ($prop as $k => $v)
		{
			if ($k == $orgurl || $k == $workurl) 
			{
				$fileinfo['properties'] = $v;
				break;
			}
			else 
			{
				$decurl = urldecode($k);
				if ($decurl == $orgurl || $decurl == $workurl)  
				{
					$fileinfo['properties'] = $v;
					break;
				}
			}
		}

		$log = @svn_log ($workurl, 
			$fileinfo['created_rev'], 
			$fileinfo['created_rev'],
			1, SVN_DISCOVER_CHANGED_PATHS);
		if ($log === FALSE) return FALSE;

		$fileinfo['fullpath'] = substr (
			$info0['url'], strlen($info0['repos']));
		$fileinfo['content'] = $str;
		$fileinfo['logmsg'] = (count($log) > 0)? $log[0]['msg']: '';
		return $fileinfo;
	}

	function getHistory ($projectid, $path, $rev = SVN_REVISION_HEAD)
	{
		//$url = 'file:///'.CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}";
		$url = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");

		if ($rev == SVN_REVISION_HEAD)
		{
			$info = @svn_info($url, FALSE, $rev);
			if ($info === FALSE || $info === NULL || count($info) != 1)  return FALASE;
		}
		else
		{
			$orgrev = $rev;
			$rev = SVN_REVISION_HEAD;

			//
			// Try to get the history from the head revision down.
			// regardless of the given revision.
			//
			$info = @svn_info($url, FALSE, $rev);
			if ($info === FALSE || $info === NULL || count($info) != 1) 
			{
				//
				// Try further with the original revision 
				//
				$rev = $orgrev;
				$info = @svn_info ($url, FALSE, $rev);
				if ($info === FALSE || $info === NULL || count($info) != 1) 
				{
					//
					// don't try with a pegged url for a project root 
					//
					if ($path == '') return FALSE; 

					//
					// Retry with a pegged url 
					//
					$url = $url . '@' . $rev;
					$info = @svn_info($url, FALSE, $rev);
					if ($info === FALSE || $info === NULL || count($info) != 1) return FALSE;
				}
			}
		}

		if ($info[0]['kind'] == SVN_NODE_FILE) 
		{
			$lsinfo = @svn_ls ($url, $rev, FALSE);
			if ($lsinfo === FALSE) return FALSE;

			if (array_key_exists ($info[0]['path'], $lsinfo) === FALSE) return FALSE;
			$fileinfo = $lsinfo[$info[0]['path']];
		}
		else if ($info[0]['kind'] == SVN_NODE_DIR)
		{
			$fileinfo['fullpath'] = substr (
				$info[0]['url'], strlen($info[0]['repos']));
			$fileinfo['name'] =  $info[0]['path'];
			$fileinfo['type'] = 'dir';
			$fileinfo['size'] = 0;
			if (array_key_exists('last_changed_rev', $info[0]))
				$fileinfo['created_rev'] = $info[0]['last_changed_rev'];
			else
				$fileinfo['created_rev'] = $info[0]['revision'];

			if (array_key_exists('last_changed_author', $info[0]))
				$fileinfo['last_author'] = $info[0]['last_changed_author'];
			else
				$fileinfo['last_author'] = '';
		}
		else return FALSE;

		$log = @svn_log ($url, 1, $rev, 0, SVN_DISCOVER_CHANGED_PATHS);
		if ($log === FALSE) return FALSE;

		$fileinfo['history'] = $log;
		return $fileinfo;
	}

	function storeFile ($projectid, $path, $committer, $commit_message, $text)
	{
		$this->clearErrorMessage ();

		//$url = 'file://'.CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}";
		$canon_path = $this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");
		$canon_dir = dirname($canon_path);
		$file_name = basename($canon_path);
		$dirurl = 'file://' . $canon_dir;

		set_error_handler (array ($this, 'capture_error'));
		$tfname = @tempnam(__FILE__, 'codepot-store-file-');
		restore_error_handler ();
		if ($tfname === FALSE) 
		{
			return FALSE;
		}

		$actual_tfname = $tfname . '.d';
		codepot_delete_files ($actual_tfname, TRUE); // delete the directory in case it exists

/* TODO: optimize it not to get all files... svn_checkout needs to be enhanced???*/
		set_error_handler (array ($this, 'capture_error'));
		if (@svn_auth_set_parameter (SVN_AUTH_PARAM_DEFAULT_USERNAME, $committer) === FALSE ||
		    @svn_checkout ($dirurl, $actual_tfname, SVN_REVISION_HEAD, SVN_NON_RECURSIVE) === FALSE ||
		    @file_put_contents ("{$actual_tfname}/{$file_name}", $text) === FALSE ||
		    ($result = @svn_commit ($commit_message, $actual_tfname)) === FALSE)
		{
			restore_error_handler ();
			codepot_delete_files ($actual_tfname, TRUE);
			@unlink ($tfname);
			return FALSE;
		}
		restore_error_handler ();
		codepot_delete_files ($actual_tfname, TRUE); // delete the directory in case it exists
		@unlink ($tfname);
		return TRUE;
	}

	function importFiles ($projectid, $path, $committer, $commit_message, $files, $uploader)
	{
		$this->clearErrorMessage ();

		//$url = 'file://'.CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}";
		$full_path = CODEPOT_SVNREPO_DIR."/{$projectid}";
		if (strlen($path) > 0) $full_path .= "/{$path}";
		$canon_path = $this->_canonical_path($full_path);
		$dirurl = 'file://' . $canon_path;

		set_error_handler (array ($this, 'capture_error'));
		$tfname = @tempnam(__FILE__, 'codepot-import-files-');
		restore_error_handler ();
		if ($tfname === FALSE) 
		{
			return FALSE;
		}

		$actual_tfname = $tfname . '.d';
		codepot_delete_files ($actual_tfname, TRUE); // delete the directory in case it exists

		set_error_handler (array ($this, 'capture_error'));
		if (@mkdir ($actual_tfname) === FALSE ||
		    @svn_auth_set_parameter (SVN_AUTH_PARAM_DEFAULT_USERNAME, $committer) === FALSE ||
		    @svn_checkout ($dirurl, $actual_tfname, SVN_REVISION_HEAD, 0) === FALSE)
		{
			restore_error_handler ();
			codepot_delete_files ($actual_tfname, TRUE);
			@unlink ($tfname);
			return FALSE;
		}

		foreach ($files as $f)
		{
			$xname = $actual_tfname . '/' . $f['name'];
			if ($f['type'] == 'dir')
			{
				if (@mkdir($xname) === FALSE ||
				    @svn_add ($xname, TRUE, TRUE) === FALSE)
				{
					restore_error_handler ();
					codepot_delete_files ($actual_tfname, TRUE);
					@unlink ($tfname);
					return FALSE;
				}
			}
			else if ($f['type'] == 'empfile')
			{
				if (@touch($xname) === FALSE ||
				    @svn_add ($xname, TRUE, TRUE) === FALSE)
				{
					restore_error_handler ();
					codepot_delete_files ($actual_tfname, TRUE);
					@unlink ($tfname);
					return FALSE;
				}
			}
			else if ($f['type'] == 'file')
			{
				$config['allowed_types'] = '*';
				$config['max_size'] = 0;
				$config['overwrite'] = FALSE;
				
				$config['remove_spaces'] = FALSE;
				$config['encrypt_name'] = TRUE;

				if (strcasecmp($f['unzip'], 'yes') == 0 && 
				    strcasecmp(substr($f['name'], -4), '.zip') == 0)
				{
					$unzip = TRUE;
					if (function_exists('sys_get_temp_dir'))
						$config['upload_path'] = sys_get_temp_dir();
					else
						$config['upload_path'] = '/tmp';
				}
				else
				{
					$unzip = FALSE;
					$config['upload_path'] = $actual_tfname;
				}

				$uploader->initialize ($config);

				if (!$uploader->do_upload($f['fid']))
				{
					restore_error_handler ();
					codepot_delete_files ($actual_tfname, TRUE);
					@unlink ($tfname);
					return FALSE;
				}

				$ud = $uploader->data();

				if ($unzip)
				{
					$x = codepot_unzip_file ($actual_tfname, $ud['full_path']);
					@unlink ($ud['full_path']);
					if ($x === FALSE)
					{
						restore_error_handler ();
						codepot_delete_files ($actual_tfname, TRUE);
						@unlink ($tfname);
						return FALSE;
					}

					foreach ($x as $y)
					{
						if (@svn_add ($actual_tfname . '/' . $y, TRUE, TRUE) === FALSE)
						{
							restore_error_handler ();
							codepot_delete_files ($actual_tfname, TRUE);
							@unlink ($tfname);
							return FALSE;
						}
					}
				}
				else
				{
					@rename ($ud['full_path'], $xname);
					if (@svn_add ($xname, TRUE, TRUE) === FALSE)
					{
						restore_error_handler ();
						codepot_delete_files ($actual_tfname, TRUE);
						@unlink ($tfname);
						return FALSE;
					}
				}
			}

			// ignore other types 
		}

		if (($result = @svn_commit ($commit_message, $actual_tfname)) === FALSE)
		{
			restore_error_handler ();
			codepot_delete_files ($actual_tfname, TRUE);
			@unlink ($tfname);
			return FALSE;
		}

		restore_error_handler ();
		codepot_delete_files ($actual_tfname, TRUE); // delete the directory in case it exists
		@unlink ($tfname);
		return TRUE;
	}

	function deleteFiles ($projectid, $path, $committer, $commit_message, $files)
	{
		$this->clearErrorMessage ();

		//$url = 'file://'.CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}";
		$full_path = CODEPOT_SVNREPO_DIR."/{$projectid}";
		if (strlen($path) > 0) $full_path .= "/{$path}";
		$canon_path = $this->_canonical_path($full_path);
		$dirurl = 'file://' . $canon_path;

		set_error_handler (array ($this, 'capture_error'));
		$tfname = @tempnam(__FILE__, 'codepot-delete-files-');
		restore_error_handler ();
		if ($tfname === FALSE) 
		{
			return FALSE;
		}

		$actual_tfname = $tfname . '.d';
		codepot_delete_files ($actual_tfname, TRUE); // delete the directory in case it exists

		set_error_handler (array ($this, 'capture_error'));
		if (@mkdir ($actual_tfname) === FALSE ||
		    @svn_auth_set_parameter (SVN_AUTH_PARAM_DEFAULT_USERNAME, $committer) === FALSE ||
		    @svn_checkout ($dirurl, $actual_tfname, SVN_REVISION_HEAD, 0) === FALSE)
		{
			restore_error_handler ();
			codepot_delete_files ($actual_tfname, TRUE);
			@unlink ($tfname);
			return FALSE;
		}

		foreach ($files as $f)
		{
			$xname = $actual_tfname . '/' . $f;

			if (@svn_delete ($xname, TRUE) === FALSE)
			{
				restore_error_handler ();
				codepot_delete_files ($actual_tfname, TRUE);
				@unlink ($tfname);
				return FALSE;
			}
		}

		if (($result = @svn_commit ($commit_message, $actual_tfname)) === FALSE)
		{
			restore_error_handler ();
			codepot_delete_files ($actual_tfname, TRUE);
			@unlink ($tfname);
			return FALSE;
		}

		restore_error_handler ();
		codepot_delete_files ($actual_tfname, TRUE); // delete the directory in case it exists
		@unlink ($tfname);
		return TRUE;
	}

	function renameFiles ($projectid, $path, $committer, $commit_message, $files)
	{
		$this->clearErrorMessage ();

		//$url = 'file://'.CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}";
		$full_path = CODEPOT_SVNREPO_DIR."/{$projectid}";
		if (strlen($path) > 0) $full_path .= "/{$path}";
		$canon_path = $this->_canonical_path($full_path);
		$dirurl = 'file://' . $canon_path;

		set_error_handler (array ($this, 'capture_error'));
		$tfname = @tempnam(__FILE__, 'codepot-rename-files-');
		restore_error_handler ();
		if ($tfname === FALSE) 
		{
			return FALSE;
		}

		$actual_tfname = $tfname . '.d';
		codepot_delete_files ($actual_tfname, TRUE); // delete the directory in case it exists

		set_error_handler (array ($this, 'capture_error'));
		if (@mkdir ($actual_tfname) === FALSE ||
		    @svn_auth_set_parameter (SVN_AUTH_PARAM_DEFAULT_USERNAME, $committer) === FALSE ||
		    @svn_checkout ($dirurl, $actual_tfname, SVN_REVISION_HEAD, 0) === FALSE)
		{
			restore_error_handler ();
			codepot_delete_files ($actual_tfname, TRUE);
			@unlink ($tfname);
			return FALSE;
		}

		foreach ($files as $f)
		{
			$xname = $actual_tfname . '/' . $f[0];
			$yname = $actual_tfname . '/' . $f[1];

			if ($f[0] == $f[1]) continue;

			if (@file_exists($yname))
			{
				$this->errmsg = "{$f[1]} already exists";
				restore_error_handler ();
				codepot_delete_files ($actual_tfname, TRUE);
				@unlink ($tfname);
				return FALSE;
			}

			if (@svn_move ($xname, $yname, TRUE) === FALSE)
			{
				restore_error_handler ();
				codepot_delete_files ($actual_tfname, TRUE);
				@unlink ($tfname);
				return FALSE;
			}
		}

		if (($result = @svn_commit ($commit_message, $actual_tfname)) === FALSE)
		{
			restore_error_handler ();
			codepot_delete_files ($actual_tfname, TRUE);
			@unlink ($tfname);
			return FALSE;
		}

		restore_error_handler ();
		codepot_delete_files ($actual_tfname, TRUE); // delete the directory in case it exists
		@unlink ($tfname);
		return TRUE;
	}

	function getRevHistory ($projectid, $path, $rev)
	{
		//$url = 'file:///'.CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}";
		$orgurl = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");
		$url = $orgurl;

		/* Compose a URL with a peg revision if a specific revision is given. However, 
		 * It skips composition if the path indicates the project root. Read the comment
		 * in getFile() to know more about this skipping.
		 */
		if ($rev != SVN_REVISION_HEAD && $path != '') $url = $url . '@' . $rev;
		$info = @svn_info($url, FALSE, $rev);
		if ($info === FALSE || $info === NULL || count($info) != 1) 
		{
			$info = @svn_info($orgurl, FALSE, $rev);
			if ($info === FALSE || $info === NULL || count($info) != 1) return FALSE;
			$url = $orgurl;
		}

		$actual_rev = $rev;
		if ($info[0]['kind'] == SVN_NODE_FILE) 
		{
			$lsinfo = @svn_ls ($url, $rev, FALSE, TRUE);
			if ($lsinfo === FALSE) return FALSE;

			if (array_key_exists ($info[0]['path'], $lsinfo) === FALSE) return FALSE;
			$fileinfo = $lsinfo[$info[0]['path']];
			if (!array_key_exists ('fullpath', $fileinfo))
				$fileinfo['fullpath'] = $info[0]['path'];

			$actual_rev = $fileinfo['created_rev'];
		}
		else if ($info[0]['kind'] == SVN_NODE_DIR)
		{
			$fileinfo['fullpath'] = substr ($info[0]['url'], strlen($info[0]['repos']));
			$fileinfo['name'] =  $info[0]['path'];
			$fileinfo['type'] = 'dir';
			$fileinfo['size'] = 0;
			if (array_key_exists('last_changed_rev', $info[0]))
				$fileinfo['created_rev'] = $info[0]['last_changed_rev'];
			else
				$fileinfo['created_rev'] = $info[0]['revision'];

			if (array_key_exists('last_changed_author', $info[0]))
				$fileinfo['last_author'] = $info[0]['last_changed_author'];
			else
				$fileinfo['last_author'] = '';

			$actual_rev = $fileinfo['created_rev'];
		}
		else return FALSE;

		$log = @svn_log ($url, $actual_rev, $actual_rev, 1, SVN_DISCOVER_CHANGED_PATHS);
		if ($log === FALSE) return FALSE;

		if (count($log) != 1) return FALSE;

		$fileinfo['history'] = $log[0];
		return $fileinfo;
	}

	function _get_diff ($diff, $src1, $src2, $all, $ent)
	{
		/* copied from websvn */
		if ($all) 
		{
			//$ofile = fopen ($oldtname, "r");
			//$nfile = fopen ($newtname, "r");
			$ofile = fopen("php://memory", 'r+');
			$nfile = fopen("php://memory", 'r+');
			fputs($ofile, $src1);
			fputs($nfile, $src2);
			rewind($ofile);
			rewind($nfile);
		}

		$abort = FALSE;
		$index = 0;
		$listing = array();

		$curoline = 1;
		$curnline = 1;

		// Ignore the 4 header lines. AFter that, it get the first real line
		// which is the 5th line. The header lines look like this:
		//
		// Index: test1.txt
		// ===================================================================
		// --- test1.txt	(revision 20)
		// +++ test1.txt	(revision 22)
		//
		// The fifth line should look like this:
		// @@ -2,5 +2,7 @@
		//
		for ($i = 0; $i < 5 && !feof($diff); $i++)
		{
			$line = fgets($diff);
			if ($line === FALSE || $line == "\n") 
			{
				// the first line can be empty if there is no contents changes.
				// property changes may be following but this function is
				// not interested in it.
				$abort = TRUE;
				break;
			}
		}

		if (!feof($diff) && !$abort)
		{
			// santy check on the fifth line. something bad must have
			// happened if it doesn't begin with @@.
			if (strncmp($line, "@@", 2) != 0) $abort = TRUE;
		}

		while (!feof($diff) && !$abort) 
		{
			// Get the first line of this range
			sscanf($line, "@@ -%d", $oline);

			$line = substr($line, strpos($line, "+"));
			sscanf($line, "+%d", $nline);

			if ($all) 
			{
				while ($curoline < $oline || $curnline < $nline) 
				{
					$listing[$index]["rev1diffclass"] = "diff";
					$listing[$index]["rev2diffclass"] = "diff";

					if ($curoline < $oline) 
					{
						$nl = fgets($ofile);
						$line = rtrim($nl, "\r\n");
						if ($ent) $line = replaceEntities($line, $rep);

						//$listing[$index]["rev1line"] = hardspace($line);
						$listing[$index]["rev1line"] = $line;

						$curoline++;
					} 
					else 
					{
						//$listing[$index]["rev1line"] = "&nbsp;";
						$listing[$index]["rev1line"] = "";
					}

					if ($curnline < $nline) 
					{
						$nl = fgets($nfile);

						$line = rtrim($nl, "\r\n");
						if ($ent) $line = replaceEntities($line, $rep);

						//$listing[$index]["rev2line"] = hardspace($line);
						$listing[$index]["rev2line"] = $line;
						$curnline++;
					} 
					else 
					{
						//$listing[$index]["rev2line"] = "&nbsp;";
						$listing[$index]["rev2line"] = "";
					}

					$listing[$index]["rev1lineno"] = 0;
					$listing[$index]["rev2lineno"] = 0;

					$index++;
				}
			} 
			else 
			{
				// Output the line numbers
				$listing[$index]["rev1lineno"] = $oline;
				$listing[$index]["rev2lineno"] = $nline;
				$index++;
			}

			$fin = FALSE;
			while (!feof($diff) && !$fin) 
			{
				$line = fgets($diff);
				if ($line === FALSE || $line == "\n")
				{
					// fgets() returned failure or an empty line has been read.
					// An empty line can be read if property changes exist.
					// The line before 'Property changes on: ..' is an  empty line.
					//
					// Index: test1.txt
					// ===================================================================
					// --- test1.txt	(revision 14)
					// +++ test1.txt	(working copy)
					// @@ -1 +1,6 @@
					// hello world
					// +
					// +
					// +hello world 2
					// +
					// +hello world 3
					//
					// Property changes on: test1.txt
					// ___________________________________________________________________
					// Added: test
					//    + xxx
					// Added: abcprop
					//    + on
					// Deleted: svn:executable
					//    - *
					//
					$fin = TRUE;
					$abort = TRUE;
				}
				else if (strncmp($line, "@@", 2) == 0)
				{
					$fin = TRUE;
				} 
				else 
				{
					$listing[$index]["rev1lineno"] = 0;
					$listing[$index]["rev2lineno"] = 0;

					$mod = $line[0];

					$line = rtrim(substr($line, 1), "\r\n");
					if ($ent) $line = replaceEntities($line, $rep);

					//if (strip_tags($line) == '') $line = '&nbsp;';
					//$listing[$index]["rev1line"] = hardspace($line);
					$listing[$index]["rev1line"] = $line;

					//$text = hardspace($line);
					$text = $line;

					switch ($mod) 
					{
						case "\\":
							// ignore it .
							// subversion seems to procude a line like this:
							//  \ No newline at the end of file
							//
							unset ($listing[$index]); // unset the data filled partially above.
							$index--;
							break;

						case "-":
							$listing[$index]["rev1diffclass"] = "diffdeleted";
							$listing[$index]["rev2diffclass"] = "diff";

							if ($all) 
							{
								fgets($ofile);
								$curoline++;
							}

							$listing[$index]["rev1line"] = $text;
							//$listing[$index]["rev2line"] = "&nbsp;";
							$listing[$index]["rev2line"] = '';

							break;

						case "+":
							// Try to mark "changed" line sensibly
							if (!empty($listing[$index-1]) && 
							    empty($listing[$index-1]["rev1lineno"]) && 
							    @$listing[$index-1]["rev1diffclass"] == "diffdeleted" &&
							    @$listing[$index-1]["rev2diffclass"] == "diff") 
							{
								$i = $index - 1;
								while (!empty($listing[$i-1]) &&
								       empty($listing[$i-1]["rev1lineno"]) &&
								       $listing[$i-1]["rev1diffclass"] == "diffdeleted" &&
								       $listing[$i-1]["rev2diffclass"] == "diff") 
								{
									$i--;
								}

								$listing[$i]["rev1diffclass"] = "diffchanged";
								$listing[$i]["rev2diffclass"] = "diffchanged";
								$listing[$i]["rev2line"] = $text;

								if ($all) 
								{
									fgets($nfile);
									$curnline++;
								}

								// Don't increment the current index count
								$index--;

							} 
							else 
							{
								$listing[$index]["rev1diffclass"] = "diff";
								$listing[$index]["rev2diffclass"] = "diffadded";

								//$listing[$index]["rev1line"] = "&nbsp;";
								$listing[$index]["rev1line"] = '';
								$listing[$index]["rev2line"] = $text;

								if ($all) 
								{
									fgets($nfile);
									$curnline++;
								}
							}
							break;

						default:
							$listing[$index]["rev1diffclass"] = "diff";
							$listing[$index]["rev2diffclass"] = "diff";

							$listing[$index]["rev1line"] = $text;
							$listing[$index]["rev2line"] = $text;

							if ($all) 
							{
								fgets($ofile);
								fgets($nfile);
								$curoline++;
								$curnline++;
							}

							break;
					}
				}

				if (!$fin) $index++;
			}
		}

		// Output the rest of the files
		if ($all) 
		{
			while (1)
			{
				if (feof($ofile) && feof($nfile)) break;
				$listing[$index]["rev1diffclass"] = "diff";
				$listing[$index]["rev2diffclass"] = "diff";

				if (!feof($ofile))
				{
					$line = rtrim(fgets($ofile), "\r\n");
					if ($ent) $line = replaceEntities($line, $rep);

					//$listing[$index]["rev1line"] = hardspace($line);
					$listing[$index]["rev1line"] = $line;
				}
				else 
				{
					//$listing[$index]["rev1line"] = "&nbsp;";
					$listing[$index]["rev1line"] = ''; 
				}

				if (!feof($nfile))
				{
					$line = rtrim(fgets($nfile), "\r\n");
					if ($ent) $line = replaceEntities(rtrim(fgets($nfile), "\r\n"), $rep);

					//$listing[$index]["rev2line"] = hardspace($line);
					$listing[$index]["rev2line"] = $line;
				}
				else 
				{
					//$listing[$index]["rev2line"] = "&nbsp;";
					$listing[$index]["rev2line"] = '';
				}

				$listing[$index]["rev1lineno"] = 0;
				$listing[$index]["rev2lineno"] = 0;

				$index++;
			}
		}

		return $listing;
	}

	//  
	// Given a path name at the HEAD revision, it compares the file
	// between two revisions given. The actual path name at a given
	// revision can be different from the path name at the HEAD revision.
	// 
	// $file - path name at the HEAD revision
	// $rev1 - new revision number
	// $rev2 - old revision number
	//
	function getDiff ($projectid, $path, $rev1, $rev2, $full = FALSE)
	{
		//$url = 'file:///'.CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}";
		$orgurl = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");

		$workurl1 = $orgurl;
		$lsinfo1 = @svn_ls ($workurl1, $rev1, FALSE, TRUE);
		if ($lsinfo1 === FALSE || count($lsinfo1) != 1) 
		{
			if ($rev1 == SVN_REVISION_HEAD || $path == '') return FALSE;
			$workurl1 = "{$orgurl}@{$rev1}";
			$lsinfo1 = @svn_ls ($workurl1, $rev1, FALSE, TRUE);
			if ($lsinfo1 === FALSE || count($lsinfo1) != 1)  return FALSE;
		}

		// the check above guarantees that the foreach block below
		// is executed only once.
		foreach ($lsinfo1 as $key => $value) 
		{
			if ($value['type'] != 'file') return FALSE;
			$rev1 = $value['created_rev'];
			$fileinfo = $value;
		}

		$fileinfo['head_name'] = $path;

		if ($rev2 <= 0)
		{
			// get two log entries including the new revision 
			$log = @svn_log (
				$workurl1, $rev1, SVN_REVISION_INITIAL, 2,
				SVN_OMIT_MESSAGES | SVN_DISCOVER_CHANGED_PATHS);
			if ($log === FALSE) return FALSE;
			$rev2 = $log[(count($log) <= 1)? 0:1]['rev'];
		}

		$workurl2 = $orgurl;
		$lsinfo2 = @svn_ls ($workurl2, $rev2, FALSE, TRUE);
		if ($lsinfo2 === FALSE || count($lsinfo2) != 1) 
		{
			if ($rev2 == SVN_REVISION_HEAD || $path == '') return FALSE;
			$workurl2 = "{$orgurl}@{$rev2}";
			$lsinfo2 = @svn_ls ($workurl2, $rev2, FALSE, TRUE);
			if ($lsinfo2 === FALSE || count($lsinfo2) != 1)  return FALSE;
		}

		// the check above guarantees the foreach block below
		// is executed only once.
		foreach ($lsinfo2 as $key => $value) 
		{
			if ($value['type'] != 'file') return FALSE;
			$rev2 = $value['created_rev'];
			$fileinfo['against'] = $value;
		}

		// let's get the actual URL for each revision.
		// the actual URLs may be different from $url 
		// if the file has been changed.
		$info1 = @svn_info($workurl1, FALSE, $rev1);
		if ($info1 === FALSE || $info1 === NULL || count($info1) != 1) return FALSE;
		if ($info1[0]['kind'] != SVN_NODE_FILE) return FALSE;
		$info2 = @svn_info($workurl2, FALSE, $rev2);
		if ($info2 === FALSE || $info2 === NULL || count($info2) != 1) return FALSE;
		if ($info2[0]['kind'] != SVN_NODE_FILE) return FALSE;

		// get the difference with the actual URLs
		list($diff, $errors) = @svn_diff (
			$info2[0]['url'], $info2[0]['revision'],
			$info1[0]['url'], $info1[0]['revision']);
		if (!$diff) return FALSE;

		/* 
			## Sample svn_info() array ##
			[0] => Array
			(
				[path] => codepot.sql
				[url] => file:///svn/test/codepot.sql
				[revision] => 27
				[kind] => 1
				[repos] => file:///svn/test
				[last_changed_rev] => 27
				[last_changed_date] => 2010-02-18T01:53:13.076062Z
				[last_changed_author] => hyunghwan
			)
		*/

		$fileinfo['fullpath'] = substr ($info1[0]['url'], strlen($info1[0]['repos']));
		$fileinfo['against']['fullpath'] = substr ($info2[0]['url'], strlen($info2[0]['repos']));

		fclose($errors);

		if ($full)
		{
			$old_text = @svn_cat ($info2[0]['url'], $info2[0]['revision']);
			if ($old_text === FALSE)
			{
				$pegged_url = $info2[0]['url'] . '@' . $info2[0]['revision'];
				$old_text = @svn_cat ($pegged_url, $info2[0]['revision']);
				if ($old_text === FALSE)
				{
					// if the old URL can't give the contents,
					// try it with the latest url and the old revision number
					$old_text = @svn_cat ($info1[0]['url'], $info2[0]['revision']);
				}
			}
			$new_text = @svn_cat ($info1[0]['url'], $info1[0]['revision']);
			if ($new_text == FALSE)
			{
				$pegged_url = $info1[0]['url'] . '@' . $info1[0]['revision'];
				$new_text = @svn_cat ($pegged_url, $info1[0]['revision']);
			}
			$fileinfo['content'] = $this->_get_diff ($diff, $old_text, $new_text, TRUE, FALSE);
		}
		else
		{
			$fileinfo['content'] = $this->_get_diff ($diff, '', '', FALSE, FALSE);
		}
		fclose ($diff);

		$log = @svn_log ($info1[0]['url'], $fileinfo['created_rev'], $fileinfo['created_rev'], 1, SVN_DISCOVER_CHANGED_PATHS);
		if ($log === FALSE) $log = @svn_log ($workurl1, $fileinfo['created_rev'], $fileinfo['created_rev'], 1, SVN_DISCOVER_CHANGED_PATHS);
		if ($log === FALSE) $fileinfo['logmsg'] = '';
		else $fileinfo['logmsg'] = (count($log) > 0)? $log[0]['msg']: '';

		$log = @svn_log ($info2[0]['url'], $fileinfo['against']['created_rev'], $fileinfo['against']['created_rev'], 1, SVN_DISCOVER_CHANGED_PATHS);
		if ($log === FALSE)  $log = @svn_log ($workurl2, $fileinfo['against']['created_rev'], $fileinfo['against']['created_rev'], 1, SVN_DISCOVER_CHANGED_PATHS);
		if ($log === FALSE) $fileinfo['logmsg'] = '';
		else $fileinfo['against']['logmsg'] = (count($log) > 0)? $log[0]['msg']: '';

		return $fileinfo;
	}

	function getPrevRev ($projectid, $path, $rev)
	{
		//$url = 'file:///'.CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}";
		$orgurl = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");

		$workurl = ($path == '')? $orgurl: "{$orgurl}@"; // trailing @ for collision prevention
		$info = @svn_info($workurl, FALSE, $rev);
		if ($info === FALSE || $info === NULL || count($info) != 1) 
		{
			if ($rev == SVN_REVISION_HEAD || $path == '') return $rev;
			$workurl = "{$orgurl}@{$rev}";
			$info = @svn_info($workurl, FALSE, $rev);
			if ($info === FALSE || $info === NULL || count($info) != 1)  return $rev;
		}

		$log = @svn_log (
			$workurl, $rev, SVN_REVISION_INITIAL, 2,
			SVN_OMIT_MESSAGES | SVN_DISCOVER_CHANGED_PATHS);
		if ($log === FALSE) return $rev;

		$count = count($log);
		if ($count <= 0) return $rev;
		if ($count == 1) return $log[0]['rev'];
		
		return $log[1]['rev'];
	}

	function getNextRev ($projectid, $path, $rev)
	{
		//$url = 'file:///'.CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}";
		$url = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");

		$log = @svn_log (
			$url, SVN_REVISION_HEAD, $rev, 0,
			SVN_OMIT_MESSAGES | SVN_DISCOVER_CHANGED_PATHS);
		if ($log === FALSE) 
		{
			if ($rev != SVN_REVISION_HEAD && $path != '')
			{
				$pegrev = $rev;
				do
				{
					$pegrev++;
					$pegurl = $url . '@' . $pegrev;

					$log = @svn_log (
						$pegurl, $pegrev, $rev, 0,
						SVN_OMIT_MESSAGES /*| SVN_DISCOVER_CHANGED_PATHS*/);
					if ($log === FALSE) return $rev;
					$count = count($log);
					if ($count <= 0) return $rev;
					if ($log[0]['rev'] != $rev) 
					{
						/* try with SVN_DISCOVER_CHANGED_PATHS only once
						 * change is detected for performance.. however 
						 * this loop itself already kills performance */
						$log = @svn_log (
							$pegurl, $pegrev, $rev, 0,
							SVN_OMIT_MESSAGES | SVN_DISCOVER_CHANGED_PATHS);
						if ($log === FALSE) return $rev;
						$count = count($log);
						if ($count <= 0) return $rev;

						$url = $pegurl;
						break;
					}
				}
				while (1);
			}
			if ($log === FALSE) return $rev;
		}

		$count = count($log);
		if ($count <= 0) return $rev;
		if ($count == 1) return $log[0]['rev'];
		
		// 
		// r22 /usr/lib/a.c  updated a.c
		// r21 /usr/lib/a.c  moved /lib to /usr/lib
		// r10 /lib/a.c      updated a.c
		// r5  /lib/a.c      added a.c
		// ------------------------------------------------------
		// subversion does not a.c for r21 to show.
		// the same thing can happen if the file is just renamed.
		// make best effort to find the revision with change.
		// 
		for ($count = $count - 2; $count >= 0; $count--)
		{
			$info = @svn_info($url, FALSE, $log[$count]['rev']);
			if ($info === FALSE || $info === NULL) return FALSE;

			if ($info[0]['last_changed_rev'] > $rev)
			{
				return $info[0]['revision'];
			}
		}

		return $rev;
	}

	function getHeadRev ($projectid, $path, $rev)
	{
		//$url = 'file:///'.CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}";
		$orgurl = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");

		if ($path == '')
		{
			$workurl = $orgurl;
			$workrev = $rev;

			$info = @svn_info($workurl, FALSE, $workrev);
			if ($info === FALSE || $info === NULL || count($info) != 1)  return FALSE;
		}
		else
		{
			$workurl = "{$orgurl}@";
			$workrev = SVN_REVISION_HEAD;
			$info = @svn_info($workurl, FALSE, $workrev);
			if ($info === FALSE || $info === NULL || count($info) != 1) 
			{
				$workrev = $rev;
				$info = @svn_info($workurl, FALSE, $workrev);
				if ($info === FALSE || $info === NULL || count($info) != 1) 
				{
					if ($rev == SVN_REVISION_HEAD) return FALSE;

					$workurl = "{$orgurl}@{$rev}";
					$info = @svn_info($workurl, FALSE, $workrev);
					if ($info === FALSE || $info === NULL || count($info) != 1) return FALSE;
				}
			}
		}

		$log = @svn_log (
			$workurl, $workrev, SVN_REVISION_INITIAL, 1,
			SVN_OMIT_MESSAGES | SVN_DISCOVER_CHANGED_PATHS);
		if ($log === FALSE) return FALSE;
		if (count($log) != 1) return FALSE;
		return $log[0]['rev'];
	}

	function getRevProp ($projectid, $rev, $prop)
	{
		$this->clearErrorMessage ();
		$url = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}");

		set_error_handler (array ($this, 'capture_error'));
		$result =  @svn_revprop_get ($url, $rev, $prop);
		restore_error_handler ();
		return $result;
	}

	function setRevProp ($projectid, $rev, $prop, $propval, $user)
	{
		$this->clearErrorMessage ();
		$url = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}");

		set_error_handler (array ($this, 'capture_error'));

		$orguser = @svn_auth_get_parameter (SVN_AUTH_PARAM_DEFAULT_USERNAME);
		@svn_auth_set_parameter (SVN_AUTH_PARAM_DEFAULT_USERNAME, $user);

		$result = @svn_revprop_set ($url, $rev, $prop, $propval);
		@svn_auth_set_parameter (SVN_AUTH_PARAM_DEFAULT_USERNAME, $orguser);

		restore_error_handler ();
		return $result;
	}

	function killRevProp ($projectid, $rev, $prop, $user)
	{
		$this->clearErrorMessage ();
		$url = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}");

		set_error_handler (array ($this, 'capture_error'));

		$orguser = @svn_auth_get_parameter (SVN_AUTH_PARAM_DEFAULT_USERNAME);
		@svn_auth_set_parameter (SVN_AUTH_PARAM_DEFAULT_USERNAME, $user);

		$result = @svn_revprop_delete ($url, $rev, $prop);

		@svn_auth_set_parameter (SVN_AUTH_PARAM_DEFAULT_USERNAME, $orguser);

		restore_error_handler ();
		return $result;
	}

	function mapRevPropToRev ($projectid, $revprop_name)
	{
		$this->clearErrorMessage ();
		$url = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}");

		set_error_handler (array ($this, 'capture_error'));
		$info = @svn_info($url, FALSE);
		restore_error_handler ();
		if ($info == FALSE || $info === NULL || count($info) != 1) return FALSE;

		$props = array ();
		$xi = $info[0];
		for ($rev = $xi['revision']; $rev > 0; $rev--)
		{
			set_error_handler (array ($this, 'capture_error'));
			$val = @svn_revprop_get ($url, $rev, $revprop_name);
			restore_error_handler ();
			if ($val != '')
			{
				$props[$val] = $rev;
			}
		}

		return $props;
	}

	function findRevWithRevProp ($projectid, $revprop_name, $revprop_value)
	{
		$this->clearErrorMessage ();
		$url = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}");

		set_error_handler (array ($this, 'capture_error'));
		$info = @svn_info($url, FALSE);
		restore_error_handler ();
		if ($info == FALSE || $info === NULL || count($info) != 1) return FALSE;

		$xi = $info[0];
		for ($rev = $xi['revision']; $rev > 0; $rev--)
		{
			set_error_handler (array ($this, 'capture_error'));
			$val = @svn_revprop_get ($url, $rev, $revprop_name);
			restore_error_handler ();
			if ($val != '' && $revprop_value == $val)
			{
				return $rev;
			}
		}

		return -1;
	}


	function listProps ($projectid, $path, $rev)
	{
		$this->clearErrorMessage ();
		$orgurl = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");

		$workurl = ($path == '')? $orgurl: "{$orgurl}@"; // trailing @ for collision prevention
		set_error_handler (array ($this, 'capture_error'));
		$info = @svn_info($workurl, FALSE, $rev);
		restore_error_handler ();

		if ($info === FALSE || $info === NULL || count($info) != 1) 
		{
			if ($rev == SVN_REVISION_HEAD || $path == '') return FALSE;

			// rebuild the URL with a peg revision and retry it.
			$workurl = "{$orgurl}@{$rev}";
			set_error_handler (array ($this, 'capture_error'));
			$info = @svn_info($workurl, FALSE, $rev);
			restore_error_handler ();
			if ($info === FALSE || $info === NULL || count($info) != 1)  return FALSE;
		}

		set_error_handler (array ($this, 'capture_error'));
		$result = @svn_proplist ($workurl, 0, $rev);
		restore_error_handler ();
		return $result;
	}

	function getProp ($projectid, $path, $rev, $prop)
	{
		$this->clearErrorMessage ();
		$orgurl = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");

		$workurl = ($path == '')? $orgurl: "{$orgurl}@"; // trailing @ for collision prevention
		set_error_handler (array ($this, 'capture_error'));
		$info = @svn_info($workurl, FALSE, $rev);
		restore_error_handler ();

		if ($info === FALSE || $info === NULL || count($info) != 1) 
		{
			if ($rev == SVN_REVISION_HEAD || $path == '') return FALSE;

			// rebuild the URL with a peg revision and retry it.
			$workurl = "{$orgurl}@{$rev}";
			set_error_handler (array ($this, 'capture_error'));
			$info = @svn_info($workurl, FALSE, $rev);
			restore_error_handler ();
			if ($info === FALSE || $info === NULL || count($info) != 1)  return FALSE;
		}

		set_error_handler (array ($this, 'capture_error'));
		$result =  @svn_propget ($workurl, $prop, FALSE, $rev);
		restore_error_handler ();
		return $result;
	}

	function _cloc_revision_by_lang ($projectid, $path, $rev)
	{
		$orgurl = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");

		$workurl = ($path == '')? $orgurl: "{$orgurl}@"; // trailing @ for collision prevention
		$info = @svn_info($workurl, FALSE, $rev);
		if ($info === FALSE || $info === NULL || count($info) != 1) 
		{
			// If a URL is at the root of repository and the url type is file://
			// (e.g. file:///svnrepo/codepot/ where /svnrepo/codepot is a project root),
			// some versions of libsvn end up with an assertion failure like
			//  ... libvvn_subr/path.c:114: svn_path_join: Assertion `svn_path_is_canonical(base, pool)' failed. 
			// 
			// Since the root directory is guaranteed to exist at the head revision,
			// the information can be acquired without using a peg revision.
			// In this case, a normal operational revision is used to work around
			// the assertion failure. Other functions that has to deal with 
			// the root directory should implement this check to work around it.
			//
			if ($rev == SVN_REVISION_HEAD || $path == '') return FALSE;

			// rebuild the URL with a peg revision and retry it.
			$workurl = "{$orgurl}@{$rev}";
			$info = @svn_info($workurl, FALSE, $rev);
			if ($info === FALSE || $info === NULL || count($info) != 1)  return FALSE;
		}

		if ($info[0]['kind'] == SVN_NODE_FILE) return FALSE;

		// pass __FILE__ as the first argument so that tempnam creates a name
		// in the system directory. __FILE__ can never be a valid directory.
		$tfname = @tempnam(__FILE__, 'codepot-cloc-rev-');
		if ($tfname === FALSE) return FALSE;

		$actual_tfname = $tfname . '.d';
		codepot_delete_files ($actual_tfname, TRUE); // delete the directory in case it exists

		if (@svn_checkout ($workurl, $actual_tfname, $rev, 0) === FALSE)
		{
			codepot_delete_files ($actual_tfname, TRUE);
			@unlink ($tfname);
			return FALSE;
		}

		$cloc_cmd = sprintf ('%s --quiet --csv --csv-delimiter=":" %s', CODEPOT_CLOC_COMMAND_PATH, $actual_tfname);
		$cloc = @popen ($cloc_cmd, 'r');
		if ($cloc === FALSE)
		{
			codepot_delete_files ($actual_tfname, TRUE);
			@unlink ($tfname);
			return FALSE;
		}

		$line_count = 0;
		$cloc_data = array ();
		while (!feof($cloc))
		{
			$line = @fgets ($cloc);
			if ($line === FALSE) break;

			$line_count++;
			$line = trim($line);
			if ($line_count >= 3)
			{
				$counter = explode (':', $line);
				$cloc_data[$counter[1]] = array ($counter[0], $counter[2], $counter[3], $counter[4]);
			}
		}

		@pclose ($cloc);
		codepot_delete_files ($actual_tfname, TRUE);
		@unlink ($tfname);

		return $cloc_data;
	}

	function clocRevByLang ($projectid, $path, $rev)
	{
		return $this->_cloc_revision_by_lang ($projectid, $path, $rev);
	}

	function clocRevByFile ($projectid, $path, $rev)
	{
		// this function composes the data as CodeFlower requires
		$stack = array();
		$cloc = new stdClass();

		$cloc->name = $path;
		if ($cloc->name == '') $cloc->name = '/';
		$cloc->children = array();

		array_push ($stack, $path);
		array_push ($stack, $cloc);

		while (!empty($stack))
		{
			$current_cloc = array_pop($stack);
			$current_path = array_pop($stack);

			$orgurl = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$current_path}");
			$trailer = ($current_path == '')? '': '@'; // trailing @ for collision prevention
			$workurl = $orgurl . $trailer;
			$info = @svn_info($workurl, FALSE, $rev);
			if ($info === FALSE || $info === NULL || count($info) != 1) 
			{
				// If a URL is at the root of repository and the url type is file://
				// (e.g. file:///svnrepo/codepot/ where /svnrepo/codepot is a project root),
				// some versions of libsvn end up with an assertion failure like
				//  ... libvvn_subr/path.c:114: svn_path_join: Assertion `svn_path_is_canonical(base, pool)' failed. 
				// 
				// Since the root directory is guaranteed to exist at the head revision,
				// the information can be acquired without using a peg revision.
				// In this case, a normal operational revision is used to work around
				// the assertion failure. Other functions that has to deal with 
				// the root directory should implement this check to work around it.
				//
				if ($rev == SVN_REVISION_HEAD || $path == '') continue;

				// rebuild the URL with a peg revision and retry it.
				$trailer = "@{$rev}";
				$workurl = $orgurl . $trailer;
				$info = @svn_info($workurl, FALSE, $rev);
				if ($info === FALSE || $info === NULL || count($info) != 1)  continue;
			}

			if ($info[0]['kind'] == SVN_NODE_FILE) return FALSE;
			$info0 = &$info[0];

			$list = @svn_ls ($workurl, $rev, FALSE, TRUE);
			if ($list === FALSE) return FALSE;

			foreach ($list as $key => $value)
			{
				$full_path = $current_path . '/' . $key;
				if ($value['type'] == 'file')
				{
					$obj = new stdClass();
					//$obj->name = $key;
					$obj->name = $full_path;

					$text = @svn_cat ("{$orgurl}/{$key}{$trailer}", $rev);
					if ($text === FALSE) $obj->size = 0;
					else
					{
						$text_len = strlen($text);
						$obj->size = substr_count($text, "\n");
						if ($text_len > 0 && $text[$text_len - 1] != "\n") $obj->size++;
					}

					$obj->language = substr(strrchr($key, '.'), 1); // file extension
					if ($obj->language === FALSE) $obj->language = '';


					array_push ($current_cloc->children, $obj);
				}
				else
				{
					$obj = new stdClass();
					// using the base name only caused some wrong linkages
					// in the graph when the base name coflicted with
					// other same base name in a different directory.
					// let's use a full path. it's anyway clearer.
					//$obj->name = $key;
					$obj->name = $full_path;

					$obj->children = array();
					array_push ($current_cloc->children, $obj);

					array_push ($stack, $full_path);
					array_push ($stack, $obj);
				}
			}
		}

		return $cloc;
	}

	private function _add_rg_node (&$nodeids, &$nodes, $name, $type = '')
	{
		if (array_key_exists($name, $nodeids)) 
		{
			$nid = $nodeids[$name];
			if ($type != '') $nodes[$nid]['_type'] .= $type;
			return $nid;
		}

		$nid = count($nodeids);
		array_push ($nodes, array ('id' => $nid, 'label' => $name, '_type' => $type));
		$nodeids[$name] = $nid;
		return $nid;
	}

	private function _add_rg_edge (&$edges, $from, $to, $label)
	{
		$edge = array ('from' => $from, 'to' => $to, 'label' => $label);
		if (!in_array($edge, $edges)) array_push ($edges, $edge);
	}

	function __revisionGraph ($projectid, $path, $rev)
	{
		// this function is almost blind translation of svn-graph.pl

		/* we should get the history from the entire project */
		$orgurl = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}");

		$startpath = $path;
		$interesting = array ("{$startpath}:1" => 1);
		$tracking = array ($startpath => 1);

		$codeline_changes_forward = array ();
		$codeline_changes_back = array ();
		$copysource = array ();
		$copydest = array ();

		$nodeids = array ();
		$nodes = array ();
		$edges = array ();

		$log = @svn_log ($orgurl, 1, $rev, 0, SVN_DISCOVER_CHANGED_PATHS);
		if ($log === FALSE || count($log) <= 0) return FALSE;

		foreach ($log as $l)
		{
			$deleted = array();
			$currev = $l['rev'];

			foreach ($l['paths'] as $p)
			{
				$curpath = $p['path'];
				if ($p['action'] == 'D' && array_key_exists($curpath, $tracking))
				{
					/* when an item is moved, D and A are listed in order.
					 * [20] => Array
					   (
						[rev] => 21
						[author] => khinsanwai
						[msg] => Mov tags/1.0.0 to tags/ATI_POC/1.0.0
						[date] => 2013-09-18T06:39:41.553616Z
						[paths] => Array
							 (
								[0] => Array
								    (
									   [action] => D
									   [path] => /tags/1.0.0
								    )
								[1] => Array
								    (
									   [action] => A
									   [path] => /tags/ATI_POC/1.0.0
									   [copyfrom] => /tags/1.0.0
									   [rev] => 20
								    )
							 )
						)
					 */
//print ("{$curpath}:{$tracking[$curpath]} [label=\"{$curpath}:{$tracking[$curpath]}\\nDelete in {$currev}\", color=red];\n");

					$id1 = $this->_add_rg_node ($nodeids, $nodes, "{$curpath}:{$tracking[$curpath]}");
					$id2 = $this->_add_rg_node ($nodeids, $nodes, "<<deleted>>");
					$this->_add_rg_edge ($edges, $id1, $id2, "deleted");

					// i can't simply remove the item from the tracking list.
					//unset ($tracking[$curpath]);
					//$deleted["{$curpath}:{$tracking[$curpath]}"] = $id1;
					continue;
				}

				if (array_key_exists('copyfrom', $p))
				{
					$copyfrom_path = $p['copyfrom'];
					if (array_key_exists($copyfrom_path, $tracking))
					{
						$copyfrom_rev = $tracking[$copyfrom_path];
						if (array_key_exists ("{$copyfrom_path}:{$copyfrom_rev}", $interesting))
						{
							$interesting["{$curpath}:{$currev}"]  = 1;
							$tracking[$curpath] = $currev;
//print ("{$copyfrom_path}:{$copyfrom_rev} -> {$curpath}:{$currev} [label=\"copy at {$currev}\", color=green];\n");

							$id1 = $this->_add_rg_node ($nodeids, $nodes, "{$copyfrom_path}:{$copyfrom_rev}");
							$id2 = $this->_add_rg_node ($nodeids, $nodes, "{$curpath}:{$currev}");
							$this->_add_rg_edge ($edges, $id1, $id2, "copied");

							$copysource["{$copyfrom_path}:{$copyfrom_rev}"] = 1;
							$copydest["{$curpath}:{$currev}"] = 1;
						}
					}
				}

				do
				{
					if (array_key_exists($curpath, $tracking) && $tracking[$curpath] != $currev)
					{
						$codeline_changes_forward["{$curpath}:{$tracking[$curpath]}"] = "{$curpath}:{$currev}";
						$codeline_changes_back["{$curpath}:{$currev}"] = "{$curpath}:{$tracking[$curpath]}";
						$interesting["{$curpath}:{$currev}"] = 1;
						$tracking[$curpath] = $currev;
					}

					if ($curpath == '/') break;
					$curpath = dirname ($curpath);
				}
				while (1);
			}

			/*foreach ($deleted as $d => $v)
			{
				$id2 = $this->_add_rg_node ($nodeids, $nodes, "<<deleted>>");
				$this->_add_rg_edge ($edges, $id1, $id2, "deleted");
			}*/
		}

		foreach ($codeline_changes_forward as $k => $v)
		{
			if (array_key_exists ($k, $codeline_changes_back) && !array_key_exists($k, $copysource)) continue;

			if (!array_key_exists ($k, $codeline_changes_back) || array_key_exists($k, $copysource))
			{
				if (array_key_exists($k, $codeline_changes_forward))
				{
					$nextchange = $codeline_changes_forward[$k];
					$changecount = 1;
					while (1)
					{
						if (array_key_exists($nextchange, $copysource) || !array_key_exists($nextchange, $codeline_changes_forward))
						{
//print "{$k} -> {$nextchange} [label={$changecount} change(s)]\n";
							$id1 = $this->_add_rg_node ($nodeids, $nodes, $k);
							$id2 = $this->_add_rg_node ($nodeids, $nodes, $nextchange);
							$this->_add_rg_edge ($edges, $id1, $id2, "{$changecount} change(s)");
							break;
						}
						$changecount++;
						if (!array_key_exists($nextchange, $codeline_changes_forward)) break;
						$nextchange = $codeline_changes_forward[$nextchange];
					}
				}
				
			}
		}

		return array ('nodes' => $nodes, 'edges' => $edges);
	}

	private function _normalize_revision_changes (&$log)
	{
		$deleted = array();
		$movedfrom = array();
		$movedto = array();
		$copiedfrom = array();
		$copiedto = array();
		$added = array();
		$modified = array();

		$copyinfo = array();
		$currev = $log['rev'];

		foreach ($log['paths'] as $p)
		{
			if (!array_key_exists('action', $p) || !array_key_exists('path', $p)) continue;

			$action = $p['action'];
			$path = $p['path'];

			if ($action == 'A')
			{
				if (array_key_exists('copyfrom', $p))
				{
					// $path:$p['rev'] has been copied to $copyinfo:$currev 
					$copyinfo[$p['copyfrom']] = array($path, $p['rev'], $currev);
				}
				else
				{
					$added[$path] = 1;
				}
			}
			else if ($action == 'D')
			{
				$deleted[$path] = 1;
			}
			else if ($action == 'M')
			{
				$modified[$path] = 1;
			}
		}

		foreach ($copyinfo as $op => $ci)
		{
			if (array_key_exists($op, $deleted))
			{
				/* moved */
				$movedfrom[$ci[0]] = array($op, $ci[1], $ci[2]); // $ci[0] has been moved from $op:$ci[1]
				$movedto[$op] = $ci; // $op:$ci[1] has been moved to $ci[0]:$ci[2]
				unset ($deleted[$op]);
			}
			else
			{
				/* copied */
				$copiedfrom[$ci[0]] = array($op, $ci[1], $ci[2]);
				$copiedto[$op] = $ci;
			}
		}

		$result = new stdClass();
		$result->modified = $modified;
		$result->deleted = $deleted;
		$result->copiedfrom = $copiedfrom;
		$result->copiedto = $copiedto;
		$result->movedfrom = $movedfrom;
		$result->movedto = $movedto;
		$result->added = $added;

		return $result;
	}

	private function _add_revision_transition (&$trans, $from, $to)
	{
		if (array_key_exists($from, $trans)) array_push ($trans[$from], $to);
		else $trans[$from] = array ($to);
	}

	private function _add_revision_trackset (&$trackset, $trapath, $rev, $act)
	{
		if (array_key_exists($trapath, $trackset)) 
		{
			// keep array elements sorted by the revision number
			$arr = &$trackset[$trapath];
			$cnt = count($arr);
			while ($cnt > 0)
			{
				$x = $arr[--$cnt];
				if ($x[0] <= $rev) 
				{
					array_splice ($trackset[$trapath], $cnt + 1, 0, array(array($rev, $act)));
					return;
				}
			}
			array_splice ($trackset[$trapath], 0, 0, array(array($rev, $act)));
		}
		else $trackset[$trapath] = array(array($rev, $act));
	}

	private function _backtrack_revision (&$trackset, $trapath, $rev)
	{
		if (array_key_exists($trapath, $trackset))
		{
			$arr = $trackset[$trapath];

			$cnt = count($arr);
			while ($cnt > 0)
			{
				$br = $arr[--$cnt];
				if ($br[0] < $rev)
				{
					if ($br[1] == 'D') return -1;
					return $br[0];
				}
			}
		}

		return -1;
	}

	function revisionGraph ($projectid, $path, $rev = SVN_REVISION_HEAD)
	{
		$h = $this->getHistory ($projectid, $path, $rev);
		$log = &$h['history'];
		if ($h['type'] == 'dir')
		{
			$url = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}");
			$log = @svn_log ($url, 1, $rev, 0, SVN_DISCOVER_CHANGED_PATHS);
			if ($log === FALSE || count($log) <= 0) return FALSE;
		}

		$nodeids = array ();
		$nodes = array ();
		$edges = array ();

//print_r ($log);
		$trackset = array ();
		$delset =  array();
		$trans = array();

		$this->_add_revision_trackset ($trackset, $path, -1, '');
		foreach ($log as $l)
		{
			$currev = $l['rev'];
			$changes = $this->_normalize_revision_changes ($l);

			/*
			print "==== {$l['rev']} ====\n";
			foreach ($changes->deleted as $op => $one) print "DELETED ===> $op\n";
			foreach ($changes->added as $op => $one) print "ADDED ===> $op\n";
			foreach ($changes->modified as $op => $one) print "MODIFIED ===> $op\n";
			foreach ($changes->movedfrom as $np => $op) print "MOVED ===> {$op[0]},{$op[1]} -> $np\n";
			foreach ($changes->copiedfrom as $np => $op) print "COPIED ===> {$op[0]},{$op[1]} -> $np\n";
			//foreach ($changes->movedto as $op=> $np) print "MOVED ===> $op -> $np\n";
			//foreach ($changes->copiedto as $op => $np) print "COPIED ===> $op -> $np\n";
			*/

//print_r ($changes);
//print "---------------------------------\n";
			foreach ($trackset as $trapath => $dummy)
			{
				if (array_key_exists($trapath, $changes->copiedto))
				{
					/* $trapath has been copied to a new file */
					$newpath = $changes->copiedto[$trapath][0];
					$oldrev = $changes->copiedto[$trapath][1];

					$this->_add_revision_trackset ($trackset, $trapath, $oldrev, '');
					$this->_add_revision_trackset ($trackset, $newpath, $currev, '');

					$this->_add_revision_transition ($trans, "{$oldrev},{$trapath}", "CP,{$currev},{$newpath}");
				}
				else if (array_key_exists($trapath, $changes->copiedfrom))
				{
					/* something else has been copied to become $trapath */
					$oldpath = $changes->copiedfrom[$trapath][0];
					$oldrev = $changes->copiedfrom[$trapath][1];

					$this->_add_revision_trackset ($trackset, $oldpath, $oldrev, '');
					$this->_add_revision_trackset ($trackset, $trapath, $currev, '');

					$this->_add_revision_transition ($trans, "{$oldrev},{$oldpath}", "CP,{$currev},{$trapath}");
				}
				else if (array_key_exists($trapath, $changes->movedto))
				{
					$newpath = $changes->movedto[$trapath][0];
					$oldrev = $changes->movedto[$trapath][1];

					$this->_add_revision_trackset ($trackset, $trapath, $oldrev, 'D');
					$this->_add_revision_trackset ($trackset, $newpath, $currev, '');

					$this->_add_revision_transition ($trans, "{$oldrev},{$trapath}", "MV,{$currev},{$newpath}");
				}
				else if (array_key_exists($trapath, $changes->movedfrom))
				{

					/* something else has been moved to become $trapath */
					$oldpath = $changes->movedfrom[$trapath][0];
					$oldrev = $changes->movedfrom[$trapath][1];

					$this->_add_revision_trackset ($trackset, $oldpath, $oldrev, 'D');
					$this->_add_revision_trackset ($trackset, $trapath, $currev, '');

					$this->_add_revision_transition ($trans, "{$oldrev},{$oldpath}", "MV,{$currev},{$trapath}");
				}
				else if (array_key_exists($trapath, $changes->deleted))
				{
					$this->_add_revision_transition ($trans, "${currev},{$trapath}", "RM,-1,<<deleted>>");
					$delset[$trapath] = 1;
					$this->_add_revision_trackset ($trackset, $trapath, $currev, 'D');
				}
				else if (array_key_exists($trapath, $changes->added))
				{
					$this->_add_revision_trackset ($trackset, $trapath, $currev, '');

					if (array_key_exists($trapath, $delset))
					{
						$this->_add_revision_transition ($trans, "-1,<<deleted>>", "AD,{$currev},{$trapath}");
						unset ($delset[$trapath]);
					}
					else 
					{
						$this->_add_revision_transition ($trans, "-1,<<start>>", "AD,{$currev},{$trapath}");
					}
				}
				else if (array_key_exists($trapath, $changes->modified))
				{
					$this->_add_revision_trackset ($trackset, $trapath, $currev, '');
					$oldrev = $this->_backtrack_revision ($trackset, $trapath, $currev);
					$this->_add_revision_transition ($trans, "{$oldrev},{$trapath}", "MF,{$currev},{$trapath}");
				}
			}
		}

//print_r ($trackset);
//print_r ($trans);
//print_r ($trackset);

		$mf_cand = array();
		foreach ($trans as $transfrom => $transtos)
		{
			$x = explode (',', $transfrom, 2);
			$frompath = $x[1];
			$fromrev = $x[0];

			$numtranstos = count($transtos);
			for ($i = 0; $i < $numtranstos; $i++)
			{
				$transto = $transtos[$i];

				$x = explode (',', $transto, 3);
				$act = $x[0];
				$topath = $x[2];
				$torev = $x[1];

				switch ($act)
				{
					case 'CP':
						$br = $this->_backtrack_revision ($trackset, $frompath, $fromrev);
						if ($br >= 0)
						{
							$id1 = $this->_add_rg_node ($nodeids, $nodes, "{$frompath}:{$br}");
							$id2 = $this->_add_rg_node ($nodeids, $nodes, "{$frompath}:{$fromrev}", 'X');
							$this->_add_rg_edge ($edges, $id1, $id2, '');
						}

						$id1 = $this->_add_rg_node ($nodeids, $nodes, "{$frompath}:{$fromrev}");
						$id2 = $this->_add_rg_node ($nodeids, $nodes, "{$topath}:{$torev}", 'X');
						$this->_add_rg_edge ($edges, $id1, $id2, 'copied');
						break;
					case 'MV':
						$br = $this->_backtrack_revision ($trackset, $frompath, $fromrev);
						if ($br >= 0)
						{
							$id1 = $this->_add_rg_node ($nodeids, $nodes, "{$frompath}:{$br}");
							$id2 = $this->_add_rg_node ($nodeids, $nodes, "{$frompath}:{$fromrev}", 'X');
							$this->_add_rg_edge ($edges, $id1, $id2, '');
						}

						$id1 = $this->_add_rg_node ($nodeids, $nodes, "{$frompath}:{$fromrev}");
						$id2 = $this->_add_rg_node ($nodeids, $nodes, "{$topath}:{$torev}", 'X');
						$this->_add_rg_edge ($edges, $id1, $id2, 'moved');
						break;
					case 'RM':
						$br = $this->_backtrack_revision ($trackset, $frompath, $fromrev);
						if ($br >= 0)
						{
							$id1 = $this->_add_rg_node ($nodeids, $nodes, "{$frompath}:{$br}");
							$id2 = $this->_add_rg_node ($nodeids, $nodes, "{$frompath}:{$fromrev}", 'X');
							$this->_add_rg_edge ($edges, $id1, $id2, '');
						}

						$id1 = $this->_add_rg_node ($nodeids, $nodes, "{$frompath}:{$fromrev}", 'D');
						//$id2 = $this->_add_rg_node ($nodeids, $nodes, $topath);
						//$this->_add_rg_edge ($edges, $id1, $id2, 'deleted');
						break;
					case 'AD':
						//$id1 = $this->_add_rg_node ($nodeids, $nodes, $frompath);
						$id2 = $this->_add_rg_node ($nodeids, $nodes, "{$topath}:{$torev}", 'A');
						//$this->_add_rg_edge ($edges, $id1, $id2, '');
						break;
					case 'MF':
						/*
						$id1 = $this->_add_rg_node ($nodeids, $nodes, "{$frompath}:{$fromrev}");
						$id2 = $this->_add_rg_node ($nodeids, $nodes, "{$topath}:{$torev}");
						$this->_add_rg_edge ($edges, $id1, $id2, '');
						*/
						if (array_key_exists($frompath, $mf_cand))
						{
							$z = &$mf_cand[$frompath];
							$z[1] = $topath;
							$z[2] = $torev;
							$z[3]++;
						}
						else
						{
							if ($fromrev <= -1) $fromrev = $torev;
							$mf_cand[$frompath] = array ($fromrev, $topath, $torev, 1);
						}
						break;
				}
			}
		}

		foreach ($mf_cand as $frompath => $ti)
		{
			$fromrev = $ti[0];
			$topath = $ti[1];
			$torev = $ti[2];
			$num_changes = $ti[3];
			$id1 = $this->_add_rg_node ($nodeids, $nodes, "{$frompath}:{$fromrev}");
			$id2 = $this->_add_rg_node ($nodeids, $nodes, "{$topath}:{$torev}", 'X');
			$this->_add_rg_edge ($edges, $id1, $id2, "{$num_changes} change(s)");
		}

		return array ('nodes' => $nodes, 'edges' => $edges);
	}

	function zipSubdir ($projectid, $path, $rev, $topdir)
	{
		// codepot_zip_dir() uses ZipArchive. Check if the class
		// exists not to perform any intermediate steps when it's
		// not available.
		if (!class_exists('ZipArchive')) return FALSE;
		
		$orgurl = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");

		$workurl = ($path == '')? $orgurl: "{$orgurl}@"; // trailing @ for collision prevention
		$info = @svn_info($workurl, FALSE, $rev);
		if ($info === FALSE || $info === NULL || count($info) != 1) 
		{
			// If a URL is at the root of repository and the url type is file://
			// (e.g. file:///svnrepo/codepot/ where /svnrepo/codepot is a project root),
			// some versions of libsvn end up with an assertion failure like
			//  ... libvvn_subr/path.c:114: svn_path_join: Assertion `svn_path_is_canonical(base, pool)' failed. 
			// 
			// Since the root directory is guaranteed to exist at the head revision,
			// the information can be acquired without using a peg revision.
			// In this case, a normal operational revision is used to work around
			// the assertion failure. Other functions that has to deal with 
			// the root directory should implement this check to work around it.
			//
			if ($rev == SVN_REVISION_HEAD || $path == '') return FALSE;

			// rebuild the URL with a peg revision and retry it.
			$workurl = "{$orgurl}@{$rev}";
			$info = @svn_info($workurl, FALSE, $rev);
			if ($info === FALSE || $info === NULL || count($info) != 1)  return FALSE;
		}

		// pass __FILE__ as the first argument so that tempnam creates a name
		// in the system directory. __FILE__ can never be a valid directory.
		$tfname = @tempnam(__FILE__, 'codepot-fetch-folder-');
		if ($tfname === FALSE) return FALSE;

		$actual_tfname = $tfname . '.d';
		codepot_delete_files ($actual_tfname, TRUE); // delete the directory in case it exists

		if (@svn_checkout ($workurl, $actual_tfname, $rev, 0) === FALSE)
		{
			codepot_delete_files ($actual_tfname, TRUE);
			@unlink ($tfname);
			return FALSE;
		}

		$zip_name = "{$tfname}.zip";
		if (codepot_zip_dir ($zip_name, $actual_tfname, $topdir, array('.svn')) === FALSE) 
		{
			codepot_delete_files ($actual_tfname, TRUE);
			@unlink ($tfname);
			@unlink ($zip_name); // delete potentially residual zip file 
			return FALSE;
		}

		// temporary files are not deleted here.
		// the caller must clear temporary files.

		return $tfname;
	}

	static function createRepo ($projectid, $repodir, $cfgdir, $api)
	{
		$projdir = "{$repodir}/{$projectid}";
		if (@svn_repos_create($projdir) === FALSE) return FALSE;

		$hooks = array (
			  "pre-commit",
			  "start-commit",
			  "post-commit",
			  "pre-revprop-change",
			  "post-revprop-change"
		);

		foreach ($hooks as $hook)
		{
			// copy hook scripts to the top repository directory
			// overwriting existing scripts are ok as they are
			// just updated to the latest scripts anyway.
			$contents = @file_get_contents("{$cfgdir}/${hook}");
			if ($contents === FALSE)
			{
				$this->deleteDirectory ($projdir);
				return FALSE;
			}

			if (@file_put_contents("{$repodir}/${hook}", str_replace('%API%', $api, $contents)) === FALSE)
			{
				$this->deleteDirectory ($projdir);
				return FALSE;
			}

			// install the hook script to the new project repository
			if (@chmod("{$repodir}/{$hook}", 0755) === FALSE ||
			    @symlink("../../{$hook}", "{$repodir}/{$projectid}/hooks/${hook}") === FALSE)
			{
				$this->deleteDirectory ($projdir);
				return FALSE;
			}
		}

		return TRUE;
	}

	static function deleteRepo ($projectid, $repodir)
	{
		return self::_deleteDirectory("{$repodir}/{$projectid}");
	}
}

?>
