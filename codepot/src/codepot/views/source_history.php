<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<title><?=htmlspecialchars($project->name)?></title>
</head>

<body>

<div class="content" id="project_source_history_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
	'projectbar',
	array (
		'pageid' => 'source',
		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="sidebar" id="project_source_history_sidebar">
</div> <!-- project_source_history_sidebar -->

<!---------------------------------------------------------------------------->


<div class="mainarea" id="project_source_history_mainarea">

<div class="title" id="project_source_history_mainarea_title">
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

	// print the anchor for the root folder with a project name
	print anchor (
		"/source/history/folder/{$project->id}{$revreqroot}",
		htmlspecialchars($project->name));

	// explodes part of the folder name into an array 
	$exps = explode ('/', $folder);
	$expsize = count($exps);
	$par = '';
	// print anchors pointing to each part
	for ($i = 1; $i < $expsize; $i++)
	{
		print '/';
		$par .= '/' . $exps[$i];
		$xpar = $this->converter->AsciiToHex ($par);
		print anchor (
			"source/history/folder/{$project->id}/{$xpar}{$revreq}",
			htmlspecialchars($exps[$i]));
	}
?>
</div>

<div class="menu" id="project_source_history_mainarea_menu">
<?php
	/* the menu here prints links to the lastest revision */
	if ($type == 'folder')
	{
		$par = $this->converter->AsciiTohex ($folder);
		$xpar = "source/folder/{$project->id}/{$par}";
		print anchor ($xpar, $this->lang->line('Folder'));
	}
	else
	{
		$par = $this->converter->AsciiTohex ($folder);
		$xpar = "source/file/{$project->id}/{$par}";
		print anchor ($xpar, $this->lang->line('Details'));
		print ' | ';
		$xpar = "source/blame/{$project->id}/{$par}";
		print anchor ($xpar, $this->lang->line('Blame'));
		print ' | ';
		$xpar = "source/diff/{$project->id}/{$par}";
		print anchor ($xpar, $this->lang->line('Difference'));
	}
?>
</div> <!-- project_source_history_mainarea_menu -->

<div id="project_source_history_mainarea_result">
<table id="project_source_history_mainarea_result_table">
<tr class='heading'>
	<th><?=$this->lang->line('Revision')?></th>
	<th><?=$this->lang->line('Author')?></th>
	<th><?=$this->lang->line('Date')?></th>
	<th><?=$this->lang->line('Message')?></th>
	<th></th>
</tr>
<?php 
	$rowclasses = array ('even', 'odd');
	$history = $file['history'];
	$history_count = count($history);	
	$curfolder = $folder;
	for ($i = $history_count; $i > 0; )
	{
		$h = $history[--$i];

		$rowclass = $rowclasses[($history_count - $i) % 2];
		print "<tr class='{$rowclass}'>";

		print '<td>';
		//
		// it seems the history can be retrieved only from the latest name */
		//
		$xfolder = $this->converter->AsciiToHex(($folder == '')? '.': $folder);
		if ($type == 'folder')
			print anchor ("/source/revision/{$type}/{$project->id}/{$xfolder}/{$h['rev']}", $h['rev']);
		else
			print anchor ("/source/{$type}/{$project->id}/{$xfolder}/{$h['rev']}", $h['rev']);
		print '</td>';

		print '<td>';
		print htmlspecialchars($h['author']);
		print '</td>';

		print '<td><code>';
		//print date('r', strtotime($h['date']));
		print date('Y-m-d', strtotime($h['date']));
		print '</code></td>';

		print '<td>';
		print '<pre>';
		print htmlspecialchars($h['msg']);
		print '</pre>';
		print '</td>';

		print '<td>';
		//
		// the actual folder or file contents must be accessed with the name
		// at a particular revision.
		//
		$xfolder = $this->converter->AsciiToHex(($curfolder == '')? '.': $curfolder);
		if ($type == 'folder')	
		{
			print anchor ("/source/folder/{$project->id}/{$xfolder}/{$h['rev']}", 
				$this->lang->line('Folder'));
		}
		else
		{
			print anchor ("/source/blame/{$project->id}/{$xfolder}/{$h['rev']}", 
				$this->lang->line('Blame'));
			print ' ';
			print anchor ("/source/diff/{$project->id}/{$xfolder}/{$h['rev']}", 
				$this->lang->line('Difference'));
		}
		print '</td>';

		print '</tr>';

		//
		// let's track the copy path.
		//
		$paths = $h['paths'];
		foreach ($paths as $p)
		{
			if (array_key_exists ('copyfrom', $p) &&
			    $p['path'] == $curfolder && $p['action'] == 'A')
			{
				$curfolder = $p['copyfrom'];
				print "<tr class='title'><td colspan=5>{$curfolder}</td></tr>";
			}
		}

	}
?>
</table>
</div> <!-- project_source_history_mainarea_body -->

</div> <!-- project_source_history_mainarea -->


<!---------------------------------------------------------------------------->


<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  project_source_history_content -->

</body>

</html>

