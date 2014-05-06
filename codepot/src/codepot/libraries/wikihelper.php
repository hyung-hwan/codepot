<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); 
 
class WikiHelper
{
	function WikiHelper ()
	{
	}

	function parseLink ($name, $projectid, $converter)
	{
		if (preg_match ('/^#R([[:digit:]]+)$/', $name, $matches) == 1)
		{
			// #R123 -> translate it to code reivision.
			$link = "code/revision/{$projectid}/2e/{$matches[1]}";
			return $link;
		}
		if ($this->_is_reserved ($name, TRUE))
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

					//redirect ("{$ex0}/home/{$ex1}");

					//$link->type = $ex0;
					//$link->target = 'home';
					//$link->projectid = $ex1;
					//if ($link->projectid == NULL) return FALSE;
					//$link->extra = NULL;

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
					//redirect ("{$ex0}/show/{$ex1}/{$ex2}");

					//$link->type = $ex0;
					//$link->target = 'show';
					//$link->projectid = $ex1;
					//if ($link->projectid == NULL) return FALSE;
					//$link->extra = $ex2;

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
						$ex3 = $converter->AsciiToHex ($ex[3]);
						//redirect ("{$ex0}/{$ex[2]}/{$ex1}/{$ex3}");
						//$link->type = $ex0;
						//$link->target = $ex[2];
						//$link->projectid = $ex1;
						//if ($link->projectid == NULL) return FALSE;
						//$link->extra = $ex3;

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

					//$link->type = $this->_trans_reserved ($ex[0]);
					//$link->target = 'attachment0';
					//$link->projectid = ($ex[1] == '')? $projectid: $ex[1];
					//$link->extra = $converter->AsciiToHex ("{$link->projectid}:{$ex[2]}:{$ex[3]}");

					$ex0 = $this->_trans_reserved ($ex[0]);
					$ex1 = ($ex[1] == '')? $projectid: $ex[1];
					$extra = $converter->AsciiToHex ("{$link->projectid}:{$ex[2]}:{$ex[3]}");
					$link = "{$ex0}/attachment0/{$ex1}/{$extra}";
		
					return $link;
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
			return substr ($name, 0, 11) == '__PROJECT__' ||
			       substr ($name, 0, 8) == '__WIKI__' ||
			       substr ($name, 0, 8) == '__FILE__' ||
			       substr ($name, 0, 8) == '__CODE__' ||
			       substr ($name, 0, 9) == '__ISSUE__';
		}
	}
} 
?>
