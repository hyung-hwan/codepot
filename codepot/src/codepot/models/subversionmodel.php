<?php

class SubversionModel extends Model
{
	function SubversionModel ()
	{
		parent::Model ();
	}

	function getList ($projectid, $subdir = '', $rev = SVN_REVISION_HEAD, $recurse = FALSE) 
	{
		$path = 'file:///' . CODEPOT_SVNREPO_DIR . '/' . $projectid;
		if ($subdir != '') $path .= '/' . $subdir;

		$list = @svn_ls ($path, $rev, $recurse);
		if ($list === FALSE) return FALSE;

		return $list;
	}

	function getFile ($projectid, $file, $rev = SVN_REVISION_HEAD)
	{
		$path = 'file:///' . CODEPOT_SVNREPO_DIR .  '/' . $projectid . '/' . $file;

		$info = @svn_ls ($path, $rev, FALSE);
		if ($info === FALSE) return FALSE;

		$last = substr(strrchr($path, '/'), 1);
		if (array_key_exists ($last, $info) === FALSE) return FALSE;
		$fileinfo = $info[$last];

		$str = @svn_cat ($path, $rev);
		if ($str === FALSE) return FALSE;

		$fileinfo['content'] = $str;
		return $fileinfo;
	}

	function getBlame ($projectid, $file, $rev = SVN_REVISION_HEAD)
	{
		$path = 'file:///' . CODEPOT_SVNREPO_DIR .  '/' . $projectid . '/' . $file;

		$info = @svn_ls ($path, $rev, FALSE);
		if ($info === FALSE) return FALSE;

		$last = substr(strrchr($path, '/'), 1);
		if (array_key_exists ($last, $info) === FALSE) return FALSE;
		$fileinfo = $info[$last];

		$str = @svn_blame ($path, $rev);
		if ($str === FALSE) return FALSE;

		$fileinfo['content'] = $str;
		return $fileinfo;
	}

	function getHistory ($projectid, $file, $rev = SVN_REVISION_HEAD)
	{
		$path = 'file:///' . CODEPOT_SVNREPO_DIR .  '/' . $projectid . '/' . $file;

		$last = substr(strrchr($path, '/'), 1);
		$info['name'] = $last;
		$fileinfo = $info;

		$str = @svn_log ($path, 1, $rev);
		if ($str === FALSE) return FALSE;

		$fileinfo['history'] = $str;
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

	function getDiff ($projectid, $file, $rev1, $rev2)
	{
		$path = 'file:///' . CODEPOT_SVNREPO_DIR .  '/' . $projectid . '/' . $file;

		$info = @svn_ls ($path, $rev1, FALSE);
		if ($info === FALSE) return FALSE;

		$last = substr(strrchr($path, '/'), 1);
		if (array_key_exists ($last, $info) === FALSE) return FALSE;
		$fileinfo = $info[$last];

		$rev1 = $info[$last]['created_rev'];

		if ($rev2 <= 0)
		{
			/*
			$log = @svn_log ($path, $rev1, SVN_REVISION_INITIAL, 2);
			if ($log === FALSE) return FALSE;
			if (count($log) < 2) return FALSE;
			$rev2 = $log[1]['rev'];
			*/
			$rev2 = $rev1 - 1;
			if ($rev2 <= 0) $rev2 = $rev1;
		}

		$info2 = @svn_ls ($path, $rev2, FALSE);
		if ($info2 === FALSE) 
		{
			$rev2 = $rev1;
			$info2 = @svn_ls ($path, $rev2, FALSE);
			if ($info2 === FALSE) return FALSE;
		}

		if (array_key_exists ($last, $info2) === FALSE) return FALSE;
		$rev2 = $info2[$last]['created_rev'];

		list($diff, $errors) = @svn_diff ($path, $rev2, $path, $rev1);
		if (!$diff) return FALSE;

		fclose($errors);

		$fileinfo['content'] = $this->_get_diff ($diff, FALSE, FALSE);
		fclose ($diff);

/*
print_r ($info[$last]);
print_r ($info2[$last]);
*/
		$fileinfo['against'] = $info2[$last];
		return $fileinfo;
	}

	function getPrevRev ($projectid, $file, $rev)
	{
		$path = 'file:///' . CODEPOT_SVNREPO_DIR .  '/' . $projectid . '/' . $file;

		$info = @svn_log ($path, $rev, SVN_REVISION_INITIAL, 2, SVN_OMIT_MESSAGES);
		if ($info === FALSE) return $rev;

		$count = count($info);
		if ($count <= 0) return $rev;
		if ($count == 1) return $info[0]['rev'];
		
		return $info[1]['rev'];
	}

	function getNextRev ($projectid, $file, $rev)
	{
		$path = 'file:///' . CODEPOT_SVNREPO_DIR .  '/' . $projectid . '/' . $file;

		$info = @svn_log ($path, SVN_REVISION_HEAD, $rev, 0, SVN_OMIT_MESSAGES);
		if ($info === FALSE) return $rev;

		$count = count($info);
		if ($count <= 0) return $rev;
		if ($count == 1) return $info[0]['rev'];
		
		return $info[$count-2]['rev'];
	}

	function getHeadRev ($projectid, $file)
	{
		$path = 'file:///' . CODEPOT_SVNREPO_DIR .  '/' . $projectid . '/' . $file;

		$info = @svn_log ($path, SVN_REVISION_HEAD, SVN_REVISION_INITIAL, 1, SVN_OMIT_MESSAGES);
		if ($info === FALSE) return FALSE;
		if (count($info) != 1) return FALSE;
		return $info[0]['rev'];
	}

}

?>
