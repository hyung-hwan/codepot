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

	function getFile ($projectid, $path, $rev = SVN_REVISION_HEAD)
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

		if ($info[0]['kind'] == SVN_NODE_FILE) 
		{
			$lsinfo = @svn_ls ($workurl, $rev, FALSE, TRUE);
			if ($lsinfo === FALSE) return FALSE;

			if (array_key_exists ($info[0]['path'], $lsinfo) === FALSE) return FALSE;
			$fileinfo = $lsinfo[$info[0]['path']];

			$str = @svn_cat ($workurl, $rev);
			if ($str === FALSE) return FALSE;

			$log = @svn_log ($workurl, 
				$fileinfo['created_rev'], 
				$fileinfo['created_rev'],
				1, SVN_DISCOVER_CHANGED_PATHS);
			if ($log === FALSE) return FALSE;

			$prop = @svn_proplist ($workurl, FALSE, $rev);
			if ($prop === FALSE) return FALSE;

			if (array_key_exists ($orgurl, $prop))
			{
				$fileinfo['properties'] = $prop[$orgurl];
			}
			else $fileinfo['properties'] = NULL;

			$fileinfo['fullpath'] = substr (
				$info[0]['url'], strlen($info[0]['repos']));
			$fileinfo['content'] = $str;
			$fileinfo['logmsg'] = (count($log) > 0)? $log[0]['msg']: '';
 
			return $fileinfo;
		}
		else if ($info[0]['kind'] == SVN_NODE_DIR) 
		{
			$list = @svn_ls ($workurl, $rev, FALSE, TRUE);
			if ($list === FALSE) return FALSE;

			if ($info[0]['revision'] <= 0) $log = array();
			else
			{
				$log = @svn_log ($workurl, 
					$info[0]['revision'], 
					$info[0]['revision'],
					1, SVN_DISCOVER_CHANGED_PATHS);
				if ($log === FALSE) return FALSE;
			}

			$prop = @svn_proplist ($workurl, FALSE, $rev);
			if ($prop === FALSE) return FALSE;

			if (array_key_exists ($orgurl, $prop))
				$fileinfo['properties'] = $prop[$orgurl];
			else $fileinfo['properties'] = NULL;

			$fileinfo['fullpath'] = substr (
				$info[0]['url'], strlen($info[0]['repos']));
			$fileinfo['name'] =  $info[0]['path'];
			$fileinfo['type'] = 'dir';
			$fileinfo['size'] = 0;
			$fileinfo['created_rev'] = $info[0]['revision'];
			if (array_key_exists ('last_changed_author', $info[0]) === FALSE)
				$fileinfo['last_author'] = '';
			else
				$fileinfo['last_author'] = $info[0]['last_changed_author'];
			$fileinfo['content'] = $list;
			$fileinfo['logmsg'] = (count($log) > 0)? $log[0]['msg']: '';
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

		if ($info[0]['kind'] != SVN_NODE_FILE) return FALSE;

		$lsinfo = @svn_ls ($workurl, $rev, FALSE, TRUE);
		if ($lsinfo === FALSE) return FALSE;

		if (array_key_exists ($info[0]['path'], $lsinfo) === FALSE) return FALSE;
		$fileinfo = $lsinfo[$info[0]['path']];

		$str = @svn_blame ($workurl, $rev);
		if ($str === FALSE) return FALSE;

		$log = @svn_log ($workurl, 
			$fileinfo['created_rev'], 
			$fileinfo['created_rev'],
			1, SVN_DISCOVER_CHANGED_PATHS);
		if ($log === FALSE) return FALSE;

		$fileinfo['fullpath'] = substr (
			$info[0]['url'], strlen($info[0]['repos']));
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
			//
			$info = @svn_info ($url, FALSE, $rev);
			if ($info === FALSE || count($info) != 1) 
			{
				//
				// Try further with a given operatal revision 
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
			$fileinfo['created_rev'] = $info[0]['revision'];
			$fileinfo['last_author'] = $info[0]['last_changed_rev'];
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
		 * It skipps composition if the path indicates the project root. Read the comment
		 * in getFile() to know more about this skipping.
		 */
		if ($rev != SVN_REVISION_HEAD && $path != '') $url = $url . '@' . $rev;

		$info = @svn_info ($url, FALSE, $rev);
		if ($info === FALSE || count($info) != 1) return FALSE;

		if ($info[0]['kind'] == SVN_NODE_FILE) 
		{
			// do not revision history this for a file.
			return FALSE;
		/*
			$lsinfo = @svn_ls ($url, $rev, FALSE, TRUE);
			if ($lsinfo === FALSE) return FALSE;

			if (array_key_exists ($info[0]['path'], $lsinfo) === FALSE) return FALSE;
			$fileinfo = $lsinfo[$info[0]['path']];
			if (!array_key_exists ('fullpath', $fileinfo))
				$fileinfo['fullpath'] = $info[0]['path'];
		*/

		}
		else if ($info[0]['kind'] == SVN_NODE_DIR)
		{
			$fileinfo['fullpath'] = substr (
				$info[0]['url'], strlen($info[0]['repos']));
			$fileinfo['name'] =  $info[0]['path'];
			$fileinfo['type'] = 'dir';
			$fileinfo['size'] = 0;
			$fileinfo['created_rev'] = $info[0]['revision'];
			$fileinfo['last_author'] = $info[0]['last_changed_rev'];
		}
		else return FALSE;

		$log = @svn_log ($url, $rev, $rev, 1, SVN_DISCOVER_CHANGED_PATHS);
		if ($log === FALSE) return FALSE;

		if (count($log) != 1) return FALSE;

		$fileinfo['history'] = $log[0];
		return $fileinfo;
	}

	function _get_diff ($diff, $all, $ent)
	{
		/* copied from websvn */

		if ($all) 
		{
			$ofile = fopen ($oldtname, "r");
			$nfile = fopen ($newtname, "r");
		}

		// Ignore the 4 header lines
		$line = fgets($diff);
		$line = fgets($diff);
		$line = fgets($diff);
		$line = fgets($diff);

		// Get the first real line
		$line = fgets($diff);

		$index = 0;
		$listing = array();

		$curoline = 1;
		$curnline = 1;

		while (!feof($diff)) 
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

						$line = rtrim($nl);
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

						$line = rtrim($nl);
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

			$fin = false;
			while (!feof($diff) && !$fin) 
			{
				$line = fgets($diff);
				if ($line === false || strncmp($line, "@@", 2) == 0) 
				{
					$fin = true;
				} 
				else 
				{
					$listing[$index]["rev1lineno"] = 0;
					$listing[$index]["rev2lineno"] = 0;

					$mod = $line{0};

					$line = rtrim(substr($line, 1));
					if ($ent) $line = replaceEntities($line, $rep);

					//if (strip_tags($line) == '') $line = '&nbsp;';
					//$listing[$index]["rev1line"] = hardspace($line);
					$listing[$index]["rev1line"] = $line;

					//$text = hardspace($line);
					$text = $line;

					switch ($mod) 
					{
						case "-":
							$listing[$index]["rev1diffclass"] = "diffdeleted";
							$listing[$index]["rev2diffclass"] = "diff";

							$listing[$index]["rev1line"] = $text;
							//$listing[$index]["rev2line"] = "&nbsp;";
							$listing[$index]["rev2line"] = '';

							if ($all) {
								fgets($ofile);
								$curoline++;
							}

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

								if ($all) {
									fgets($nfile);
									$curnline++;
								}

								// Don't increment the current index count
								$index--;

							} else {
								$listing[$index]["rev1diffclass"] = "diff";
								$listing[$index]["rev2diffclass"] = "diffadded";

								//$listing[$index]["rev1line"] = "&nbsp;";
								$listing[$index]["rev1line"] = '';
								$listing[$index]["rev2line"] = $text;

								if ($all) {
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

							if ($all) {
								fgets($ofile);
								fgets($nfile);
								$curoline++;
								$curnline++;
							}

							break;
					}
				}

				if (!$fin) {
					$index++;
				}
			}
		}

		// Output the rest of the files
		if ($all) 
		{
			while (!feof($ofile) || !feof($nfile)) 
			{
				$listing[$index]["rev1diffclass"] = "diff";
				$listing[$index]["rev2diffclass"] = "diff";

				$line = rtrim(fgets($ofile));
				if ($ent) $line = replaceEntities($line, $rep);

				if (!feof($ofile)) {
					//$listing[$index]["rev1line"] = hardspace($line);
					$listing[$index]["rev1line"] = $line;
				}
				else {
					//$listing[$index]["rev1line"] = "&nbsp;";
					$listing[$index]["rev1line"] = ''; 
				}

				$line = rtrim(fgets($nfile));
				if ($ent) $line = replaceEntities(rtrim(fgets($nfile)), $rep);

				if (!feof($nfile)) {
					//$listing[$index]["rev2line"] = hardspace($line);
					$listing[$index]["rev2line"] = $line;
				}
				else {
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
	function getDiff ($projectid, $path, $rev1, $rev2)
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

		$fileinfo['content'] = $this->_get_diff ($diff, FALSE, FALSE);
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

}

?>
