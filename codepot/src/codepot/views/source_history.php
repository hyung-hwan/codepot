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
function print_path ($project, $path, $type, $converter, $rev = SVN_REVISION_HEAD)
{
	$exps = explode ('/', $path);
	$expsize = count($exps);
	$par = '';
	for ($i = 1; $i < $expsize; $i++)
	{
		$par .= '/' . $exps[$i];
		$hexpar = $converter->AsciiToHex ($par);
		print '/';
		$xpar = "source/history/$type/{$project->id}/{$hexpar}";
		if ($rev != '') $xpar .=  "/{$rev}";
		print anchor ($xpar, htmlspecialchars($exps[$i]));
	}
}

print anchor ("/source/history/folder/{$project->id}", htmlspecialchars($project->name));
if ($folder != '') print_path ($project, $folder, 'folder', $this->converter);
?>
</div>

<div class="menu" id="project_source_history_mainarea_menu">
<?php
/* the menu here prints links to the lastest revision */
if ($type == 'folder')
{
	$par = $this->converter->AsciiTohex ($folder);
	$xpar = "source/folder/{$project->id}/{$par}";
	print anchor ($xpar, $this->lang->line('Directory'));
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
	<th><?=$this->lang->line('Time')?></th>
	<th><?=$this->lang->line('Message')?></th>
	<th></th>
</tr>
<?php 
	$rowclasses = array ('even', 'odd');
	$history = $file['history'];
	$history_count = count($history);	
	for ($i = $history_count; $i > 0; )
	{
		$h = $history[--$i];

		$rowclass = $rowclasses[($history_count - $i) % 2];
		print "<tr class='{$rowclass}'>";

		print '<td>';
		$hexfolder = $this->converter->AsciiToHex(($folder == '')? '.': $folder);
		if ($type == 'folder')
			print anchor ("/source/revision/{$project->id}/{$hexfolder}/{$h['rev']}", $h['rev']);
		else
			print anchor ("/source/$type/{$project->id}/{$hexfolder}/{$h['rev']}", $h['rev']);
		print '</td>';

		print '<td>';
		print htmlspecialchars($h['author']);
		print '</td>';

		print '<td><code>';
		//print date('r', strtotime($h['date']));
		print date('Y-m-d', strtotime($h['date']));
		print '</code></td>';

		print '<td>';
		print htmlspecialchars($h['msg']);
		print '</td>';

		print '<td>';
		if ($type == 'folder')	
		{
			print anchor ("/source/folder/{$project->id}/{$hexfolder}/{$h['rev']}", 
				$this->lang->line('Directory'));
		}
		else
		{
			print anchor ("/source/blame/{$project->id}/{$hexfolder}/{$h['rev']}", 
				$this->lang->line('Blame'));
			print ' | ';
			print anchor ("/source/diff/{$project->id}/{$hexfolder}/{$h['rev']}", 
				$this->lang->line('Difference'));
		}
		print '</td>';

		print '</tr>';
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

