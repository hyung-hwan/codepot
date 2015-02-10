<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('base_url_make'))
{
	function base_url_make($path)
	{
		$CI =& get_instance();
		$url = $CI->config->slash_item('base_url');
		if (substr($url, -1) == '/')
		{
			for ($i = 0; substr($path, $i, 1) == '/'; $i++);
			return $url . substr($path, $i);
		}
		else return $url . $path;
	}
}

if ( ! function_exists('codepot_merge_path'))
{
	function codepot_merge_path($base, $path)
	{
		if (substr($base, -1) == '/')
		{
			for ($i = 0; substr($path, $i, 1) == '/'; $i++);
			return $base . substr($path, $i);
		}
		else return $base . $path;
	}
}



if ( !function_exists ('codepot_json_encode'))
{
	function codepot_json_encode( $data ) 
	{
		if( is_array($data) || is_object($data) ) 
		{
			$islist = is_array($data) && ( empty($data) || array_keys($data) === range(0,count($data)-1) );

			if( $islist ) 
			{
				$json = '[' . implode(',', array_map('codepot_json_encode', $data) ) . ']';
			} 
			else 
			{
				$items = Array();
				foreach( $data as $key => $value ) 
				{
					$items[] = codepot_json_encode("$key") . ':' . codepot_json_encode($value);
				}
				$json = '{' . implode(',', $items) . '}';
			}
		} 
		elseif( is_string($data) ) 
		{
			# Escape non-printable or Non-ASCII characters.
			# I also put the \\ character first, as suggested in comments on the 'addclashes' page.
			$string = '"' . addcslashes($data, "\\\"\n\r\t/" . chr(8) . chr(12)) . '"';
			$json    = '';
			$len    = strlen($string);
			# Convert UTF-8 to Hexadecimal Codepoints.
			for( $i = 0; $i < $len; $i++ ) 
			{
				$char = $string[$i];
				$c1 = ord($char);
			   
				# Single byte;
				if( $c1 <128 ) 
				{
					$json .= ($c1 > 31) ? $char : sprintf("\\u%04x", $c1);
					continue;
				}
			   
				# Double byte
				$c2 = ord($string[++$i]);
				if ( ($c1 & 32) === 0 )
				{
					$json .= sprintf("\\u%04x", ($c1 - 192) * 64 + $c2 - 128);
					continue;
				}
			   
				# Triple
				$c3 = ord($string[++$i]);
				if( ($c1 & 16) === 0 )
				{
					$json .= sprintf("\\u%04x", (($c1 - 224) <<12) + (($c2 - 128) << 6) + ($c3 - 128));
					continue;
				}
				   
				# Quadruple
				$c4 = ord($string[++$i]);
				if( ($c1 & 8 ) === 0 )
				{
					$u = (($c1 & 15) << 2) + (($c2>>4) & 3) - 1;

					$w1 = (54<<10) + ($u<<6) + (($c2 & 15) << 2) + (($c3>>4) & 3);
					$w2 = (55<<10) + (($c3 & 15)<<6) + ($c4-128);
					$json .= sprintf("\\u%04x\\u%04x", $w1, $w2);
				}
			}
		} 
		else 
		{
			# int, floats, bools, null
			$json = strtolower(var_export( $data, true ));
		}
		return $json;
	} 

}

