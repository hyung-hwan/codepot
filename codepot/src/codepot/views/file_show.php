<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<script type="text/javascript" src="<?=base_url()?>/js/creole.js"></script>

<script type="text/javascript">
function render_wiki()
{
	creole_render_wiki (
		"project_file_show_textpre", 
		"project_file_show_textarea", 
		"<?=dirname(dirname(dirname(dirname(current_url()))))?>/wiki/show/<?=$project->id?>/"
	);
}
</script>

<title><?=htmlspecialchars($file->name)?></title>
</head>

<body onLoad="render_wiki()">

<div class="content" id="project_file_show_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$hexname = $this->converter->AsciiToHex ($file->name);
$this->load->view (
	'projectbar',
	array (
		'site' => NULL,
		'pageid' => 'file',
		'ctxmenuitems' => array (
			array ("file/create/{$project->id}", $this->lang->line('New')),
			array ("file/update/{$project->id}/{$hexname}", $this->lang->line('Edit')),
			array ("file/delete/{$project->id}/{$hexname}", $this->lang->line('Delete'))
		)
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="sidebar" id="project_file_show_sidebar">

<div class="box">
<ul>
<li><?=$this->lang->line('Created on')?> <?= $file->updatedon ?></li>
<li><?=$this->lang->line('Last updated on')?> <?= $file->updatedon ?></li>
<li><?=$this->lang->line('Last updated by')?> <?= $file->updatedby ?></li>
</ul>
</div>

<div class="box">
<div class="boxtitle"><?=$this->lang->line('MD5')?></div>
<?= $file->md5sum ?>
</div>

<?= anchor ("file/get/{$project->id}/". $this->converter->AsciiToHex($file->name), '['.$this->lang->line('Download').']') ?>

</div>

<div class="mainarea" id="project_file_show_mainarea">
<div class="title"><?=htmlspecialchars($file->name)?></div>

<div id="project_file_show_textarea">
<pre id="project_file_show_textpre" style="visibility: hidden">
<?php print htmlspecialchars($file->description); ?>
</pre>
</div> <!-- project_file_show_textarea -->

</div> <!-- project_file_show_mainarea -->


<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  project_file_show_content -->

</body>

</html>

