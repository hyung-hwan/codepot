<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/wiki.css')?>" />

<script type="text/javascript" src="<?=base_url_make('/js/creole.js')?>"></script>

<title><?=htmlspecialchars($wiki->name)?></title>
</head>

<?php
$hexname = $this->converter->AsciiToHex ($wiki->name);
?>

<script type="text/javascript">
function render_wiki()
{

	creole_render_wiki (
		"wiki_show_mainarea_wiki_text", 
		"wiki_show_mainarea_wiki", 
		"<?=site_url()?>/wiki/show/<?=$project->id?>/",
		"<?=site_url()?>/wiki/attachment/<?=$project->id?>/<?=$hexname?>/"
	);
}
</script>

<body onload="render_wiki()">

<div class="content" id="wiki_show_content">

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
			'id' => 'wiki',
			'project' => $project,
		),

		'ctxmenuitems' => array (
			array ("wiki/create/{$project->id}", $this->lang->line('New')),
			array ("wiki/update/{$project->id}/{$hexname}", $this->lang->line('Edit')),
			array ("wiki/delete/{$project->id}/{$hexname}", $this->lang->line('Delete'))
		)
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="sidebar" id="wiki_show_sidebar">
<div class="box">
<ul>
<li><?=$this->lang->line('Created on')?> <?= $wiki->createdon ?></li>
<li><?=$this->lang->line('Last updated on')?> <?= $wiki->updatedon ?></li>
<li><?=$this->lang->line('Last updated by')?> <?= $wiki->updatedby ?></li>
</ul>
</div>

<?php if (!empty($wiki->attachments)): ?>
	<div class="box">
		<div class="boxtitle"><?= $this->lang->line('WIKI_ATTACHMENTS') ?></div>
		<ul>
		<?php
			foreach ($wiki->attachments as $att)
			{
				$hexattname = $this->converter->AsciiToHex ($att->name);
				print '<li>';
				print anchor (
					"wiki/attachment/{$project->id}/{$hexname}/{$hexattname}", 
					htmlspecialchars($att->name)
				);
				print '</li>';
			}
		?>
		</ul>
	</div>
<?php endif; ?>

</div>

<div class="mainarea" id="wiki_show_mainarea">
<div class="title"><?=htmlspecialchars($wiki->name)?></div>

<div id="wiki_show_mainarea_wiki">
<pre id="wiki_show_mainarea_wiki_text" style="visibility: hidden">
<?php print htmlspecialchars($wiki->text); ?>
</pre>
</div> <!-- wiki_show_mainarea_wiki -->

</div> <!-- wiki_show_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  wiki_show_content -->

</body>

</html>

