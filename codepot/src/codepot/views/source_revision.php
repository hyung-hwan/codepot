<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<title><?=htmlspecialchars($project->name)?></title>
</head>

<body>

<div class="content" id="project_source_revision_content">

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

<div class="mainarea" id="project_source_revision_mainarea">

<?php
$history = $file['history'];
?>

<div class="title" id="project_source_revision_mainarea_title">
<?php
	if ($revision <= 0)
	{
		$revreq = '';
		$revreqroot = '';
	}
	else
	{
		$revreq = "/{$file['created_rev']}";
		$revreqroot = '/' . $this->converter->AsciiToHex ('.') . $revreq;
	}

	print anchor (
		"/source/revision/{$project->id}{$revreqroot}",
		htmlspecialchars($project->name));

	$exps = explode ('/', $headpath);
	$expsize = count($exps);
	$par = '';
	for ($i = 1; $i < $expsize; $i++)
	{
		$par .= '/' . $exps[$i];
		$xpar = $this->converter->AsciiToHex ($par);

		print '/';
		print anchor (
			"source/revision/{$project->id}/{$xpar}{$revreq}",
			htmlspecialchars($exps[$i]));
	}

	if ($headpath != $file['fullpath'])
	{
		print ' - ';
		print htmlspecialchars($file['fullpath']);
	}
?>
</div>

<div class="menu" id="project_source_revision_mainarea_menu">
<?php
	$xpar = $this->converter->AsciiToHex(($headpath == '')? '.': $headpath);
	if ($revision > 0 && $revision < $next_revision)
	{
		print anchor ("source/revision/{$project->id}/{$xpar}", $this->lang->line('Head revision'));
		print ' | ';
	}
	print anchor ("source/history/{$project->id}/{$xpar}", $this->lang->line('History'));
?>
</div> <!-- project_source_revision_mainarea_menu -->

<div class="infostrip" id="project_source_revision_mainarea_infostrip">
	<?=anchor ("source/revision/{$project->id}/${xpar}/{$prev_revision}", '<<')?> 
	<?=$this->lang->line('Revision')?>: <?=$history['rev']?> 
	<?=anchor ("source/revision/{$project->id}/${xpar}/{$next_revision}", '>>')?> | 
	<?=$this->lang->line('Author')?>: <?=htmlspecialchars($history['author'])?> | 
	<?=$this->lang->line('Last updated on')?>: <?=date('r', strtotime($history['date']))?>
</div>


<div id="project_source_revision_mainarea_result">

<div class="title">Message</div>
<pre id="project_source_revision_mainarea_result_msg">
<?=htmlspecialchars($history['msg'])?>
</pre>

<div class="title">Files updated</div>
<table id="project_source_revision_mainarea_result_table">
<?php 
	/*
	print '<tr class="heading">';
	print '<th>' .  $this->lang->line('Path') . '</th>';
	print '<th></th>';
	print '</tr>';
	*/
	
	$rowclasses = array ('odd', 'even');
	$rowcount = 0;
	foreach ($history['paths'] as $p)
	{
		$rowclass = $rowclasses[++$rowcount % 2];
		print "<tr class='{$rowclass}'>";

		$xpar = $this->converter->AsciiToHex ($p['path']);

		print "<td class='{$p['action']}'>";
		print anchor ("source/file/{$project->id}/{$xpar}/{$history['rev']}", htmlspecialchars($p['path']));
		print '</td>';

		/*
		print '<td>';
		print anchor ("source/blame/{$project->id}/{$xpar}/{$history['rev']}", $this->lang->line('Blame'));
		print ' ';
		print anchor ("source/diff/{$project->id}/{$xpar}/{$history['rev']}", $this->lang->line('Difference'));
		print '</td>';
		*/

		print '</tr>';
	}
?>
</table>
</div> <!-- project_source_revision_mainarea_body -->

</div> <!-- project_source_revision_mainarea -->


<!---------------------------------------------------------------------------->


<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  project_source_revision_content -->

</body>

</html>

