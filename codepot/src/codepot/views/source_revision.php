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

<!--
<div class="sidebar" id="project_source_revision_mainarea_sidebar">
<div class="box">
<ul>
<li><?=$this->lang->line('Revision')?>: <?=htmlspecialchars($history['rev'])?></li>
<li><?=$this->lang->line('Author')?>: <?=htmlspecialchars($history['author'])?></li>
<li><?=$this->lang->line('Last updated on')?>: <?=date('r', strtotime($history['date']))?></li>
<li><?=$this->lang->line('Message')?>: <?=htmlspecialchars($history['msg'])?></li>
</ul>
</div>
</div>
-->

<div class="title" id="project_source_revision_mainarea_title">
<?php
function print_path ($project, $path, $rev, $converter)
{
	$exps = explode ('/', $path);
	$expsize = count($exps);
	$par = '';
	for ($i = 1; $i < $expsize; $i++)
	{
		$par .= '/' . $exps[$i];
		$hexpar = $converter->AsciiToHex ($par);
		print '/';
		$xpar = "source/revision/folder/{$project->id}/{$hexpar}";
		if ($rev != '') $xpar .=  "/{$rev}";
		print anchor ($xpar, htmlspecialchars($exps[$i]));
	}
}

$hexfolder = $this->converter->AsciiToHex('.');
print anchor ("/source/revision/folder/{$project->id}/{$hexfolder}/{$revision}", htmlspecialchars($project->name));
if ($folder != '') print_path ($project, $folder, $revision, $this->converter);
?>
</div>

<div class="menu" id="project_source_revision_mainarea_menu">
<?php
$hexfolder = $this->converter->AsciiToHex(($folder == '')? '.': $folder);
if ($revision > 0 && $revision < $next_revision)
{
	print anchor ("source/revision/{$type}/{$project->id}/{$hexfolder}", $this->lang->line('Head revision'));
	print ' | ';
}
print anchor ("source/history/{$type}/{$project->id}/{$hexfolder}", $this->lang->line('History'));
?>
</div> <!-- project_source_revision_mainarea_menu -->

<div class="infostrip" id="project_source_revision_mainarea_infostrip">
<?=anchor ("source/revision/{$type}/{$project->id}/${hexfolder}/{$prev_revision}", '<<')?> 
<?=$this->lang->line('Revision')?>: <?=$history['rev']?> 
<?=anchor ("source/revision/{$type}/{$project->id}/${hexfolder}/{$next_revision}", '>>')?> | 
<?=$this->lang->line('Author')?>: <?=htmlspecialchars($history['author'])?> | 
<?=$this->lang->line('Last updated on')?>: <?=date('r', strtotime($history['date']))?>
</div>


<div id="project_source_revision_mainarea_result">
<table id="project_source_revision_mainarea_result_table">
<?php 
	print '<tr class="heading">';
	print '<th>' .  $this->lang->line('Path') . '</th>';
	print '<th></th>';
	print '</tr>';
	
	$rowclasses = array ('even', 'odd');
	$rowcount = 0;
	foreach ($history['paths'] as $p)
	{
		$rowclass = $rowclasses[++$rowcount % 2];
		print "<tr class='{$rowclass}'>";

		$hexpar = $this->converter->AsciiToHex ($p['path']);

		print "<td class='{$p['action']}'>";
		print htmlspecialchars($p['path']);
		print '</td>';

		print '<td>';
/*
		if ($type == 'folder')
		{
			print anchor ("source/folder/{$project->id}/{$hexpar}/{$history['rev']}", $this->lang->line('Folder'));
		}
		else
		{
*/
			print anchor ("source/file/{$project->id}/{$hexpar}/{$history['rev']}", $this->lang->line('Details'));
			print ' ';
			print anchor ("source/blame/{$project->id}/{$hexpar}/{$history['rev']}", $this->lang->line('Blame'));
			print ' ';
			print anchor ("source/diff/{$project->id}/{$hexpar}/{$history['rev']}", $this->lang->line('Difference'));
/*
		}
*/
		print '</td>';
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

