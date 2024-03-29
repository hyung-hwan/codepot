<?php

class CodeRepoModel extends CI_Model
{
	protected $errmsg = '';

	function capture_error ($errno, $errmsg)
	{
		$this->errmsg = $errmsg;
	}

	function getErrorMessage ()
	{
		return $this->errmsg;
	}

	function clearErrorMessage ()
	{
		return $this->errmsg;
	}

	static function _scandir ($dir)
	{
		$files = array ();

		$dh = opendir($dir);
		while (false !== ($filename = readdir($dh)))
		{
			$files[] = $filename;
		}
		closedir ($dh);

		return $files;
	}

	static function _deleteDirectory($dir)
	{
		if (is_link($dir)) return @unlink($dir);
		if (!file_exists($dir)) return TRUE;
		if (!is_dir($dir)) return @unlink($dir);

		foreach (self::_scandir($dir) as $item)
		{
			if ($item == '.' || $item == '..') continue;
			if (self::_deleteDirectory($dir . "/" . $item) === FALSE)
			{
				@chmod($dir . "/" . $item, 0777);
				if (self::_deleteDirectory($dir . "/" . $item) === FALSE) return FALSE;
			};
		}

		return @rmdir($dir);
	}

	function deleteDirectory($dir)
	{
		return self::_deleteDirectory($dir);
	}
}
