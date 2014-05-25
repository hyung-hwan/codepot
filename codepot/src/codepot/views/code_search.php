<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/code.css')?>" />

<script type="text/javascript" src="<?=base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/jquery-ui.css')?>" />

<?php
	$file_count = count($file['content']);
?>

<title><?=htmlspecialchars($project->name)?></title>
</head>

<body>

<div class="content" id="code_search_content">

<!-- ================================================================== -->

<?php $this->load->view ('taskbar'); ?>

<!-- ================================================================== -->

<?php
$this->load->view (
	'projectbar',
	array (
		'banner' => NULL,

		'page' => array (
			'type' => 'project',
			'id' => 'code',
			'project' => $project,
		),

		'ctxmenuitems' => array ()
	)
);
?>

<!-- ================================================================== -->

<div class="mainarea" id="code_search_mainarea">

<div class="title">
<?php
	if ($revision <= 0)
	{
		$revreq = '';
		$revreqroot = '';
	}
	else
	{
		$revreq = "/{$revision}";
		$revreqroot = '/' . $this->converter->AsciiToHex('.') . $revreq;
	}

	// print the main anchor for the root folder. 
	// let the anchor text be the project name.
	print anchor (
		"code/file/{$project->id}{$revreqroot}", 
		htmlspecialchars($project->name));

	// explode non-root folder parts to anchors
	$exps = explode ('/', $headpath);
	$expsize = count($exps);
	$par = '';
	for ($i = 1; $i < $expsize; $i++)
	{
		print '/';
		$par .= '/' . $exps[$i];
		$xpar = $this->converter->AsciiToHex ($par);
		print anchor (
			"code/file/{$project->id}/{$xpar}{$revreq}",
			htmlspecialchars($exps[$i]));
	}

	if ($headpath != $file['fullpath'])
	{
		print ' - ';
		print htmlspecialchars ($file['fullpath']);
	}
?>
</div>


<div class="infostrip" id="code_search_mainarea_infostrip">
	<?=form_open("code/search/{$project->id}/", 'id="code_search_search_form"')?>
	<?=form_hidden ('search_folder', set_value('search_folder', $file['fullpath']), 'id="code_search_search_folder"')?>
	<?=form_hidden ('search_revision', set_value('search_revision', $revision), 'id="code_search_search_revision"')?>
	<?=form_input ('search_pattern', set_value('search_pattern', ''), 'id="code_search_search_pattern"')?>
	<?=form_submit ('search_submit', $this->lang->line('Search'), 'id="code_search_search_submit"')?>
	| 
	<?=$this->lang->line('Revision')?>: <?=$file['created_rev']?> 
	<?=form_close()?>
</div>

<div id="code_search_mainarea_result">

<?php
function search_and_show ($controller, $project, $path, $revision, $pattern, $recurse)
{
	//$file = $controller->subversion->getFile ($project->id, $path, $revision);
	//if ($file['type'] == 'file') return;

	$dirarray = array ($path);

	while (count($dirarray) > 0)
	{
		$path = array_shift ($dirarray);
		$file = $controller->subversion->getFile ($project->id, $path, $revision);
		if ($file === FALSE || $file['type'] == 'file') continue;;

		// search in a directory.	
		$file_list = $file['content'];
		foreach ($file_list as $f)
		{
			$fullpath = $file['fullpath'] . '/' . $f['name'];
			$file2 = $controller->subversion->getFile ($project->id, $fullpath, $revision);
			if ($file2 !== FALSE)
			{
				if ($file2['type'] == 'file')
				{
					$lines = explode ("\n", $file2['content']);
					//$matchkeys = preg_grep ("/{$pattern}/i", $lines, 0);
					// TODO: allow regular expression
					$escaped_pattern  = preg_quote ($pattern, '/');
					$matchlines = @preg_grep ("/{$pattern}/", $lines, 0);
					if ($matchlines === FALSE)
					{
						print $controller->lang->line('CODE_BAD_SEARCH_PATTERN'); 
						return;
					}
					else if (count($matchlines) > 0)
					{
						$hexpath = $controller->converter->AsciiToHex($fullpath);
						if ($revision <= 0)
						{
							$revreq = '';
							$revreqroot = '';
						}
						else
						{
							$revreq = "/{$file2['created_rev']}";
							$revreqroot = '/' . $controller->converter->AsciiToHex ('.') . $revreq;
						}
	
						print '<li>';
						print anchor (
							"code/file/{$project->id}/{$hexpath}{$revreq}",
							htmlspecialchars($fullpath));
	
						print '<pre>';
						foreach ($matchlines as $linenum => $line)
						{
							printf ('% 6d: ', $linenum);
							print htmlspecialchars($line);
							print "\n";
						}
						print '</pre>';
	
						print '</li>';
					}
				}
				else
				{

					if ($recurse && count($file2['content']) > 0)
					{
						array_push ($dirarray, $fullpath);
					}
				}
			}
		}
	}
}

// the repository search can take very long.
// change the execution time limit to run this script forever if it's allowed.
if (CODEPOT_ALLOW_SET_TIME_LIMIT) set_time_limit (0);

// TODO: prevent recursion to subdirectories depending on input
search_and_show ($this, $project, $file['fullpath'], $revision, $pattern, TRUE);
?>

</div> <!-- code_search_mainarea_result -->

</div> <!-- code_search_mainarea -->


<!-- ================================================================== -->

<?php $this->load->view ('footer'); ?>

<!-- ================================================================== -->

</div> <!--  code_search_content -->

</body>

</html>

