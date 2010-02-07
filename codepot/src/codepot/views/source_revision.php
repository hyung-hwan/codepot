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

<div class="sidebar" id="project_source_revision_sidebar">
</div> <!-- project_source_revision_sidebar -->

<!---------------------------------------------------------------------------->


<div class="mainarea" id="project_source_revision_mainarea">

<?php
$history = $file['history'];
?>

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
		$xpar = "source/revision/{$project->id}/{$hexpar}";
		if ($rev != '') $xpar .=  "/{$rev}";
		print anchor ($xpar, htmlspecialchars($exps[$i]));
	}
}

$hexfolder = $this->converter->AsciiToHex('.');
print anchor ("/source/revision/{$project->id}/{$hexfolder}/{$revision}", htmlspecialchars($project->name));
if ($folder != '') print_path ($project, $folder, $revision, $this->converter);
?>
</div>

<div class="menu" id="project_source_revision_mainarea_menu">
<?php
$hexfolder = $this->converter->AsciiToHex(($folder == '')? '.': $folder);
$xpar = "source/history/file/{$project->id}/{$hexfolder}";
print anchor ($xpar, $this->lang->line('History'));
?>
</div> <!-- project_source_revision_mainarea_menu -->

<div id="project_source_revision_mainarea_result">
<?php 
	print '<ul id="project_source_revision_mainarea_result_table_path_list">';
	foreach ($history['paths'] as $p)
	{
		print '<li>';
		print '[';
		print $p['action'];
		print '] ';
		$hexpar = $this->converter->AsciiToHex ($p['path']);
		print anchor ("source/file/{$project->id}/{$hexpar}/{$history['rev']}", htmlspecialchars($p['path']));
		print '</li>';
	}
	print '</ul>';
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

