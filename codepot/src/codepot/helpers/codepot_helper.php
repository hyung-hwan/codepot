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

if ( !function_exists ('codepot_delete_files'))
{
	function codepot_delete_files($path, $del_dir = FALSE, $level = 0)
	{	
		// Trim the trailing slash
		$path = rtrim($path, DIRECTORY_SEPARATOR);
			
		if ( ! $current_dir = @opendir($path))
			return;
	
		while(FALSE !== ($filename = @readdir($current_dir)))
		{
			if ($filename != "." && $filename != "..")
			{
				if (is_dir($path.DIRECTORY_SEPARATOR.$filename))
				{
					codepot_delete_files($path.DIRECTORY_SEPARATOR.$filename, $del_dir, $level + 1);
				}
				else
				{
					@unlink($path.DIRECTORY_SEPARATOR.$filename);
				}
			}
		}
		@closedir($current_dir);
	
		if ($del_dir == TRUE /*&& $level > 0*/)
		{
			@rmdir($path);
		}
	}
}

if ( !function_exists ('codepot_zip_dir'))
{
	// $output_file: zip file to create
	// $path: directory to zip recursively
	// $local_path: the leading $path part is translated to $local_path
	// $exclude: file names to exclude. string or array of strings
	function codepot_zip_dir ($output_file, $path, $local_path = NULL, $exclude = NULL)
	{
		$stack = array ();

		if (!is_dir($path)) return FALSE;

		array_push ($stack, $path); 
		$prefix = strlen($path);

		$zip = new ZipArchive();
		if (@$zip->open ($output_file, ZipArchive::OVERWRITE) === FALSE) return FALSE;

		while (!empty($stack))
		{
			$dir = array_pop($stack);

			$d = @opendir ($dir);
			if ($d === FALSE) continue;

			$new_path = empty($local_path)? $dir: substr_replace($dir, $local_path, 0, $prefix);
			if (@$zip->addEmptyDir($new_path) == FALSE)
			{
				@closedir ($dir);
				$zip->close ();
				return FALSE;
			}

			//printf (">> [%s] [%s]\n", $dir, $new_path);
			while (($f = @readdir($d)) !== FALSE)
			{
				if ($f == '.' || $f == '..') continue;
				if (!empty($exclude))
				{
					$found = FALSE;
					if (is_array($exclude))
					{
						foreach ($exclude as $ex)
						{
							if (fnmatch ($ex, $f, FNM_PERIOD | FNM_PATHNAME)) 
							{
								$found = TRUE;
								break;
							}
						}
						if ($found) continue;
					}
					else if (fnmatch($exclude, $f, FNM_PERIOD | FNM_PATHNAME)) continue;
				}

				$full_path = $dir . DIRECTORY_SEPARATOR . $f;
				if (is_dir($full_path))
				{
					array_push ($stack, $full_path);
				}
				else
				{
					$new_path = empty($local_path)? $dir: substr_replace($full_path, $local_path, 0, $prefix);
					@$zip->addFile ($full_path, $new_path);
					//printf ("[%s] [%s]\n", $full_path, $new_path);
				}
			}

			@closedir ($dir);
		}

		$zip->close ();
		return TRUE;
	}
}

if ( !function_exists ('codepot_find_longest_matching_sequence'))
{
	function codepot_find_longest_matching_sequence ($old, $old_start, $old_len, $new, $new_start, $new_len)
	{
		$old_end = $old_start + $old_len;
		$new_end = $new_start + $new_len;
	
		$match_start_in_old = $old_start;
		$match_start_in_new = $new_start;
		$match_len = 0;
	
		$runs = array ();
		for ($i = $old_start; $i < $old_end; $i++)
		{
			$new_runs = array();
			for ($j = $new_start; $j < $new_end; $j++)
			{
				if ($old[$i] == $new[$j])
				{
					if (isset($runs[$j - 1]))
						$new_runs[$j] = $runs[$j - 1] + 1;
					else
						$new_runs[$j] = 1;
					if ($new_runs[$j] > $match_len)
					{
						$match_len = $new_runs[$j];
						$match_start_in_old = ($i + 1) - $match_len;
						$match_start_in_new = ($j + 1) - $match_len;
					}
				}
			}
			$runs = $new_runs;
		}


		//print "$match_start_in_old\n";
		//print "$match_start_in_new\n";
		//print "$match_len\n";
		//print "----------------\n";

		return array ($match_len, $match_start_in_old, $match_start_in_new);
	}
}


if ( !function_exists ('codepot_find_matching_sequences'))
{
	function codepot_find_matching_sequences ($old, $new)
	{
		$stack = array ();
		$result = array ();
	
		if (is_array($old) && is_array($new))
		{
			$old_size = count($old);
			$new_size = count($new);
		}
		else if (is_string($old) && is_string($new))
		{
			$old_size = strlen($old);
			$new_size = strlen($new);
		}
		else
		{
			return FALSE;
		}
	
		// push the whole range for the initial search.
		array_push ($stack, array (0, 0, $old_size, 0, $new_size));
	
		while (count($stack) > 0)
		{
			$item = array_pop($stack);
	
			if ($item[0] == 0)
			{
				$old_seg_pos = $item[1];
				$old_seg_len = $item[2];
				$new_seg_pos = $item[3];
				$new_seg_len = $item[4];
			
				list ($match_len, $match_start_in_old, $match_start_in_new) = 
					codepot_find_longest_matching_sequence ($old, $old_seg_pos, $old_seg_len, $new, $new_seg_pos, $new_seg_len);
				if ($match_len > 0)
				{
					// push the back part
					$a = $match_start_in_old + $match_len;   // beginning of the back part of $old
					$b = $old_seg_len - ($a - $old_seg_pos); // length of the back part of $old
					$c = $match_start_in_new + $match_len;   // beginning of the back part in $new
					$d = $new_seg_len - ($c - $new_seg_pos); // length of the back part of $new
					if ($b > 0 && $d > 0) array_push ($stack, array (0, $a, $b, $c, $d));
	
					// push the longest sequence found to hold it until the front part 
					// has been processed.
					array_push ($stack, array (1, $match_start_in_old, $match_start_in_new, $match_len));
	
					// push the front part
					$b = $match_start_in_old - $old_seg_pos; // length of the front part of $old
					$d = $match_start_in_new - $new_seg_pos; // length of the front part of $new
					if ($b > 0 && $d > 0) array_push ($stack, array (0, $old_seg_pos, $b, $new_seg_pos, $d));
				}
			}
			else
			{
				// move the result item to the result array
				array_push ($result, array_slice ($item, 1, 3));
			}
		}
	
	
		return $result;
	}
}
