<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/code.css" />
<title><?=htmlspecialchars($project->name)?></title>
</head>

<body>

<div class="content" id="code_revision_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

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

<!---------------------------------------------------------------------------->

<div class="mainarea" id="code_revision_mainarea">

<?php
$history = $file['history'];
?>

<div class="title" id="code_revision_mainarea_title">
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
		"code/revision/{$project->id}{$revreqroot}",
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
			"code/revision/{$project->id}/{$xpar}{$revreq}",
			htmlspecialchars($exps[$i]));
	}

	if ($headpath != $file['fullpath'])
	{
		print ' - ';
		print htmlspecialchars($file['fullpath']);
	}
?>
</div>

<div class="menu" id="code_revision_mainarea_menu">
<?php
	$xpar = $this->converter->AsciiToHex(($headpath == '')? '.': $headpath);
	if ($revision > 0 && $revision < $next_revision)
	{
		print anchor ("code/revision/{$project->id}/{$xpar}", $this->lang->line('Head revision'));
		print ' | ';
	}
	print anchor ("code/history/{$project->id}/{$xpar}", $this->lang->line('History'));
?>
</div> <!-- code_revision_mainarea_menu -->

<div class="infostrip" id="code_revision_mainarea_infostrip">
	<?=anchor ("code/revision/{$project->id}/${xpar}/{$prev_revision}", '<<')?> 
	<?=$this->lang->line('Revision')?>: <?=$history['rev']?> 
	<?=anchor ("code/revision/{$project->id}/${xpar}/{$next_revision}", '>>')?> | 
	<?=$this->lang->line('Author')?>: <?=htmlspecialchars($history['author'])?> | 
	<?=$this->lang->line('Last updated on')?>: <?=date('r', strtotime($history['date']))?>
</div>


<div id="code_revision_mainarea_result">

<div class="title">Message</div>
<pre id="code_revision_mainarea_result_msg">
<?=htmlspecialchars($history['msg'])?>
</pre>

<div class="title">Files updated</div>
<table id="code_revision_mainarea_result_table">
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
		print anchor ("code/file/{$project->id}/{$xpar}/{$history['rev']}", htmlspecialchars($p['path']));
		print '</td>';

		print '<td>';
		//print anchor ("code/blame/{$project->id}/{$xpar}/{$history['rev']}", $this->lang->line('Blame'));
		//print ' ';
		print anchor ("code/diff/{$project->id}/{$xpar}/{$history['rev']}", $this->lang->line('Difference'));
		print '</td>';

		print '</tr>';
	}
?>
</table>
</div> <!-- code_revision_mainarea_body -->

</div> <!-- code_revision_mainarea -->


<!---------------------------------------------------------------------------->


<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!-- code_revision_content -->

</body>

</html>

