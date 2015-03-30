<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/file.css')?>" />

<script type="text/javascript" src="<?=base_url_make('/js/creole.js')?>"></script>

<script type="text/javascript" src="<?=base_url_make('/js/prettify/prettify.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/prettify/lang-css.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/prettify/lang-lisp.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/prettify/lang-lua.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/prettify/lang-sql.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/prettify/lang-vb.js')?>"></script>

<script type="text/javascript" src="<?=base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/jquery-ui.css')?>" />

<script type="text/javascript">
$(function () {
	if ($("#file_show_mainarea_result_info").is(":visible"))
		btn_label = "<?=$this->lang->line('Hide details')?>";
	else
		btn_label = "<?=$this->lang->line('Show details')?>";
	

	btn = $("#file_show_mainarea_details_button").button({"label": btn_label}).click (function () {
		
		if ($("#file_show_mainarea_result_info").is(":visible"))
		{
			$("#file_show_mainarea_result_info").hide("blind",{},200);
			$("#file_show_mainarea_details_button").button(
				"option", "label", "<?=$this->lang->line('Show details')?>");
		}
		else
		{
			$("#file_show_mainarea_result_info").show("blind",{},200);
			$("#file_show_mainarea_details_button").button(
				"option", "label", "<?=$this->lang->line('Hide details')?>");
		}
	});
});

function render_wiki()
{
	creole_render_wiki (
		"file_show_mainarea_wiki_text", 
		"file_show_mainarea_wiki", 
		"<?=site_url()?>/wiki/show/<?=$project->id?>/",
		"<?=site_url()?>/wiki/attachment0/<?=$project->id?>/"
	);

	prettyPrint ();
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


<div class="mainarea" id="file_show_mainarea">
<div class="title"><?=htmlspecialchars($file->name)?></div>

<div class="infostrip" id="wiki_show_mainarea_infostrip">
	<?= anchor ("file/get/{$project->id}/". $this->converter->AsciiToHex($file->name), $this->lang->line('Download')) ?>
	| <a id="file_show_mainarea_details_button" href='#'><?=$this->lang->line('Details')?></a>
</div>

<div id="file_show_mainarea_result">


<div class="result" id="file_show_mainarea_wiki">
<pre id="file_show_mainarea_wiki_text" style="visibility: hidden">
<?php print htmlspecialchars($file->description); ?>
</pre>
</div> <!-- file_show_mainarea_wiki -->



<div id="file_show_mainarea_result_info">

	<ul>
	<li><?=$this->lang->line('Created on')?> <?= $file->createdon ?></li>
	<li><?=$this->lang->line('Created by')?> <?= $file->createdby ?></li>
	<li><?=$this->lang->line('Last updated on')?> <?= $file->updatedon ?></li>
	<li><?=$this->lang->line('Last updated by')?> <?= $file->updatedby ?></li>
	</ul>

	<div class="title"><?=$this->lang->line('MD5')?></div>
	<ul>
	<li><?= $file->md5sum ?></li>
	</ul>

</div> <!-- file_show_mainarea_result_info -->



</div> <!-- file_show_mainarea_result -->

</div> <!-- file_show_mainarea -->


<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  file_show_content -->

</body>

</html>

