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
}

?>
