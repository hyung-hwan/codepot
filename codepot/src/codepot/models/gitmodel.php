<?php

$CI = &get_instance();
$CI->load->model('CodeRepoModel');

class GitModel extends CodeRepoModel
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
			$canonical = substr ($canonical, 0, -1);
		}
		return $canonical;
	}

	function getFile ($projectid, $path, $rev = SVN_REVISION_HEAD, $type_and_name_only = FALSE)
	{
		return FALSE;
	}

	function getBlame ($projectid, $path, $rev = SVN_REVISION_HEAD)
	{
		return FALSE;
	}

	function getHistory ($projectid, $path, $rev = SVN_REVISION_HEAD)
	{
		return FALSE;
	}

	function storeFile ($projectid, $path, $committer, $commit_message, $text)
	{
		return FALSE;
	}

	function importFiles ($projectid, $path, $committer, $commit_message, $files, $uploader)
	{
		return FALSE;
	}

	function deleteFiles ($projectid, $path, $committer, $commit_message, $files)
	{
		return FALSE;
	}

	function renameFiles ($projectid, $path, $committer, $commit_message, $files)
	{
		return FALSE;
	}

	function getRevHistory ($projectid, $path, $rev)
	{
		return FALSE;
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
		return FALSE;
	}

	function getPrevRev ($projectid, $path, $rev)
	{
		return FALSE;
	}

	function getNextRev ($projectid, $path, $rev)
	{
		return FALSE;
	}

	function getHeadRev ($projectid, $path, $rev)
	{
		return FALSE;
	}

	function getRevProp ($projectid, $rev, $prop)
	{
		return FALSE;
	}

	function setRevProp ($projectid, $rev, $prop, $propval, $user)
	{
		return FALSE;
	}

	function killRevProp ($projectid, $rev, $prop, $user)
	{
		return FALSE;
	}

	function mapRevPropToRev ($projectid, $revprop_name)
	{
		return FALSE;
	}

	function findRevWithRevProp ($projectid, $revprop_name, $revprop_value)
	{
		return FALSE;
	}


	function listProps ($projectid, $path, $rev)
	{
		return FALSE;
	}

	function getProp ($projectid, $path, $rev, $prop)
	{
		return FALSE;
	}

	function clocRevByLang ($projectid, $path, $rev)
	{
		return FALSE;
	}

	function clocRevByFile ($projectid, $path, $rev)
	{
		return FALSE;
	}

	
	function revisionGraph ($projectid, $path, $rev = SVN_REVISION_HEAD)
	{
		return FALSE;
	}

	function zipSubdir ($projectid, $path, $rev, $topdir)
	{
		return FALSE;
	}

	static function createRepo ($projectid, $repodir, $cfgdir, $api)
	{
		$projdir = "{$repodir}/{$projectid}";
		if (@git_repository_init($projdir, TRUE) === FALSE) return FALSE;
	}

	static function deleteRepo ($projectid, $repodir)
	{
		return $this->deleteDirectory("{$repodir}/{$projectid}");
	}
}

?>
