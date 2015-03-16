<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); 
 
class Converter
{
	function Converter ()
	{
	}

	// convert an ascii string to its hex representation
	function AsciiToHex($ascii)
	{
		$len = strlen($ascii);

/*
		$hex = '';
		for($i = 0; $i < $len; $i++)
			$hex .= str_pad(base_convert(ord($ascii[$i]), 10, 16), 2, '0', STR_PAD_LEFT);
*/

		$hex = '!'; # the new style conversion begins with an exclamation mark.
		for ($i = 0; $i < $len; $i++)
		{

			if ($ascii[$i] == '\\')
			{
				// backslash to double backslashes.
				$seg = '\\\\';
			}
			else if ($ascii[$i] == ':')
			{
				// colon to backslash-colon
				$seg = '\\:';
			}
			else if ($ascii[$i] == '/')
			{
				// slash to colon
				$seg = ':';
			}
			else if ($ascii[$i] == '.')
			{
				// period - no conversion
				$seg = '.';
			}
			else 
			{
				if (preg_match ('/^[A-Za-z0-9]$/', $ascii[$i]) === FALSE)
				{
					$seg = '\\' . str_pad(base_convert(ord($ascii[$i]), 10, 16), 2, '0', STR_PAD_LEFT);
				}
				else
				{
					$seg = $ascii[$i];
				}
			}

			$hex .= $seg;
		}


		return $hex;
	}

	// convert a hex string to ascii, prepend with '0' if input is not 
	// an even number of characters in length   
	function HexToAscii($hex)
	{
		$ascii = '';
   
		$len = strlen($hex);
		if ($len > 0 && $hex[0] == '!')
		{
			for ($i = 1; $i < $len; $i++)
			{
				if ($hex[$i] == '\\')
				{
					$j = $i + 1;
					$k = $i + 2;

					if ($k < $len && ctype_xdigit($hex[$j]) && ctype_xdigit($hex[$k]))
					{
						$seg = chr(base_convert(substr($hex, $j, 2), 16, 10));
						$i = $k;
					}
					else if ($j < $len)
					{
						$seg = $hex[$j];
						$i = $j;
					}
					else
					{
						// the last charater is a backslash
						$seg = $hex[$i];
					}
				}
				else if ($hex[$i] == ':')
				{
					$seg = '/';
				}
				else 
				{
					$seg = $hex[$i];
				}

				$ascii .= $seg;
			}	
		}
		else
		{
			if (strlen($hex) % 2 == 1) $hex = '0' . $hex;
   
			for($i = 0; $i < strlen($hex); $i += 2)
				$ascii .= chr(base_convert(substr($hex, $i, 2), 16, 10));
		}
   
		return $ascii;
	}

	function expand ($fmt, $vars)
	{
		foreach ($vars as $name => $value)
		{
			if (!is_scalar($value)) continue;
			// use preg_replace to match ${`$name`} or $`$name`
			$fmt = preg_replace (
				sprintf('/\$\{?%s\}?/', $name), $value, $fmt);
		}
		return $fmt;
	}

} 
?>
