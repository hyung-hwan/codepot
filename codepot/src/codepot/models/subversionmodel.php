<?php

class SubversionModel extends Model
{
	function SubversionModel ()
	{
		parent::Model ();
	}

	function _canonical_path($path) 
	{
		$canonical = preg_replace('|/\.?(?=/)|','',$path);
		while (($collapsed = preg_replace('|/[^/]+/\.\./|','/',$canonical,1)) !== $canonical) 
		{
			$canonical = $collapsed;
		}
		$canonical = preg_replace('|^/\.\./|','/',$canonical);
		return $canonical;
	}

	function getFile ($projectid, $path, $rev = SVN_REVISION_HEAD, $type_and_name_only = FALSE)
	{
		//$url = 'file://'.CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}";
		$orgurl = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");

		$workurl = ($path == '')? $orgurl: "{$orgurl}@"; // trailing @ for collision prevention
		$info = @svn_info ($workurl, FALSE, $rev);
		if ($info === FALSE || count($info) != 1) 
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
			$info = @svn_info ($workurl, FALSE, $rev);
			if ($info === FALSE || count($info) != 1)  return FALSE;
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

				$fileinfo['properties'] = array_key_exists($orgurl, $prop)?  $prop[$orgurl]: NULL;
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

				$fileinfo['properties'] = array_key_exists($orgurl, $prop)?  $prop[$orgurl]: NULL;
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
		$info = @svn_info ($workurl, FALSE, $rev);
		if ($info === FALSE || count($info) != 1) 
		{
			if ($rev == SVN_REVISION_HEAD || $path == '') return FALSE;
			$workurl = "{$orgurl}@{$rev}";
			$info = @svn_info ($workurl, FALSE, $rev);
			if ($info === FALSE || count($info) != 1)  return FALSE;
		}

		$info0 = $info[0];
		if ($info0['kind'] != SVN_NODE_FILE) return FALSE;

		$lsinfo = @svn_ls ($workurl, $rev, FALSE, TRUE);
		if ($lsinfo === FALSE) return FALSE;

		if (array_key_exists ($info0['path'], $lsinfo) === FALSE) return FALSE;
		$fileinfo = $lsinfo[$info0['path']];

		$str = @svn_blame ($workurl, $rev);
		if ($str === FALSE) return FALSE;

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
			$info = @svn_info ($url, FALSE, $rev);
			if ($info === FALSE || count($info) != 1) return FALSE;
		}
		else
		{
			$orgrev = $rev;
			$rev = SVN_REVISION_HEAD;

			//
			// Try to get the history from the head revision down.
			// regardless of the given revision.
			//
			$info = @svn_info ($url, FALSE, $rev);
			if ($info === FALSE || count($info) != 1) 
			{
				//
				// Try further with the original revision 
				//
				$rev = $orgrev;
				$info = @svn_info ($url, FALSE, $rev);
				if ($info === FALSE || count($info) != 1) 
				{
					//
					// don't try with a pegged url for a project root 
					//
					if ($path == '') return FALSE; 

					//
					// Retry with a pegged url 
					//
					$url = $url . '@' . $rev;
					$info = @svn_info ($url, FALSE, $rev);
					if ($info === FALSE || count($info) != 1) return FALSE;
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
			$fileinfo['last_author'] = $info[0]['last_changed_author'];
		}
		else return FALSE;

		$log = @svn_log ($url, 1, $rev, 0, SVN_DISCOVER_CHANGED_PATHS);
		if ($log === FALSE) return FALSE;

		$fileinfo['history'] = $log;
		return $fileinfo;
	}

	function getRevHistory ($projectid, $path, $rev)
	{
		//$url = 'file:///'.CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}";
		$url = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");

		/* Compose a URL with a peg revision if a specific revision is given. However, 
		 * It skips composition if the path indicates the project root. Read the comment
		 * in getFile() to know more about this skipping.
		 */
		if ($rev != SVN_REVISION_HEAD && $path != '') $url = $url . '@' . $rev;

		$info = @svn_info ($url, FALSE, $rev);
		if ($info === FALSE || count($info) != 1) return FALSE;

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

					$mod = $line{0};

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
		$info1 = @svn_info ($workurl1, FALSE, $rev1);
		if ($info1 === FALSE || count($info1) != 1) return FALSE;
		if ($info1[0]['kind'] != SVN_NODE_FILE) return FALSE;
		$info2 = @svn_info ($workurl2, FALSE, $rev2);
		if ($info2 === FALSE || count($info1) != 1) return FALSE;
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

		$fileinfo['fullpath'] = substr (
			$info1[0]['url'], strlen($info1[0]['repos']));
		$fileinfo['against']['fullpath'] = substr (
			$info2[0]['url'], strlen($info2[0]['repos']));

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

		return $fileinfo;
	}

	function getPrevRev ($projectid, $path, $rev)
	{
		//$url = 'file:///'.CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}";
		$orgurl = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");

		$workurl = ($path == '')? $orgurl: "{$orgurl}@"; // trailing @ for collision prevention
		$info = @svn_info ($workurl, FALSE, $rev);
		if ($info === FALSE || count($info) != 1) 
		{
			if ($rev == SVN_REVISION_HEAD || $path == '') return $rev;
			$workurl = "{$orgurl}@{$rev}";
			$info = @svn_info ($workurl, FALSE, $rev);
			if ($info === FALSE || count($info) != 1)  return $rev;
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
			$info = @svn_info ($url, FALSE, $log[$count]['rev']);
			if ($info === FALSE) return FALSE;

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

			$info = @svn_info ($workurl, FALSE, $workrev);
			if ($info === FALSE || count($info) != 1)  return FALSE;
		}
		else
		{
			$workurl = "{$orgurl}@";
			$workrev = SVN_REVISION_HEAD;
			$info = @svn_info ($workurl, FALSE, $workrev);
			if ($info === FALSE || count($info) != 1) 
			{
				$workrev = $rev;
				$info = @svn_info ($workurl, FALSE, $workrev);
				if ($info === FALSE || count($info) != 1) 
				{
					if ($rev == SVN_REVISION_HEAD) return FALSE;

					$workurl = "{$orgurl}@{$rev}";
					$info = @svn_info ($workurl, FALSE, $workrev);
					if ($info === FALSE || count($info) != 1) return FALSE;
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
		$url = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}");
		return @svn_revprop_get ($url, $rev, $prop);
	}

	function setRevProp ($projectid, $rev, $prop, $propval, $user)
	{
		$url = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}");

		$orguser = @svn_auth_get_parameter (SVN_AUTH_PARAM_DEFAULT_USERNAME);
		@svn_auth_set_parameter (SVN_AUTH_PARAM_DEFAULT_USERNAME, $user);

		$result = @svn_revprop_set ($url, $rev, $prop, $propval);

		@svn_auth_set_parameter (SVN_AUTH_PARAM_DEFAULT_USERNAME, $orguser);
		return $result;
	}

	function killRevProp ($projectid, $rev, $prop, $user)
	{
		$url = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}");

		$orguser = @svn_auth_get_parameter (SVN_AUTH_PARAM_DEFAULT_USERNAME);
		@svn_auth_set_parameter (SVN_AUTH_PARAM_DEFAULT_USERNAME, $user);

		$result = @svn_revprop_delete ($url, $rev, $prop);

		@svn_auth_set_parameter (SVN_AUTH_PARAM_DEFAULT_USERNAME, $orguser);
		return $result;
	}

	function listProps ($projectid, $path, $rev)
	{
		$orgurl = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");

		$workurl = ($path == '')? $orgurl: "{$orgurl}@"; // trailing @ for collision prevention
		$info = @svn_info ($workurl, FALSE, $rev);

		if ($info === FALSE || count($info) != 1) 
		{
			if ($rev == SVN_REVISION_HEAD || $path == '') return FALSE;

			// rebuild the URL with a peg revision and retry it.
			$workurl = "{$orgurl}@{$rev}";
			$info = @svn_info ($workurl, FALSE, $rev);
			if ($info === FALSE || count($info) != 1)  return FALSE;
		}

		return @svn_proplist ($workurl, 0, $rev);
	}

	function _cloc_revision_by_lang ($projectid, $path, $rev)
	{
		$orgurl = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");

		$workurl = ($path == '')? $orgurl: "{$orgurl}@"; // trailing @ for collision prevention
		$info = @svn_info ($workurl, FALSE, $rev);
		if ($info === FALSE || count($info) != 1) 
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
			$info = @svn_info ($workurl, FALSE, $rev);
			if ($info === FALSE || count($info) != 1)  return FALSE;
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
			$info = @svn_info ($workurl, FALSE, $rev);
			if ($info === FALSE || count($info) != 1) 
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
				$info = @svn_info ($workurl, FALSE, $rev);
				if ($info === FALSE || count($info) != 1)  continue;
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

	function zipSubdir ($projectid, $path, $rev, $topdir)
	{
		// codepot_zip_dir() uses ZipArchive. Check if the class
		// exists not to perform any intermediate steps when it's
		// not available.
		if (!class_exists('ZipArchive')) return FALSE;
		
		$orgurl = 'file://'.$this->_canonical_path(CODEPOT_SVNREPO_DIR."/{$projectid}/{$path}");

		$workurl = ($path == '')? $orgurl: "{$orgurl}@"; // trailing @ for collision prevention
		$info = @svn_info ($workurl, FALSE, $rev);
		if ($info === FALSE || count($info) != 1) 
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
			$info = @svn_info ($workurl, FALSE, $rev);
			if ($info === FALSE || count($info) != 1)  return FALSE;
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
}

?>
