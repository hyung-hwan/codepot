<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<script type="text/javascript" src="<?=base_url()?>/js/creole.js"></script>
<title><?=htmlspecialchars($wiki->name)?></title>
</head>

<body>

<div class="content" id="project_wiki_show_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$hexname = $this->converter->AsciiToHex ($wiki->name);
$this->load->view (
	'projectbar',
	array (
		'pageid' => 'wiki',
		'ctxmenuitems' => array (
			array ("wiki/create/{$project->id}", $this->lang->line('New')),
			array ("wiki/update/{$project->id}/{$hexname}", $this->lang->line('Edit')),
			array ("wiki/delete/{$project->id}/{$hexname}", $this->lang->line('Delete'))
		)
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="sidebar" id="project_wiki_show_sidebar">
<div class="box">
<ul>
<li><?=$this->lang->line('Created on')?> <?= $wiki->createdon ?></li>
<li><?=$this->lang->line('Last updated on')?> <?= $wiki->updatedon ?></li>
<li><?=$this->lang->line('Last updated by')?> <?= $wiki->updatedby ?></li>
</ul>
</div>
</div>

<div class="mainarea" id="project_wiki_show_mainarea">
<div class="title"><?=htmlspecialchars($wiki->name)?></div>

<div id="project_wiki_show_textarea">
<pre id="project_wiki_show_textpre" style="visibility: hidden">
<?php print htmlspecialchars($wiki->text); ?>
</pre>
</div> <!-- project_wiki_show_textarea -->

</div> <!-- project_wiki_show_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  project_wiki_show_content -->

<script type="text/javascript">
creole_render_wiki (
	"project_wiki_show_textpre", 
	"project_wiki_show_textarea", 
	""
);
</script>

</body>

</html>

