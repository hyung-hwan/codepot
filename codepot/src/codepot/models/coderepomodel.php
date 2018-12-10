<?php

class CodeRepoModel extends Model
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

	function _scandir ($dir)
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

	function deleteDirectory($dir)
	{
		if (is_link($dir)) return @unlink($dir);
		if (!file_exists($dir)) return TRUE;
		if (!is_dir($dir)) return @unlink($dir);

		foreach ($this->_scandir($dir) as $item)
		{
			if ($item == '.' || $item == '..') continue;
			if ($this->deleteDirectory($dir . "/" . $item) === FALSE)
			{
				chmod($dir . "/" . $item, 0777);
				if ($this->deleteDirectory($dir . "/" . $item) === FALSE) return FALSE;
			};
		}

		return rmdir($dir);
	}
}
