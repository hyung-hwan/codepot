<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); 
 
class Converter
{
	function Converter ()
	{
	}

	// convert an ascii string to its hex representation
	function AsciiToHex($ascii)
	{
		$hex = '';

		for($i = 0; $i < strlen($ascii); $i++)
		$hex .= str_pad(base_convert(ord($ascii[$i]), 10, 16), 2, '0', STR_PAD_LEFT);

		return $hex;
	}

	// convert a hex string to ascii, prepend with '0' if input is not 
	// an even number of characters in length   
	function HexToAscii($hex)
	{
		$ascii = '';
   
		if (strlen($hex) % 2 == 1) $hex = '0'.$hex;
   
		for($i = 0; $i < strlen($hex); $i += 2)
		$ascii .= chr(base_convert(substr($hex, $i, 2), 16, 10));
   
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
