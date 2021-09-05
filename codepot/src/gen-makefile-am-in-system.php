<?php

function gen_dir($basedir, $dir)
{
	$curdir = (substr($basedir, -1) == "/")? "$basedir$dir": "$basedir/$dir";

	$dh = opendir($curdir);
	if ($dh === false) return false;

	$file_list = [];
	$dir_list = [];
	while (($file = readdir($dh)) !== false) {
		if ($file == "." || $file == "..") continue;

		$actual_path = ($dir == "")? $file: "$dir/$file";
		$full_path = $basedir == ""? $full_path: "$basedir/$actual_path";

		if (is_dir($full_path))
		{
			gen_dir ($basedir, $actual_path);
			$dir_list[] = $file;
		}
		else
		{
			if ($file != "Makefile.am" && $file != "Makefile.in")
				$file_list[] = $file;
		}
	}
	closedir($dh);

	print "generating $curdir/Makefile.am\n";
	$fp = fopen ("$curdir/Makefile.am", "w");

	$dir_list_count = count($dir_list);
	if ($dir_list_count > 0)
	{
		fwrite ($fp, "SUBDIRS=");
		foreach ($dir_list as $tmp) fwrite ($fp, " \\\n\t" . $tmp);
		fwrite ($fp, "\n\n");
	}

	fwrite ($fp, 'wwwdir=$(WWWDIR)/' . $dir . "\n\n");

	fwrite ($fp, "www_DATA=");
	foreach ($file_list as $tmp) fwrite ($fp, " \\\n\t" . $tmp);
	fwrite ($fp, "\n\n");
	fwrite ($fp, 'EXTRA_DIST = $(www_DATA)' . "\n");

	fclose ($fp);

	return true;
}

//gen_dir("/etc", "");
gen_dir(getcwd(), "system");
