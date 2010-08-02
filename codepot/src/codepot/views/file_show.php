<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/file.css" />
<script type="text/javascript" src="<?=base_url()?>/js/creole.js"></script>

<script type="text/javascript">
function render_wiki()
{
	creole_render_wiki (
		"file_show_mainarea_wiki_text", 
		"file_show_mainarea_wiki", 
		"<?=site_url()?>/wiki/show/<?=$project->id?>/",
		"<?=site_url()?>/wiki/attachment0/<?=$project->id?>/"
	);
}
</script>

<title><?=htmlspecialchars($file->name)?></title>
</head>

<body onload="render_wiki()">

<div class="content" id="file_show_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$hexname = $this->converter->AsciiToHex ($file->name);
$this->load->view (
	'projectbar',
	array (
		'banner' => NULL,

		'page' => array (
			'type' => 'project',
			'id' => 'file',
			'project' => $project,
		),

		'ctxmenuitems' => array (
			array ("file/create/{$project->id}", $this->lang->line('New')),
			array ("file/update/{$project->id}/{$hexname}", $this->lang->line('Edit')),
			array ("file/delete/{$project->id}/{$hexname}", $this->lang->line('Delete'))
		)
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="sidebar" id="file_show_sidebar">

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

<div class="mainarea" id="file_show_mainarea">
<div class="title"><?=htmlspecialchars($file->name)?></div>

<div id="file_show_mainarea_wiki">
<pre id="file_show_mainarea_wiki_text" style="visibility: hidden">
<?php print htmlspecialchars($file->description); ?>
</pre>
</div> <!-- file_show_mainarea_wiki -->

</div> <!-- file_show_mainarea -->


<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  file_show_content -->

</body>

</html>

