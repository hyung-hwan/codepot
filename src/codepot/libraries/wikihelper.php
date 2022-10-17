<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); 
 
class WikiHelper
{
	private static $double_hash_table = array (
		'##P' => '__PROJECT__',
		'##W' => '__WIKI__',
		'##I' => '__ISSUE__',
		'##C' => '__CODE__',
		'##F' => '__FILE__'
	);

	function __construct ()
	{
	}

	function parseLink ($name, $projectid, $converter)
	{
		if (preg_match ('/^#R([[:digit:]]+)$/', $name, $matches) == 1)
		{
			// #R123 -> translate it to code revision.
			$link = "code/revision/{$projectid}/2e/{$matches[1]}";
			return $link;
		}
		else if (preg_match ('/^#I([[:digit:]]+)$/', $name, $matches) == 1)
		{
			// #I123 -> translate it to issue number.
			$num_hex = $converter->AsciiToHex ($matches[1]);
			$link = "issue/show/{$projectid}/{$num_hex}";
			return $link;
		}
		else if (preg_match ('/^#C([[:digit:]]*)(\/.*)$/', $name, $matches) == 1)
		{
			// #C/XXX... -> translate it to a code file
			// #C123/XXX... -> translate it to a code file at revision 123
			$file_name = $converter->AsciiToHex ($matches[2]);
			$link = "code/file/{$projectid}/{$file_name}";
			if (strlen($matches[1]) > 0) $link .= "/" . $matches[1];
			return $link;
		}
		else if (preg_match ('/^#F(.+)$/', $name, $matches) == 1)
		{
			// #Ffilename.tgz -> translate it to a file download
			$file_name = $converter->AsciiToHex ($matches[1]);
			$link = "file/show/{$projectid}/{$file_name}";
			return $link;
		}
		else if (preg_match ('/^#P(.+)$/', $name, $matches) == 1)
		{
			// #Pprojectid -> translate it to a project home
			$project_name = $matches[1]; // no AsciiToHex
			$link = "project/home/{$project_name}";
			return $link;
		}
		else if (preg_match ('/^#W(.+)$/', $name, $matches) == 1)
		{
			// #Pprojectid -> translate it to a wiki name
			$wiki_name = $converter->AsciiToHex ($matches[1]);
			$link = "wiki/show/{$projectid}/{$wiki_name}";
			return $link;
		}

		$r = $this->double_hash_to_reserved($name);
		if ($r !== FALSE) 
		{
			$name = $r;
			$reserved = TRUE;
		}
		else 
		{
			$reserved = $this->_is_reserved ($name, TRUE);
		}

		if ($reserved)
		{
			$ex0 = $this->_trans_reserved ($name);

			//redirect ("{$ex0}/home/{$projectid}");

			//$link->type = $ex0;
			//$link->target = 'home';
			//$link->projectid = $projectid;
			//if ($link->projectid == NULL) return FALSE;
			//$link->extra = NULL;

			if ($projectid == NULL) return FALSE;
			$link = "{$ex0}/home/{$projectid}";
			return $link;
		}
		else
		{
			$ex = explode (':', $name);
			$cnt = count($ex);
			if ($cnt >= 1 && ($r = $this->double_hash_to_reserved($ex[0])) !== FALSE) $ex[0] = $r;

			if ($cnt == 2)
			{
				if ($ex[0] == '__LOCALURL__')
				{
					return ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')? 'https': 'http') . '://' . $_SERVER['HTTP_HOST'] . $ex[1];
				}
				else if ($this->_is_reserved ($ex[0], TRUE))
				{
					$ex0 = $this->_trans_reserved ($ex[0]);
					$ex1 = ($ex[1] == '')? $projectid: $ex[1];

					if ($ex1 == NULL) return FALSE;
					$link = "{$ex0}/home/{$ex1}";
					return $link;
				}
			}
			else if ($cnt == 3)
			{
				if ($this->_is_reserved ($ex[0], TRUE) && 
				    $ex[0] != '__PROJECT__' && $ex[0] != '__CODE__')
				{
					$ex0 = $this->_trans_reserved ($ex[0]);
					$ex1 = ($ex[1] == '')? $projectid: $ex[1];
					$ex2 = $converter->AsciiToHex ($ex[2]);

					if ($ex1 == NULL) return FALSE;
					$link = "{$ex0}/show/{$ex1}/{$ex2}";
					return $link;
				}
			}
			else if ($cnt == 4)
			{
				if ($ex[0] == '__CODE__')
				{
					$ex0 = $this->_trans_reserved ($ex[0]);
					$ex1 = ($ex[1] == '')? $projectid: $ex[1];
					if ($ex[2] == 'file' || $ex[2] == 'history' || 
					    $ex[2] == 'blame' || $ex[2] == 'diff')
					{
						// __CODE__|project001|file|file001.txt

						$ex3 = $converter->AsciiToHex ($ex[3]);

						if ($ex1 == NULL) return FALSE;
						$link = "{$ex0}/{$ex[2]}/{$ex1}/{$ex3}";
						return $link;
					}
					else if ($ex[2] == 'revision')
					{
						// __CODE__::revision:178.
						// 2e for the root directory.
						if ($ex1 == NULL) return FALSE;
						$link = "{$ex0}/{$ex[2]}/{$ex1}/2e/{$ex[3]}";
						return $link;
					}

					return FALSE;
				}
				else if ($ex[0] == '__WIKI__')
				{
					// __WIKI__:projectid:wikiname:attachment

					$ex0 = $this->_trans_reserved ($ex[0]);
					$ex1 = ($ex[1] == '')? $projectid: $ex[1];
					$extra = $converter->AsciiToHex ("{$link->projectid}:{$ex[2]}:{$ex[3]}");
					$link = "{$ex0}/attachment0/{$ex1}/{$extra}";
		
					return $link;
				}
			}
			else if ($cnt == 5)
			{
				if ($ex[0] == '__CODE__')
				{
					$ex0 = $this->_trans_reserved ($ex[0]);
					$ex1 = ($ex[1] == '')? $projectid: $ex[1];
					if ($ex[2] == 'file')
					{
						// __CODE__|project001|file|123|file001.txt

						$ex4 = $converter->AsciiToHex($ex[4]);
						if ($ex1 == NULL) return FALSE;
						$link = "{$ex0}/{$ex[2]}/{$ex1}/{$ex4}/{$ex[3]}";
						return $link;
					}

					return FALSE;
				}
			}
		}

		return NULL; // not an error
	}

	function _trans_reserved ($name)
	{
		// trim off leading and trailing double underscores blindly.
		// actually it trims off the first and the last two characters
		// each.
		return substr (strtolower($name), 2, strlen($name) -  4);
	}

	function _is_reserved ($name, $exact)
	{
		if ($exact)
		{
			return $name == '__PROJECT__' ||
			       $name == '__WIKI__' ||
			       $name == '__FILE__' ||
			       $name == '__CODE__' ||
			       $name == '__ISSUE__';
		}
		else
		{
			return substr($name, 0, 11) == '__PROJECT__' ||
			       substr($name, 0, 8) == '__WIKI__' ||
			       substr($name, 0, 8) == '__FILE__' ||
			       substr($name, 0, 8) == '__CODE__' ||
			       substr($name, 0, 9) == '__ISSUE__';
		}
	}

	private function double_hash_to_reserved ($name)
	{
		if (array_key_exists ($name, self::$double_hash_table)) return self::$double_hash_table[$name];
		return FALSE;
	}
} 
?>
